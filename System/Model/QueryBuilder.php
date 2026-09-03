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

    /** @var string[] nombres de relaciones a cargar con with() */
    private array $eager = [];

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
     * Ejemplo: Publicacion::with('usuario', 'comentarios')->get()
     */
    public function with(string ...$relations): self
    {
        foreach ($relations as $relation) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $relation)) {
                throw new \Error("Nombre de relacion no valido: {$relation}");
            }

            if (!method_exists($this->model, $relation)) {
                throw new \Error("La relacion {$relation} no existe en el modelo {$this->modelClass}");
            }

            $this->eager[] = $relation;
        }

        return $this;
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

        return $model;
    }

    public function firstNotHidden(): Model|null
    {
        return $this->first();
    }

    public function count(): int
    {
        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $sql = 'SELECT COUNT(*) FROM ' . $this->model->getTable();

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhereSql();

        $result = $this->execute($sql);

        return (int) ((array) $result[0])['COUNT(*)'];
    }

    public function max(): int|float|string
    {
        return $this->aggregate('max', 'maximo');
    }

    public function min(): int|float|string
    {
        return $this->aggregate('min', 'minimo');
    }

    public function sum(): int|float|string
    {
        return $this->aggregate('sum', 'suma');
    }

    public function avg(): int|float|string
    {
        return $this->aggregate('avg', 'promedio');
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
     * Si el modelo usa SoftDeletes, marca eliminado_en en lugar de borrar.
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

        if ($this->model->usesSoftDeletes()) {
            $columna = $this->model->getDeletedAtColumn();
            $sql = "UPDATE {$table} SET {$columna} = ?" . $this->buildWhereSql() . " AND {$columna} IS NULL";

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

    private function aggregate(string $type, string $nombre): int|float|string
    {
        if ($this->selects === '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor {$nombre} Model::select('columna')->{$type}()");
        }

        $selectsArray = explode(', ', $this->selects);
        if (count($selectsArray) !== 1) {
            throw new \Error("solo se puede obtener el valor {$nombre} de una columna Model::select('columna')->{$type}()");
        }

        $this->model->validateModel();
        $this->applySoftDeleteFilter();

        $result = $this->execute($this->buildSelectSql($type));
        $row = (array) $result[0];

        return $row[strtoupper($type) . "({$this->selects})"];
    }

    private function applySoftDeleteFilter(): void
    {
        if (!$this->model->usesSoftDeletes()) {
            return;
        }

        $this->wheres[] = $this->model->getDeletedAtColumn() . ' IS NULL';
        $this->boolWhere = true;
    }

    //******************************************************************
    // EAGER LOADING
    //******************************************************************

    /**
     * @param Model[] $models
     */
    private function eagerLoadRelations(array $models): void
    {
        if (empty($this->eager) || $models === []) {
            return;
        }

        foreach ($this->eager as $name) {
            $relation = $models[0]->{$name}();

            if ($relation instanceof HasOne || $relation instanceof HasMany) {
                $this->eagerLoadHasChildren($models, $name, $relation, $relation instanceof HasMany);
            } elseif ($relation instanceof BelongsTo) {
                $this->eagerLoadBelongsTo($models, $name, $relation);
            } elseif ($relation instanceof BelongsToMany) {
                $this->eagerLoadBelongsToMany($models, $name, $relation);
            } else {
                throw new \Error("La relacion {$name} no es soportada por with()");
            }
        }
    }

    /**
     * @param Model[] $models
     */
    private function eagerLoadHasChildren(array $models, string $name, HasOne|HasMany $relation, bool $many): void
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
            $results = $relatedClass::whereIn($foreignKey, array_values($keys))->get();

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
    private function eagerLoadBelongsTo(array $models, string $name, BelongsTo $relation): void
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
            $results = $relatedClass::whereIn($ownerKey, array_values($keys))->get();

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
}
