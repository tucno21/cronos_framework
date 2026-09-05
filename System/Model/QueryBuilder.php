<?php

namespace Cronos\Model;

use Cronos\Database\DatabaseDriver;

/**
 * Constructor de consultas interno del ORM de Cronos.
 *
 * Cada cadena de llamadas (Usuario::where(...)->orderBy(...)->get()) crea SU
 * PROPIO QueryBuilder, por lo que el estado de una consulta nunca contamina
 * otra consulta ni otro modelo (a diferencia del antiguo estado estatico).
 *
 * Esta clase NO se usa directamente: se accede desde el modelo
 * (Usuario::where(...), $model->newQuery()->where(...)).
 */
final class QueryBuilder
{
    private const OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];

    private string $modelClass;

    private Model $model;

    private string $selects = '*';

    private array $joins = [];

    private array $wheres = [];

    private array $andOrWheres = [];

    private array $values = [];

    private array $orderBys = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private bool $boolWhere = false;

    private bool $boolWhereBetween = false;

    private bool $boolWhereConcat = false;

    /**
     * Relaciones a cargar con with().
     * Cada entrada: ['path' => 'usuario.perfil', 'constraint' => ?Closure]
     *
     * @var array<int, array{path: string, constraint: callable|null}>
     */
    private array $eager = [];

    /**
     * Conteos de relaciones con withCount(): nombre => ?Closure constraint.
     *
     * @var array<string, callable|null>
     */
    private array $counts = [];

    //SOFT DELETES A NIVEL CONSULTA (estilo Laravel)
    //withTrashed(): incluye registros borrados; onlyTrashed(): solo borrados
    private bool $withTrashed = false;

    private bool $onlyTrashed = false;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->model = new $modelClass();
    }

    //******************************************************************
    // METODOS DE CADENA (compatibles con la API anterior)
    //******************************************************************

    public function select(string ...$select): self
    {
        foreach ($select as $fragment) {
            if ($fragment === '*') {
                continue;
            }
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.(\*|[a-zA-Z_][a-zA-Z0-9_]*))?$/', $fragment)) {
                throw new \Error("Columna de seleccion no valida: {$fragment}");
            }
        }

        $this->selects = implode(', ', $select);

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        if ($table === '') {
            throw new \Error('Debe proveer el nombre de la tabla para el join');
        }

        if ($first === '' || $operator === '' || $second === '') {
            throw new \Error('Debe proveer las condiciones para el join');
        }

        $this->validateIdentifier($table, 'JOIN');
        $this->validateIdentifier($first, 'JOIN');
        $this->validateIdentifier($second, 'JOIN');
        $operador = $this->validateOperator($operator);

        $this->joins[] = "JOIN {$table} ON {$first} {$operador} {$second}";

        return $this;
    }

    public function where(string $columna, string|int $operadorOvalor, string|int|null $valor = null): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        $this->validateIdentifier($columna, 'WHERE');

        if ($valor === null) {
            if ($operadorOvalor === '') {
                throw new \Error('Debe proveer el segundo parametro para la condición WHERE');
            }

            $this->wheres[] = "{$columna} = ?";
            $this->values[] = $operadorOvalor;
        } else {
            $operador = $this->validateOperator((string) $operadorOvalor);
            $this->wheres[] = "{$columna} {$operador} ?";
            $this->values[] = $valor;
        }

        $this->boolWhere = true;

        return $this;
    }

    public function andWhere(string $columna, string|int $operadorOvalor, string|int|null $valor = null): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if (empty($this->wheres)) {
            throw new \Error('no existe el metodo where() o debe estar antes');
        }

        $this->validateIdentifier($columna, 'AND WHERE');

        if ($valor === null) {
            if ($operadorOvalor === '') {
                throw new \Error('Debe proveer el segundo parámetro para la condición WHERE');
            }

            $this->andOrWheres[] = "AND {$columna} = ?";
            $this->values[] = $operadorOvalor;
        } else {
            $operador = $this->validateOperator((string) $operadorOvalor);
            $this->andOrWheres[] = "AND {$columna} {$operador} ?";
            $this->values[] = $valor;
        }

        return $this;
    }

    public function orWhere(string $columna, string|int $operadorOvalor, string|int|null $valor = null): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if (empty($this->wheres)) {
            throw new \Error('no existe el metodo where() o debe estar antes');
        }

        $this->validateIdentifier($columna, 'OR WHERE');

        if ($valor === null) {
            if ($operadorOvalor === '') {
                throw new \Error('Debe proveer el segundo parámetro para la condición WHERE');
            }

            $this->andOrWheres[] = "OR {$columna} = ?";
            $this->values[] = $operadorOvalor;
        } else {
            $operador = $this->validateOperator((string) $operadorOvalor);
            $this->andOrWheres[] = "OR {$columna} {$operador} ?";
            $this->values[] = $valor;
        }

        return $this;
    }

    public function whereConcat(string $columna, string|int $operadorOvalor, string|int|null $valor = null): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if (!preg_match('/^[a-zA-Z0-9_,. ]+$/', $columna)) {
            throw new \Error("Identificador no valido para whereConcat: {$columna}");
        }

        $this->boolWhereConcat = true;

        if ($valor === null) {
            $this->wheres[] = "CONCAT({$columna}) = ?";
            $this->values[] = $operadorOvalor;
        } else {
            $operador = $this->validateOperator((string) $operadorOvalor);
            $this->wheres[] = "CONCAT({$columna}) {$operador} ?";
            $this->values[] = $valor;
        }

        return $this;
    }

    public function whereBetween(string $columna, string|int $valor1, string|int $valor2): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        $this->validateIdentifier($columna, 'WHERE BETWEEN');

        $this->wheres[] = "{$columna} BETWEEN ? AND ?";
        $this->values[] = $valor1;
        $this->values[] = $valor2;

        $this->boolWhereBetween = true;

        return $this;
    }

    public function whereIn(string $columna, array $valores): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if ($valores === []) {
            throw new \Error('whereIn() requiere un array con al menos un valor');
        }

        $this->validateIdentifier($columna, 'WHERE IN');

        $placeholders = implode(', ', array_fill(0, count($valores), '?'));

        $this->wheres[] = "{$columna} IN ({$placeholders})";
        foreach ($valores as $valor) {
            $this->values[] = $valor;
        }

        $this->boolWhere = true;

        return $this;
    }

    public function whereNotIn(string $columna, array $valores): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if ($valores === []) {
            throw new \Error('whereNotIn() requiere un array con al menos un valor');
        }

        $this->validateIdentifier($columna, 'WHERE NOT IN');

        $placeholders = implode(', ', array_fill(0, count($valores), '?'));

        $this->wheres[] = "{$columna} NOT IN ({$placeholders})";
        foreach ($valores as $valor) {
            $this->values[] = $valor;
        }

        $this->boolWhere = true;

        return $this;
    }

    public function whereNull(string $columna): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        $this->validateIdentifier($columna, 'WHERE NULL');

        $this->wheres[] = "{$columna} IS NULL";
        $this->boolWhere = true;

        return $this;
    }

    public function whereNotNull(string $columna): self
    {
        if ($columna === '') {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        $this->validateIdentifier($columna, 'WHERE NOT NULL');

        $this->wheres[] = "{$columna} IS NOT NULL";
        $this->boolWhere = true;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        if ($column === '') {
            throw new \Error('Debe proveer el nombre de la columna para ordenar');
        }

        $this->validateIdentifier($column, 'ORDER BY');

        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \Error('Dirección de orden inválida');
        }

        $this->orderBys[] = "{$column} {$direction}";

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit <= 0) {
            throw new \Error('El límite debe ser mayor a 0');
        }

        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new \Error('El offset no puede ser negativo');
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Carga anticipada (eager loading) de relaciones para evitar el problema N+1.
     *
     * Plano:      Publicacion::with('usuario', 'comentarios')->get()
     * Anidado:    Publicacion::with('usuario.perfil')->get()
     * Con filtro: Publicacion::with(['comentarios' => fn($q) => $q->where('activo', 1)])->get()
     *
     * El closure recibe el QueryBuilder del modelo relacionado y NO debe
     * terminarlo (sin get()/first()).
     */
    public function with(string|array ...$relations): self
    {
        foreach ($relations as $relation) {
            if (is_string($relation)) {
                $this->assertRelationName($relation);
                $this->eager[] = ['path' => $relation, 'constraint' => null];

                continue;
            }

            //array: lista simple ['usuario', 'comentarios'] o mapa con constraints
            foreach ($relation as $clave => $valor) {
                if (is_int($clave)) {
                    //lista simple: ['usuario', 'comentarios']
                    if (!is_string($valor)) {
                        throw new \Error('with() solo acepta nombres de relacion como string en listas simples');
                    }

                    $this->assertRelationName($valor);
                    $this->eager[] = ['path' => $valor, 'constraint' => null];

                    continue;
                }

                //mapa con constraint: ['comentarios' => fn($q) => ...]
                if (!is_callable($valor)) {
                    throw new \Error("El valor para la relacion {$clave} en with() debe ser un closure");
                }

                $this->assertRelationName((string) $clave);
                $this->eager[] = ['path' => (string) $clave, 'constraint' => $valor];
            }
        }

        return $this;
    }

    private function assertRelationName(string $relation): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)*$/', $relation)) {
            throw new \Error("Nombre de relacion no valido: {$relation}");
        }
    }

    /**
     * Incluye los registros con soft delete en la consulta.
     * En el DELETE de la consulta pasa a borrar fisicamente.
     */
    public function withTrashed(): self
    {
        $this->requireSoftDeletes('withTrashed()');
        $this->withTrashed = true;
        $this->onlyTrashed = false;

        return $this;
    }

    /**
     * La consulta devuelve SOLO los registros con soft delete.
     */
    public function onlyTrashed(): self
    {
        $this->requireSoftDeletes('onlyTrashed()');
        $this->onlyTrashed = true;
        $this->withTrashed = false;

        return $this;
    }

    /**
     * Recupera (eliminado_en = NULL) los registros que cumplen las
     * condiciones de la consulta. Requiere al menos un where().
     *
     * @return int cantidad de filas restauradas
     */
    public function restore(): int
    {
        $this->requireSoftDeletes('restore()');

        if (empty($this->wheres)) {
            throw new \Error('restore() requiere al menos una condicion where() para evitar restaurar toda la tabla');
        }

        $columna = $this->model->getDeletedAtColumn();
        $sql = "UPDATE {$this->model->getTable()} SET {$columna} = NULL" . $this->buildWhereSql() . " AND {$columna} IS NOT NULL";

        return Model::db()->statementC_U_D($sql, $this->values);
    }

    private function requireSoftDeletes(string $metodo): void
    {
        $this->model->validateModel();

        if (!$this->model->usesSoftDeletes()) {
            throw new \Error("El modelo {$this->modelClass} no usa el trait SoftDeletes, no puede usar {$metodo}");
        }
    }

    //******************************************************************
    // METODOS TERMINALES (ejecutan la consulta)
    //******************************************************************

    public function get(): ModelCollection|null
    {
        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $result = $this->execute($this->buildSelectSql('get'));

        if (count($result) === 0) {
            return null;
        }

        $models = array_map(fn (array $row): Model => $this->modelClass::hydrate($row), $result);

        $this->eagerLoadRelations($models);
        $this->loadRelationCounts($models);

        return new ModelCollection($models);
    }

    public function first(): Model|null
    {
        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $result = $this->execute($this->buildSelectSql('first'));

        if (count($result) === 0) {
            return null;
        }

        $model = $this->modelClass::hydrate($result[0]);

        $this->eagerLoadRelations([$model]);
        $this->loadRelationCounts([$model]);

        return $model;
    }

    public function firstNotHidden(): Model|null
    {
        return $this->first();
    }

    /**
     * Igual que first() pero lanza ModelNotFoundException si no hay resultados.
     */
    public function firstOrFail(): Model
    {
        $model = $this->first();

        if ($model === null) {
            throw new ModelNotFoundException('No hay resultados de consulta para el modelo ' . $this->modelClass);
        }

        return $model;
    }

    public function count(): int
    {
        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        return $this->runCount();
    }

    private function runCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->model->getTable();

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereSql();

        $result = $this->execute($sql);

        return (int) ((array) $result[0])['COUNT(*)'];
    }

    /**
     * Pagina los resultados de la consulta (estilo Laravel).
     *
     * Ejemplo: Publicacion::where('estado', 'publicado')->paginate(15, (int) ($_GET['page'] ?? 1))
     */
    public function paginate(int $porPagina = 15, int $pagina = 1): Paginator
    {
        if ($porPagina < 1) {
            throw new \Error('La cantidad por pagina debe ser mayor a 0');
        }

        if ($pagina < 1) {
            $pagina = 1;
        }

        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $total = $this->runCount();

        $resultado = $this->limit($porPagina)->offset(($pagina - 1) * $porPagina)->get();

        $items = $resultado === null ? [] : iterator_to_array($resultado);

        return new Paginator($items, $total, $porPagina, $pagina);
    }

    public function max(?string $column = null): int|float|string
    {
        return $this->aggregate('max', 'maximo', $column);
    }

    public function min(?string $column = null): int|float|string
    {
        return $this->aggregate('min', 'minimo', $column);
    }

    public function sum(?string $column = null): int|float|string
    {
        return $this->aggregate('sum', 'suma', $column);
    }

    public function avg(?string $column = null): int|float|string
    {
        return $this->aggregate('avg', 'promedio', $column);
    }

    /**
     * Devuelve el valor de una columna del primer registro de la consulta.
     * Ejemplo: Usuario::where('correo', $correo)->value('id')
     */
    public function value(string $column): mixed
    {
        $this->validateIdentifier($column, 'VALUE');

        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $sql = "SELECT {$column} FROM {$this->model->getTable()}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereSql() . ' LIMIT 1';

        $result = $this->execute($sql);

        if (count($result) === 0) {
            return null;
        }

        return ((array) $result[0])[$column] ?? null;
    }

    /**
     * Indica si la consulta tiene al menos un resultado.
     */
    public function exists(): bool
    {
        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $sql = 'SELECT 1 AS cronos_exists FROM ' . $this->model->getTable();

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereSql() . ' LIMIT 1';

        return count($this->execute($sql)) > 0;
    }

    /**
     * Indica si la consulta no tiene ningun resultado.
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Atajo de orderBy($column, 'DESC'). Por defecto created_at.
     */
    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Atajo de orderBy($column, 'ASC'). Por defecto created_at.
     */
    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    //******************************************************************
    // EXISTENCIA DE RELACIONES (has / whereHas) Y CONTEOS (withCount)
    //******************************************************************

    /**
     * Filtra los registros que tienen al menos $cantidad relaciones.
     *
     * Ejemplo: Publicacion::has('comentarios')->get()
     */
    public function has(string $relation, string $operador = '>=', int $cantidad = 1): self
    {
        return $this->whereHas($relation, null, $operador, $cantidad);
    }

    /**
     * Filtra los registros cuya relacion cumple las condiciones del closure.
     *
     * Ejemplo: Publicacion::whereHas('comentarios', fn ($q) => $q->where('activo', 1))->get()
     *
     * El closure recibe el QueryBuilder del modelo relacionado y NO debe
     * terminarlo; solo condiciones (where/whereNull/...). El filtro de soft
     * deletes del modelo relacionado se aplica automaticamente.
     */
    public function whereHas(string $relation, ?callable $callback = null, string $operador = '>=', int $cantidad = 1): self
    {
        if (!method_exists($this->model, $relation)) {
            throw new \Error("La relacion {$relation} no existe en el modelo {$this->modelClass}");
        }

        $operadorValido = strtoupper(trim($operador));
        if (!in_array($operadorValido, ['=', '!=', '<>', '>', '>=', '<', '<='], true)) {
            throw new \Error("Operador no permitido para has()/whereHas(): {$operador}");
        }

        $this->model->validateModel();

        $relationObj = $this->model->{$relation}();
        $parentTable = $this->model->getTable();

        if ($relationObj instanceof HasOne || $relationObj instanceof HasMany) {
            $related = $relationObj->getRelated();
            $relatedTable = $related->getTable();
            $inner = $this->innerWhereFragment($related, $callback);

            $condicion = "{$relatedTable}.{$relationObj->getForeignKey()} = {$parentTable}.{$relationObj->getLocalKey()}"
                . $this->suffixConInner($inner);

            $this->pushHasCondition($relatedTable, $condicion, $operadorValido, $cantidad, $inner['values']);
        } elseif ($relationObj instanceof BelongsTo) {
            $related = $relationObj->getRelated();
            $relatedTable = $related->getTable();
            $inner = $this->innerWhereFragment($related, $callback);

            $condicion = "{$relatedTable}.{$relationObj->getOwnerKey()} = {$parentTable}.{$relationObj->getForeignKey()}"
                . $this->suffixConInner($inner);

            $this->pushHasCondition($relatedTable, $condicion, $operadorValido, $cantidad, $inner['values']);
        } elseif ($relationObj instanceof BelongsToMany) {
            $related = $relationObj->getRelated();
            $pivot = $relationObj->getPivotTable();
            $from = "{$pivot} JOIN {$related->getTable()} ON {$related->getTable()}.{$related->getPrimaryKey()} = {$pivot}.{$relationObj->getRelatedPivotKey()}";
            $inner = $this->innerWhereFragment($related, $callback);

            $condicion = "{$pivot}.{$relationObj->getForeignPivotKey()} = {$parentTable}.{$this->model->getPrimaryKey()}"
                . $this->suffixConInner($inner);

            $this->pushHasCondition($from, $condicion, $operadorValido, $cantidad, $inner['values']);
        } else {
            throw new \Error("La relacion {$relation} no es soportada por has()/whereHas()");
        }

        $this->boolWhere = true;

        return $this;
    }

    private function suffixConInner(array $inner): string
    {
        return $inner['sql'] !== '' ? " AND ({$inner['sql']})" : '';
    }

    /**
     * Agrega la condicion EXISTS o COUNT de has()/whereHas().
     *
     * @param array<int, int|float|string> $innerValues
     */
    private function pushHasCondition(string $from, string $condicion, string $operador, int $cantidad, array $innerValues): void
    {
        $this->values = array_merge($this->values, $innerValues);

        if ($operador === '>=' && $cantidad === 1) {
            $this->wheres[] = "EXISTS (SELECT 1 FROM {$from} WHERE {$condicion})";

            return;
        }

        $this->values[] = $cantidad;
        $this->wheres[] = "(SELECT COUNT(*) FROM {$from} WHERE {$condicion}) {$operador} ?";
    }

    /**
     * Compila las condiciones de un builder de relacion (constraint + soft
     * deletes) a fragmento SQL y valores, para incrustarlo en subconsultas.
     *
     * @return array{sql: string, values: array}
     */
    private function innerWhereFragment(Model $related, ?callable $callback): array
    {
        $inner = $related->newQuery();
        $inner->applySoftDeleteFilter();

        if ($callback !== null) {
            $callback($inner);
        }

        return $inner->toWhereFragment();
    }

    /**
     * Compila las condiciones actuales a fragmento SQL (sin el WHERE inicial)
     * y sus valores. Uso interno de has()/whereHas()/withCount().
     *
     * @return array{sql: string, values: array}
     */
    public function toWhereFragment(): array
    {
        $sql = $this->buildWhereSql();

        return [
            'sql' => $sql === '' ? '' : substr($sql, 7),
            'values' => $this->values,
        ];
    }

    /**
     * Agrega a cada modelo el atributo {relacion}_count con la cantidad de
     * registros relacionados (en 1 consulta por relacion, sin N+1).
     *
     * Ejemplos:
     *   Publicacion::withCount('comentarios')->get()
     *   Usuario::withCount(['publicaciones' => fn ($q) => $q->where('estado', 'publicado')])->get()
     */
    public function withCount(string|array ...$relations): self
    {
        foreach ($relations as $relation) {
            if (is_string($relation)) {
                $this->assertRelationName($relation);
                $this->counts[$relation] = null;

                continue;
            }

            foreach ($relation as $clave => $valor) {
                if (is_int($clave)) {
                    if (!is_string($valor)) {
                        throw new \Error('withCount() solo acepta nombres de relacion como string en listas simples');
                    }

                    $this->assertRelationName($valor);
                    $this->counts[$valor] = null;

                    continue;
                }

                if (!is_callable($valor)) {
                    throw new \Error("El valor para la relacion {$clave} en withCount() debe ser un closure");
                }

                $this->assertRelationName((string) $clave);
                $this->counts[(string) $clave] = $valor;
            }
        }

        return $this;
    }

    /**
     * Ejecuta los conteos de withCount() sobre los modelos ya hidratados.
     *
     * @param Model[] $models
     */
    private function loadRelationCounts(array $models): void
    {
        if (empty($this->counts) || $models === []) {
            return;
        }

        foreach ($this->counts as $name => $constraint) {
            $this->loadSingleRelationCount($models, $name, $constraint);
        }
    }

    /**
     * @param Model[] $models
     */
    private function loadSingleRelationCount(array $models, string $name, ?callable $constraint): void
    {
        if (!method_exists($models[0], $name)) {
            throw new \Error("La relacion {$name} no existe en el modelo " . get_class($models[0]));
        }

        $this->model->validateModel();

        $relationObj = $models[0]->{$name}();
        $parentTable = $this->model->getTable();
        $aliasKey = '__cronos_key';
        $aliasTotal = '__cronos_total';

        if ($relationObj instanceof HasOne || $relationObj instanceof HasMany) {
            $related = $relationObj->getRelated();
            $relatedTable = $related->getTable();
            $fk = $relationObj->getForeignKey();
            $localKey = $relationObj->getLocalKey();

            $keys = $this->keysDeModelos($models, $localKey);
            $inner = $this->innerWhereFragment($related, $constraint);

            $sql = "SELECT {$relatedTable}.{$fk} AS {$aliasKey}, COUNT(*) AS {$aliasTotal} FROM {$relatedTable}"
                . $this->suffixCountWhere($keys, $inner, "{$relatedTable}.{$fk}");

            $mapa = $this->ejecutarCountYmapear($sql, array_values($keys), $inner['values'], $aliasKey, $aliasTotal);

            foreach ($models as $model) {
                $model->setRelation("{$name}_count", $mapa[(string) $model->{$localKey}] ?? 0);
            }

            return;
        }

        if ($relationObj instanceof BelongsTo) {
            $related = $relationObj->getRelated();
            $relatedTable = $related->getTable();
            $ownerKey = $relationObj->getOwnerKey();
            $fk = $relationObj->getForeignKey();

            $keys = $this->keysDeModelos($models, $fk);
            $inner = $this->innerWhereFragment($related, $constraint);

            $sql = "SELECT {$relatedTable}.{$ownerKey} AS {$aliasKey}, COUNT(*) AS {$aliasTotal} FROM {$relatedTable}"
                . $this->suffixCountWhere($keys, $inner, "{$relatedTable}.{$ownerKey}");

            $mapa = $this->ejecutarCountYmapear($sql, array_values($keys), $inner['values'], $aliasKey, $aliasTotal);

            foreach ($models as $model) {
                $model->setRelation("{$name}_count", $mapa[(string) $model->{$fk}] ?? 0);
            }

            return;
        }

        if ($relationObj instanceof BelongsToMany) {
            $related = $relationObj->getRelated();
            $pivot = $relationObj->getPivotTable();
            $fpk = $relationObj->getForeignPivotKey();
            $from = "{$pivot} JOIN {$related->getTable()} ON {$related->getTable()}.{$related->getPrimaryKey()} = {$pivot}.{$relationObj->getRelatedPivotKey()}";

            $keys = $this->keysDeModelos($models, $this->model->getPrimaryKey());
            $inner = $this->innerWhereFragment($related, $constraint);

            $sql = "SELECT {$pivot}.{$fpk} AS {$aliasKey}, COUNT(*) AS {$aliasTotal} FROM {$from}"
                . $this->suffixCountWhere($keys, $inner, "{$pivot}.{$fpk}");

            $mapa = $this->ejecutarCountYmapear($sql, array_values($keys), $inner['values'], $aliasKey, $aliasTotal);

            foreach ($models as $model) {
                $model->setRelation("{$name}_count", $mapa[(string) $model->{$this->model->getPrimaryKey()}] ?? 0);
            }

            return;
        }

        throw new \Error("La relacion {$name} no es soportada por withCount()");
    }

    /**
     * Clave unica por modelo para las consultas de conteo.
     *
     * @param Model[] $models
     * @return array<string, int|float|string>
     */
    private function keysDeModelos(array $models, string $columna): array
    {
        $keys = [];

        foreach ($models as $model) {
            $key = $model->{$columna};
            if ($key !== null) {
                $keys[(string) $key] = $key;
            }
        }

        return $keys;
    }

    /**
     * Clausula WHERE para las consultas de conteo: IN de claves + inner.
     *
     * @param array<string, int|float|string> $keys
     * @param array{sql: string, values: array} $inner
     */
    private function suffixCountWhere(array $keys, array $inner, string $columnaKey): string
    {
        if ($keys === []) {
            return ' WHERE 1 = 0';
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        return " WHERE {$columnaKey} IN ({$placeholders})"
            . $this->suffixConInner($inner)
            . " GROUP BY {$columnaKey}";
    }
    /**
     * Ejecuta la consulta de conteo y devuelve mapa clave => total.
     *
     * @param array<int, int|float|string> $keyValues
     * @return array<string, int>
     */
    private function ejecutarCountYmapear(string $sql, array $keyValues, array $innerValues, string $aliasKey, string $aliasTotal): array
    {
        $mapa = [];

        if ($keyValues === []) {
            return $mapa;
        }

        $filas = (array) Model::db()->statement($sql, array_merge($keyValues, $innerValues));

        foreach ($filas as $fila) {
            $fila = (array) $fila;
            $mapa[(string) $fila[$aliasKey]] = (int) $fila[$aliasTotal];
        }

        return $mapa;
    }

    /**
     * Actualiza los registros que cumplen las condiciones de la consulta.
     *
     * Ejemplo: Publicacion::where('estado', 'borrador')->update(['estado' => 'archivado'])
     *
     * @return int cantidad de filas afectadas
     */
    public function update(array|object $data): int
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (empty($this->wheres)) {
            throw new \Error('update() requiere al menos una condicion where() para evitar actualizar toda la tabla');
        }

        $this->model->validateModel();
        $this->model->validateColumns($data);

        if ($this->model->hasTimestamps()) {
            $data[$this->model->getUpdatedAtColumn()] = date('Y-m-d H:i:s');
        }

        $sets = [];
        $values = [];
        foreach ($data as $columna => $valor) {
            $this->validateIdentifier((string) $columna, 'UPDATE');
            $sets[] = "{$columna} = ?";
            $values[] = $valor;
        }

        $sql = "UPDATE {$this->model->getTable()} SET " . implode(', ', $sets) . $this->buildWhereSql();

        return Model::db()->statementC_U_D($sql, array_merge($values, $this->values));
    }

    /**
     * Elimina los registros que cumplen las condiciones de la consulta.
     * Si el modelo usa SoftDeletes, marca eliminado_en en lugar de borrar,
     * salvo que la consulta tenga withTrashed() (borrado fisico).
     *
     * Ejemplo: Publicacion::where('estado', 'borrador')->delete()
     *
     * @return int cantidad de filas afectadas
     */
    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new \Error('delete() requiere al menos una condicion where() para evitar borrar toda la tabla');
        }

        $this->model->validateModel();

        $table = $this->model->getTable();

        $borradoFisico = !$this->model->usesSoftDeletes() || $this->withTrashed;

        if (!$borradoFisico) {
            $columna = $this->model->getDeletedAtColumn();
            $marcaViva = $this->onlyTrashed ? 'IS NOT NULL' : 'IS NULL';
            $sql = "UPDATE {$table} SET {$columna} = ?" . $this->buildWhereSql() . " AND {$columna} {$marcaViva}";

            return Model::db()->statementC_U_D($sql, array_merge([date('Y-m-d H:i:s')], $this->values));
        }

        $sql = "DELETE FROM {$table}" . $this->buildWhereSql();

        return Model::db()->statementC_U_D($sql, $this->values);
    }

    /**
     * Retorna la consulta sin ejecutarla (para depuracion).
     */
    public function dd(): array
    {
        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $sql = $this->buildSelectSql('get');
        $bindings = $this->values;

        $debugSql = $sql;
        foreach ($bindings as $value) {
            $value = is_string($value)
                ? "'" . addslashes($value) . "'"
                : (is_null($value) ? 'NULL' : $value);
            $debugSql = preg_replace('/\?/', (string) $value, $debugSql, 1);
        }

        return [
            'sql_raw' => $sql,
            'sql_debug' => $debugSql,
            'bindings' => $bindings,
            'model' => $this->modelClass,
        ];
    }

    //******************************************************************
    // CONSTRUCCION DE SQL
    //******************************************************************

    private function buildSelectSql(string $type): string
    {
        $sql = match ($type) {
            'max' => 'SELECT MAX(' . $this->selects . ')',
            'min' => 'SELECT MIN(' . $this->selects . ')',
            'avg' => 'SELECT AVG(' . $this->selects . ')',
            'sum' => 'SELECT SUM(' . $this->selects . ')',
            default => 'SELECT ' . $this->selects,
        };

        $sql .= ' FROM ' . $this->model->getTable();

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereSql();

        if (!empty($this->orderBys)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBys);
        }

        if ($type === 'get') {
            if ($this->limit !== null) {
                $sql .= ' LIMIT ' . $this->limit;
            }
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        } elseif ($type === 'first') {
            $sql .= ' LIMIT 1';
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return $sql;
    }

    private function buildWhereSql(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        if ($this->boolWhere && $this->boolWhereBetween) {
            throw new \Error('el metodo where() no puede estar con el metodo whereBetween()');
        }

        if ($this->boolWhere && $this->boolWhereConcat) {
            throw new \Error('el metodo where() no puede estar con el metodo whereConcat()');
        }

        if ($this->boolWhereConcat && $this->boolWhereBetween) {
            throw new \Error('el metodo whereConcat() no puede estar con el metodo whereBetween()');
        }

        if ($this->boolWhere) {
            $sql = ' WHERE ' . implode(' AND ', $this->wheres);
        } elseif ($this->boolWhereBetween) {
            $sql = ' WHERE ' . implode(' ', $this->wheres);
        } else {
            $sql = ' WHERE ' . implode(' ', $this->wheres);
        }

        if (!empty($this->andOrWheres)) {
            $sql .= ' ' . implode(' ', $this->andOrWheres);
        }

        return $sql;
    }

    private function execute(string $sql): array
    {
        return (array) Model::db()->statement($sql, $this->values);
    }

    private function aggregate(string $type, string $nombre, ?string $column = null): int|float|string
    {
        if ($column !== null) {
            //estilo Laravel: Usuario::sum('id')
            $this->validateIdentifier($column, 'AGGREGATE');
            $expresion = $column;
        } else {
            //estilo legacy: Usuario::select('id')->sum()
            if ($this->selects === '*') {
                throw new \Error("no agrego ninguna columna para obtener el valor {$nombre} Model::select('columna')->{$type}()");
            }

            $selectsArray = explode(', ', $this->selects);
            if (count($selectsArray) !== 1) {
                throw new \Error("solo se puede obtener el valor {$nombre} de una columna Model::select('columna')->{$type}()");
            }

            $expresion = $this->selects;
        }

        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $sql = 'SELECT ' . strtoupper($type) . "({$expresion}) FROM {$this->model->getTable()}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereSql();

        $result = $this->execute($sql);

        if (count($result) === 0) {
            return 0;
        }

        $row = (array) $result[0];

        return $row[strtoupper($type) . "({$expresion})"] ?? 0;
    }

    /**
     * Aplica el filtro de soft deletes a las condiciones actuales.
     * Publica para uso interno de has()/withCount() (subconsultas).
     */
    public function applySoftDeleteFilter(): void
    {
        if (!$this->model->usesSoftDeletes()) {
            return;
        }

        $columna = $this->model->getDeletedAtColumn();

        if ($this->onlyTrashed) {
            $this->wheres[] = "{$columna} IS NOT NULL";
            $this->boolWhere = true;

            return;
        }

        if ($this->withTrashed) {
            return;
        }

        $this->wheres[] = "{$columna} IS NULL";
        $this->boolWhere = true;
    }

    //******************************************************************
    // EAGER LOADING (anidado y con constraints)
    //******************************************************************

    /**
     * @param Model[] $models
     */
    private function eagerLoadRelations(array $models): void
    {
        if (empty($this->eager) || $models === []) {
            return;
        }

        $this->loadTree($models, $this->buildRelationTree());
    }

    /**
     * Convierte las rutas de with() en un arbol.
     * Forma: nombre => [?Closure constraint, array hijos]
     *
     * @return array<string, array{0: callable|null, 1: array}>
     */
    private function buildRelationTree(): array
    {
        $tree = [];

        foreach ($this->eager as $entry) {
            $tree = $this->addToTree($tree, explode('.', $entry['path']), $entry['constraint']);
        }

        return $tree;
    }

    /**
     * Inserta una ruta de relacion en el arbol (recursivo, sin referencias).
     *
     * @param array<string, array{0: callable|null, 1: array}> $tree
     * @param string[] $segments
     * @return array<string, array{0: callable|null, 1: array}>
     */
    private function addToTree(array $tree, array $segments, ?callable $constraint): array
    {
        $segment = array_shift($segments);
        $nodo = $tree[$segment] ?? [null, []];

        if ($segments === []) {
            if ($constraint !== null) {
                $nodo[0] = $constraint;
            }
        } else {
            $nodo[1] = $this->addToTree($nodo[1], $segments, $constraint);
        }

        $tree[$segment] = $nodo;

        return $tree;
    }

    /**
     * Carga cada nivel del arbol de relaciones sobre los modelos.
     *
     * @param Model[] $models
     * @param array<string, array{0: callable|null, 1: array}> $tree
     */
    private function loadTree(array $models, array $tree): void
    {
        foreach ($tree as $name => [$constraint, $hijos]) {
            if (!method_exists($models[0], $name)) {
                throw new \Error("La relacion {$name} no existe en el modelo " . get_class($models[0]));
            }

            $relation = $models[0]->{$name}();

            if ($relation instanceof HasOne || $relation instanceof HasMany) {
                $this->eagerLoadHasChildren($models, $name, $relation, $relation instanceof HasMany, $constraint);
            } elseif ($relation instanceof BelongsTo) {
                $this->eagerLoadBelongsTo($models, $name, $relation, $constraint);
            } elseif ($relation instanceof BelongsToMany) {
                if ($constraint !== null) {
                    throw new \Error("with() con closures no esta soportado para la relacion belongsToMany {$name}");
                }

                $this->eagerLoadBelongsToMany($models, $name, $relation);
            } else {
                throw new \Error("La relacion {$name} no es soportada por with()");
            }

            if ($hijos !== []) {
                $relacionados = $this->collectRelatedModels($models, $name);

                if ($relacionados !== []) {
                    $this->loadTree($relacionados, $hijos);
                }
            }
        }
    }

    /**
     * Reune los modelos relacionados ya cargados (para el siguiente nivel anidado).
     *
     * @param Model[] $models
     * @return Model[]
     */
    private function collectRelatedModels(array $models, string $name): array
    {
        $relacionados = [];

        foreach ($models as $model) {
            $valor = $model->getRelation($name);

            if ($valor instanceof Model) {
                $relacionados[] = $valor;
            } elseif ($valor instanceof ModelCollection) {
                foreach ($valor as $item) {
                    if ($item instanceof Model) {
                        $relacionados[] = $item;
                    }
                }
            }
        }

        return $relacionados;
    }

    /**
     * @param Model[] $models
     */
    private function eagerLoadHasChildren(array $models, string $name, HasOne|HasMany $relation, bool $many, ?callable $constraint = null): void
    {
        $relatedClass = get_class($relation->getRelated());
        $foreignKey = $relation->getForeignKey();
        $localKey = $relation->getLocalKey();

        $keys = [];
        foreach ($models as $model) {
            $key = $model->{$localKey};
            if ($key !== null) {
                $keys[(string) $key] = $key;
            }
        }

        $grouped = [];
        if ($keys !== []) {
            $query = $relatedClass::whereIn($foreignKey, array_values($keys));

            if ($constraint !== null) {
                $constraint($query);
            }

            $results = $query->get();

            foreach ($results ?? [] as $row) {
                $grouped[(string) $row->{$foreignKey}][] = $row;
            }
        }

        foreach ($models as $model) {
            $list = $grouped[(string) $model->{$localKey}] ?? [];
            $model->setRelation($name, $many ? new ModelCollection($list) : ($list[0] ?? null));
        }
    }

    /**
     * @param Model[] $models
     */
    private function eagerLoadBelongsTo(array $models, string $name, BelongsTo $relation, ?callable $constraint = null): void
    {
        $relatedClass = get_class($relation->getRelated());
        $foreignKey = $relation->getForeignKey();
        $ownerKey = $relation->getOwnerKey();

        $keys = [];
        foreach ($models as $model) {
            $key = $model->{$foreignKey};
            if ($key !== null) {
                $keys[(string) $key] = $key;
            }
        }

        $map = [];
        if ($keys !== []) {
            $query = $relatedClass::whereIn($ownerKey, array_values($keys));

            if ($constraint !== null) {
                $constraint($query);
            }

            $results = $query->get();

            foreach ($results ?? [] as $row) {
                $map[(string) $row->{$ownerKey}] = $row;
            }
        }

        foreach ($models as $model) {
            $model->setRelation($name, $map[(string) $model->{$foreignKey}] ?? null);
        }
    }

    /**
     * @param Model[] $models
     */
    private function eagerLoadBelongsToMany(array $models, string $name, BelongsToMany $relation): void
    {
        $related = $relation->getRelated();
        $relatedClass = get_class($related);
        $table = $related->getTable();
        $primaryKey = $related->getPrimaryKey();
        $pivot = $relation->getPivotTable();
        $foreignPivotKey = $relation->getForeignPivotKey();
        $relatedPivotKey = $relation->getRelatedPivotKey();

        $keys = [];
        foreach ($models as $model) {
            $key = $model->{$model->getPrimaryKey()};
            if ($key !== null) {
                $keys[(string) $key] = $key;
            }
        }

        $grouped = [];
        if ($keys !== []) {
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));
            $sql = "SELECT {$table}.*, {$pivot}.{$foreignPivotKey} AS __cronos_pivot"
                . " FROM {$table}"
                . " JOIN {$pivot} ON {$table}.{$primaryKey} = {$pivot}.{$relatedPivotKey}"
                . " WHERE {$pivot}.{$foreignPivotKey} IN ({$placeholders})";

            $rows = (array) $relatedClass::customQuery($sql, array_values($keys));

            foreach ($rows as $row) {
                $row = (array) $row;
                $pivotKey = $row['__cronos_pivot'];
                unset($row['__cronos_pivot']);
                $grouped[(string) $pivotKey][] = $relatedClass::hydrate($row);
            }
        }

        foreach ($models as $model) {
            $list = $grouped[(string) $model->{$model->getPrimaryKey()}] ?? [];
            $model->setRelation($name, new ModelCollection($list));
        }
    }

    //******************************************************************
    // VALIDACION DE IDENTIFICADORES (seguridad)
    //******************************************************************

    private function validateIdentifier(string $value, string $context): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $value)) {
            throw new \Error("Identificador no valido para {$context}: {$value}");
        }

        return $value;
    }

    private function validateOperator(string $operator): string
    {
        $upper = strtoupper(trim($operator));

        if (!in_array($upper, self::OPERATORS, true)) {
            throw new \Error(
                'Operador no permitido: ' . $operator . '. Permitidos: ' . implode(', ', self::OPERATORS)
            );
        }

        return $upper;
    }

    /**
     * Permite invocar local scopes definidos en el modelo como scope{Nombre}($query, ...$args).
     */
    public function __call(string $method, array $arguments): mixed
    {
        $scopeMethod = 'scope' . ucfirst($method);

        if (method_exists($this->model, $scopeMethod)) {
            $result = $this->model->{$scopeMethod}($this, ...$arguments);

            return $result ?? $this;
        }

        throw new \BadMethodCallException("El metodo [{$method}] no existe en el query builder ni en el modelo {$this->modelClass}.");
    }
}
