<?php

namespace Cronos\Model;

use Cronos\Database\DatabaseDriver;

abstract class Model
{
    //DATOS BASICOS DEL MODELO DE LA TABLA
    protected string $table = '';
    protected string $primaryKey = '';
    protected array $fillable = [];
    protected array $hidden = [];
    protected bool $timestamps = false;
    protected string $created = 'created_at';
    protected string $updated = 'updated_at';

    //GUARDAR LOS ATRIBUTOS PARA CREATE, UPDATE
    protected array $attributes = [];


    //DATOS PARA LA QUERY
    protected static string $selects = '*';
    protected static  array $joins = [];

    protected static  array $wheres = [];
    protected static  array $andOrWheres = [];
    protected static  bool $boolWhere = false;
    protected static  bool $boolWhereBetween = false;
    protected static  bool $boolWhereConcat = false;

    protected static  array $orderBys = [];
    protected static  ?int $limit = null;
    protected static  array $values = [];

    private static ?DatabaseDriver $db = null;

    public static function setDB(DatabaseDriver $db): void
    {
        self::$db = $db;
    }

    public function __get($property)
    {
        // Verifica si la propiedad existe en el arreglo de atributos
        if (array_key_exists($property, $this->attributes)) {
            return $this->attributes[$property];
        }
        return null; // Devuelve null si la propiedad no existe
    }

    public function __set($name, $value)
    {
        // Verifica si la propiedad existe en el arreglo de atributos
        $this->attributes[$name] = $value;
    }

    protected function setAttributes(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    private function validateModel(): void
    {
        //verificar que $table no esté vacía
        if (empty($this->table)) {
            throw new \Error('La propiedad $table no puede estar vacia en el modelo ' . static::class);
        }

        //verificar que $primaryKey no esté vacía
        if (empty($this->primaryKey)) {
            throw new \Error('La propiedad $primaryKey no puede estar vacia en el modelo ' . static::class);
        }

        //verificar que $fillable no esté vacía
        if (empty($this->fillable)) {
            throw new \Error('La propiedad $fillable no puede estar vacia en el modelo ' . static::class);
        }
    }

    //verificar que $attributes todos esten dentro de $fillable
    private function validateAttributes(): void
    {
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->fillable)) {
                throw new \Error('El atributo ' . $key . ' no está permitido en el modelo ' . static::class);
            }
        }
    }

    //verificar que $fillable todos esten dentro de $attributes
    private function validateFillableOnAttibutes(): void
    {
        foreach ($this->fillable as $key => $value) {
            if (!array_key_exists($value, $this->attributes)) {
                throw new \Error('El atributo ' . $value . ' es requerido en el modelo ' . static::class);
            }
        }
    }


    private function addTimestamps($type = 'created'): void
    {
        if ($this->timestamps) {
            if ($type == 'created') {
                $this->attributes[$this->created] = date('Y-m-d H:i:s');
                $this->attributes[$this->updated] = date('Y-m-d H:i:s');
            } else {
                $this->attributes[$this->updated] = date('Y-m-d H:i:s');
            }
        }
    }

    // eliminar los atributos ocultos
    private function hiddenAttibutes(): void
    {
        foreach ($this->hidden as $value) {
            unset($this->attributes[$value]);
        }
    }

    public function toArray(): array
    {
        $this->hiddenAttibutes();
        return $this->attributes;
    }

    public function toObject(): object
    {
        $this->hiddenAttibutes();
        return (object)$this->attributes;
    }

    public static function create(array|object $data): self|null
    {
        if (!is_object($data) && !is_array($data)) {
            throw new \Error('los datos debe ser un array u objeto');
        }

        if (is_object($data)) {
            $data = (array) $data;
        }

        $model = new static();

        // Asignar los datos al arreglo $attributes
        $model->setAttributes($data);

        //realizar validaciones
        $model->validateModel();
        $model->validateAttributes();
        $model->validateFillableOnAttibutes();

        //agregar registros de tiempo
        $model->addTimestamps();

        // Crear la sentencia SQL y los parámetros según los datos en $model->attributes
        $sql = "INSERT INTO {$model->table} (" . implode(',', array_keys($model->attributes)) . ") VALUES (" . implode(',', array_fill(0, count($model->attributes), '?')) . ")";
        $param = array_values($model->attributes);

        $responseInt = self::$db->statementC_U_D($sql, $param);
        if ($responseInt > 0) {
            //agregar el id del registro creado al arreglo $attributes
            $model->attributes[$model->primaryKey] = self::$db->lastInsertId();
            return $model;
        }

        return null;
    }

    public static function update(int|string $id, array|object $data): object|null
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        //instancia de la clase hija
        $model = new static();

        $model->setAttributes($data);

        //realizar validaciones
        $model->validateModel();
        $model->validateAttributes();

        //agregar registros de tiempo
        $model->addTimestamps('updated');

        // Construir la sentencia SQL de actualización
        $sql = "UPDATE {$model->table} SET ";
        $updateFields = [];
        $param = [];

        foreach ($model->attributes as $key => $value) {
            $updateFields[] = "$key = ?";
            $param[] = $value;
        }

        $sql .= implode(', ', $updateFields);
        $sql .= " WHERE {$model->primaryKey} = ?";
        $param[] = $id;

        $rows = self::$db->statementC_U_D($sql, $param);

        if ($rows > 0) {
            $model->attributes[$model->primaryKey] = $id;
            return $model;
        } else {
            return null;
        }
    }

    public static function delete(int|string $id): bool
    {
        $model = new static();
        $sql = "DELETE FROM {$model->table} WHERE {$model->primaryKey} = ?";
        $param = [$id];
        $rows = self::$db->statementC_U_D($sql, $param);
        return $rows > 0;
    }

    public static function select(string ...$select): self
    {
        //unir los elementos del array $select con una coma
        $select = implode(", ", $select);
        self::$selects = $select;

        return new static;
    }

    public static function join(string $table, string $first, string $operator, string $second): self
    {
        // Validaciones
        if (empty($table)) {
            throw new \Error('Debe proveer el nombre de la tabla para el join');
        }

        if (empty($first) || empty($operator) || empty($second)) {
            throw new \Error('Debe proveer las condiciones para el join');
        }

        // Sanitizar nombre de tabla y columnas
        $table =

            // Armar join
            $join = "JOIN $table ON $first $operator $second";

        // Guardarlo
        self::$joins[] = $join;

        // Retornar instancia
        return new static;
    }

    public static function where(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        // Validaciones
        if (empty($columna)) {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if (empty($operadorOvalor)) {
            throw new \Error('Debe proveer el segundo parametro para la condición WHERE');
        }

        self::$boolWhere = true;

        if (is_null($valor)) {
            self::$wheres[] = "$columna = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$wheres[] = "$columna $operadorOvalor ?";
            self::$values[] = $valor;
        }

        return new static;
    }

    public static function orderBy(string $column, string $direction = 'ASC'): self
    {
        // Validar columna
        if (empty($column)) {
            throw new \Error('Debe proveer el nombre de la columna para ordenar');
        }

        // Validar direction
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \Error('Dirección de orden inválida');
        }

        // Armar cláusula order by
        $orderBy = "$column $direction";

        // Guardar
        self::$orderBys[] = $orderBy;

        // Retornar instancia
        return new static;
    }

    public static function limit(int $limit): self
    {
        // dd($limit);
        //comprobar si el limite es un numero entero
        if (!is_int($limit)) {
            throw new \Error("El limite debe ser un numero entero");
        }

        // Validar límite
        if ($limit <= 0) {
            throw new \Error('El límite debe ser mayor a 0');
        }

        // Guardar límite
        self::$limit = $limit;

        // Retornar instancia
        return new static;
    }


    public static function andWhere(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        if (empty($columna)) {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if (empty($operadorOvalor)) {
            throw new \Error('Debe proveer el segundo parámetro para la condición WHERE');
        }

        if (empty(self::$wheres)) {
            throw new \Error('no existe el metodo where() o debe estar antes');
        }

        if (is_null($valor)) {
            self::$andOrWheres[] = "AND $columna = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$andOrWheres[] = "AND $columna $operadorOvalor ?";
            self::$values[] = $valor;
        }

        return new static;
    }

    public static function orWhere(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        if (empty($columna)) {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        if (empty($operadorOvalor)) {
            throw new \Error('Debe proveer el segundo parámetro para la condición WHERE');
        }

        if (empty(self::$wheres)) {
            throw new \Error('no existe el metodo where() o debe estar antes');
        }

        if (is_null($valor)) {
            self::$andOrWheres[] = "OR $columna = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$andOrWheres[] = "OR $columna $operadorOvalor ?";
            self::$values[] = $valor;
        }

        return new static;
    }

    public static function whereConcat(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        if (empty($columna)) {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        self::$boolWhereConcat = true;

        if (is_null($valor)) {
            self::$wheres[] = "CONCAT($columna) = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$wheres[] = "CONCAT($columna) $operadorOvalor ?";
            self::$values[] = $valor;
        }
        return new static;
    }

    public static function whereBetween(string $columna, string|int $valor1, string|int $valor2): self
    {
        if (empty($columna)) {
            throw new \Error('Debe proveer el nombre de la columna para la condición WHERE');
        }

        self::$boolWhereBetween = true;

        self::$wheres[] = "$columna BETWEEN ? AND ?";
        self::$values[] = $valor1;
        self::$values[] = $valor2;

        return new static;
    }

    private function createQuery(string $type): string
    {
        // Query
        if ($type === 'max') {
            $sql = 'SELECT MAX(' .  self::$selects . ') FROM ' . $this->table;
        } else if ($type === 'min') {
            $sql = 'SELECT MIN(' .  self::$selects . ') FROM ' . $this->table;
        } else if ($type === 'avg') {
            $sql = 'SELECT AVG(' .  self::$selects . ') FROM ' . $this->table;
        } else if ($type === 'sum') {
            $sql = 'SELECT SUM(' .  self::$selects . ') FROM ' . $this->table;
        } else {
            $sql = 'SELECT ' .  self::$selects . ' FROM ' . $this->table;
        }

        // Joins
        if (!empty(self::$joins)) {
            $sql .= ' ' . implode(' ', self::$joins);
        }

        // Wheres
        if (!empty(self::$wheres)) {

            if (self::$boolWhere && self::$boolWhereBetween) {
                throw new \Error('el metodo where() no puede estar con el metodo whereBetween()');
            }

            if (self::$boolWhere && self::$boolWhereConcat) {
                throw new \Error('el metodo where() no puede estar con el metodo whereConcat()');
            }

            if (self::$boolWhereConcat && self::$boolWhereBetween) {
                throw new \Error('el metodo whereConcat() no puede estar con el metodo whereBetween()');
            }

            if (self::$boolWhere) {
                $sql .= ' WHERE ' . implode(' AND ', self::$wheres);
            }

            if (self::$boolWhereBetween) {
                $sql .= ' WHERE ' . implode(' ', self::$wheres);
            }

            if (self::$boolWhereConcat) {
                $sql .= ' WHERE ' . implode(' ', self::$wheres);
            }

            if (!empty(self::$andOrWheres)) {
                //agregamos los and or al final
                $sql .= ' ' . implode(' ', self::$andOrWheres);
            }
        }

        // Order bys
        if (!empty(self::$orderBys)) {
            $sql .= ' ORDER BY ' . implode(', ', self::$orderBys);
        }

        // Limit
        if (self::$limit && $type === 'get') {
            $sql .= ' LIMIT ' . self::$limit;
        } else if ($type === 'first') {
            $sql .= ' LIMIT 1';
        }

        return $sql;
    }

    private function resetProperties()
    {
        //Resetear propiedades estáticas
        self::$selects = '*';
        self::$joins = [];
        self::$wheres = [];
        self::$andOrWheres = [];
        self::$boolWhere = false;
        self::$boolWhereBetween = false;
        self::$boolWhereConcat = false;
        self::$orderBys = [];
        self::$limit = null;
        self::$values = [];
    }

    private function executeQuery(string $query): array|object
    {
        $statement = self::$db->statement($query, self::$values);
        return $statement;
    }

    protected static function customQuery(string $query, array|object $data = []): array|object
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $statement = self::$db->statement($query, $data);
        return $statement;
    }

    public function get(): ModelCollection|null
    {
        $sql = $this->createQuery('get');

        $result = $this->executeQuery($sql);
        if (count($result) == 0) {
            return null;
        }

        $arrayModels = array_map([static::class, 'createModelFromResult'], $result);

        //Resetear propiedades estáticas
        $this->resetProperties();

        return new ModelCollection($arrayModels);
    }

    public function first(): self|null
    {
        $sql = $this->createQuery('first');

        $result = $this->executeQuery($sql);
        if (count($result) == 0) {
            return null;
        }

        //Resetear propiedades estáticas
        $this->resetProperties();

        return self::createModelFromResult($result[0]);
    }

    public function max(): int|float|string
    {

        $selects = self::$selects;
        if ($selects == '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor maximo Model::select('columna')->max()");
        }
        $selectsArray = explode(", ", $selects);
        if (count($selectsArray) !== 1) {
            throw new \Error("solo se puede obtener el valor maximo de una columna Model::select('columna')->max()");
        }

        $sql = $this->createQuery('max');

        $result = $this->executeQuery($sql);

        $result = (array) $result[0];

        //Resetear propiedades estáticas
        $this->resetProperties();

        return $result["MAX($selects)"];
    }

    public function min(): int|float|string
    {
        $selects = self::$selects;
        if ($selects == '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor minimo Model::select('columna')->min()");
        }
        $selectsArray = explode(", ", $selects);
        if (count($selectsArray) !== 1) {
            throw new \Error("solo se puede obtener el valor minimo de una columna Model::select('columna')->min()");
        }
        $sql = $this->createQuery('min');
        $result = $this->executeQuery($sql);
        $result = (array) $result[0];
        //Resetear propiedades estáticas
        $this->resetProperties();
        return $result["MIN($selects)"];
    }

    public function sum(): int|float|string
    {
        $selects = self::$selects;
        if ($selects == '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor suma Model::select('columna')->sum()");
        }
        $selectsArray = explode(", ", $selects);
        if (count($selectsArray) !== 1) {
            throw new \Error("solo se puede obtener el valor suma de una columna Model::select('columna')->sum()");
        }
        $sql = $this->createQuery('sum');
        $result = $this->executeQuery($sql);
        $result = (array) $result[0];
        //Resetear propiedades estáticas
        $this->resetProperties();
        return $result["SUM($selects)"];
    }

    public function avg(): int|float|string
    {
        $selects = self::$selects;
        if ($selects == '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor promedio Model::select('columna')->avg()");
        }
        $selectsArray = explode(", ", $selects);
        if (count($selectsArray) !== 1) {
            throw new \Error("solo se puede obtener el valor promedio de una columna Model::select('columna')->avg()");
        }
        $sql = $this->createQuery('avg');
        $result = $this->executeQuery($sql);
        $result = (array) $result[0];
        //Resetear propiedades estáticas
        $this->resetProperties();
        return $result["AVG($selects)"];
    }

    // Agregar este método en la clase Model
    public static function dd(): array
    {
        $model = new static();
        $sql = $model->createQuery('get');
        $bindings = self::$values;

        // Formatear SQL con bindings aplicados
        $debugSql = $sql;
        foreach ($bindings as $value) {
            $value = is_string($value)
                ? "'" . addslashes($value) . "'"
                : (is_null($value) ? 'NULL' : $value);
            $debugSql = preg_replace('/\?/', $value, $debugSql, 1);
        }

        $data = [
            'sql_raw' => $sql,
            'sql_debug' => $debugSql,
            'bindings' => $bindings,
            'model' => static::class
        ];

        // ¡Resetear propiedades después de construir la consulta!
        $model->resetProperties();

        return $data;
    }

    private static function createModelFromResult(array $result): self
    {
        $model = new static();
        $model->setAttributes($result);
        return $model;
    }

    public static function all(): ModelCollection|null
    {
        $model = new static();
        $sql = "SELECT * FROM {$model->table}";
        $result = $model->executeQuery($sql);
        if (count($result) == 0) {
            return null;
        }

        $arrayModels = array_map([static::class, 'createModelFromResult'], $result);
        //Resetear propiedades estáticas
        $model->resetProperties();
        return new ModelCollection($arrayModels);
    }

    public static function find(int|string $id): self|null
    {
        self::$values = [$id];
        $model = new static();
        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ?";
        $result = $model->executeQuery($sql);
        if (count($result) == 0) {
            return null;
        }
        //Resetear propiedades estáticas
        $model->resetProperties();
        return self::createModelFromResult($result[0]);
    }

    public function firstNotHidden(): self|null
    {
        $sql = $this->createQuery('first');
        $result = $this->executeQuery($sql);
        if (count($result) == 0) {
            return null;
        }
        $model = new static();
        $model->resetProperties();
        return self::createModelFromResult($result[0]);
    }


    public function hasOne($related, $foreignKey = null, $localKey = 'id')
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->table . '_id';
        return new HasOne($instance, $this, $foreignKey, $localKey);
    }

    public function hasMany(string $related, string $foreignKey = null, string $localKey = 'id'): HasMany
    {
        $foreignKey = $foreignKey ?: $this->table . '_id';
        return new HasMany(new $related(), $this, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, string $foreignKey = null, string $ownerKey = 'id'): BelongsTo
    {
        $foreignKey = $foreignKey ?: (new $related)->primaryKey;
        return new BelongsTo(new $related(), $this, $foreignKey, $ownerKey);
    }

    public function belongsToMany(string $related, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey): BelongsToMany
    {
        return new BelongsToMany(new $related(), $this, $pivotTable, $foreignPivotKey, $relatedPivotKey);
    }
}

class HasOne
{
    protected $related;
    protected $parent;
    protected $foreignKey;
    protected $localKey;

    public function __construct($related, $parent, $foreignKey, $localKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function get()
    {
        return $this->related->where($this->foreignKey, $this->parent->{$this->localKey})->first();
    }
}

class HasMany
{
    protected Model $related;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function get(): ?\Cronos\Model\ModelCollection
    {
        return $this->related->where($this->foreignKey, $this->parent->{$this->localKey})->get();
    }
}

class BelongsTo
{
    protected Model $related;
    protected Model $parent;
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $ownerKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    public function get(): ?Model
    {
        return $this->related->where($this->ownerKey, $this->parent->{$this->foreignKey})->first();
    }
}

class BelongsToMany
{
    protected Model $related;
    protected Model $parent;
    protected string $pivotTable;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;

    public function __construct(Model $related, Model $parent, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    public function get(): ?\Cronos\Model\ModelCollection
    {
        $this->related->join(
            $this->pivotTable,
            $this->related->table . '.' . $this->related->primaryKey,
            '=',
            $this->pivotTable . '.' . $this->relatedPivotKey
        )->where(
            $this->pivotTable . '.' . $this->foreignPivotKey,
            $this->parent->{$this->parent->primaryKey}
        );

        return $this->related->get();
    }
}
