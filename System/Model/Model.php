<?php

namespace Cronos\Model;

use Cronos\Database\DatabaseDriver;

abstract class Model
{

    protected string $table = '';

    protected string $primaryKey = 'id';

    protected array $fillable = [];

    protected array $hidden = [];

    protected bool $timestamps = false;

    protected string $created = '';

    protected string $updated = '';

    protected array $attributes = [];

    protected static string $query = '';
    protected static bool $existWhere = false;
    protected static array $where = [];
    protected static array $join = [];
    protected static string $select = '*';
    protected static string $orderby = '';
    protected static string $limit = '';
    protected static array $values = [];

    protected static ?DatabaseDriver $db = null;

    public static function setDB(DatabaseDriver $db)
    {
        self::$db = $db;
    }

    //metodo magico para obtener los atributos de la clase
    public function __get(string $attribute)
    {
        return in_array($attribute, $this->hidden) ? null : $this->attributes[$attribute] ?? null;
    }

    //metodo magico para asignar los atributos de la clase
    public function __set(string $attribute, $value)
    {
        //in_array — Comprueba si un valor existe en un array
        if (in_array($attribute, $this->fillable) || $attribute == $this->primaryKey) {
            $this->$attribute = $this->attributes[$attribute] = $value;
        } elseif ($this->timestamps && ($attribute === $this->created || $attribute === $this->updated)) {
            $this->$attribute = $this->attributes[$attribute] = $value;
        } else {
            throw new \Error('El atributo "' . $attribute . '" no es asignable en el modelo ' . static::class);
        }
    }

    //metodo para pasar los atributos a la propiedad attributes
    protected function setAttribute(string $attribute, mixed $value): void
    {
        if (in_array($attribute, $this->fillable)) {
            $this->attributes[$attribute] = $value;
        } else {
            throw new \Error('El atributo "' . $attribute . '" no es asignable en el modelo ' . static::class);
        }
    }

    //metodo para guardar los datos en la base de datos
    public function save(): ?self
    {
        if ($this->timestamps) {
            //agregar los campos created_at y updated_at proiedad atributos
            $this->attributes[$this->created] = $this->attributes[$this->updated] = date('Y-m-d H:i:s');
        }

        //las claves de los atributos forma un string separado por comas
        $databaseColumns = implode(",", array_keys($this->attributes));
        //el numero de ? es igual al numero de atributos
        $bind = implode(",", array_fill(0, count($this->attributes), "?"));
        //el string de las columnas de la base de datos se une con el string de los ? para formar la consulta
        $sql = "INSERT INTO {$this->table} ({$databaseColumns}) VALUES ({$bind})";

        //los valores de los atributos se convierten en un array
        $param = array_values($this->attributes);

        $responseInt = self::$db->statementC_U_D($sql, $param);
        if ($responseInt > 0) {
            //se obtiene el id del ultimo registro insertado
            $this->attributes[$this->primaryKey] = self::$db->lastInsertId();

            // como retornar como propiedades del la clase hija los datos de la propiedad $this->attributes
            foreach ($this->attributes as $key => $value) {
                // ocultar los atributos que estan en la propiedad $this->hidden
                if (!in_array($key, $this->hidden)) {
                    $this->{$key} = $value;
                }
            }

            return $this;
        }
    }

    //metodo estico para guardar los datos en la base de datos
    public static function create(array|object $data): ?self
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $model = new static();
        foreach ($data as $key => $value) {
            // Ocultar los atributos que están en la propiedad $model->hidden
            if (!in_array($key, $model->hidden)) {
                $model->{$key} = $value;
            } else {
                // Guardar el campo eliminado de la propiedad $model->hidden
                $model->setAttribute($key, $value);
            }
        }
        return $model->save();
    }

    //metodo para actualizar los datos en la base de datos
    public static function update(int|string $id, array|object $data): ?self
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        //instancia de la clase hija
        $model = new static();

        //si timestamps es true se agrega el campo updated_at
        if ($model->timestamps) {
            $model->attributes[$model->updated] = date('Y-m-d H:i:s');
        }

        foreach ($data as $key => $value) {
            // creamos propiedades con los nombres de los campos de la base de datos ocultando los campos que estan en la propiedad $model->hidden
            if (!in_array($key, $model->hidden)) {
                $model->{$key} = $value;
            } else {
                // Guardar el campo eliminado de la propiedad $model->hidden
                $model->setAttribute($key, $value);
            }
        }

        $sql = "UPDATE {$model->table} SET ";

        $param = [];
        foreach ($model->attributes as $key => $value) {
            $sql .= "{$key} = ?, ";
            $param[] = $value;
        }

        $sql = substr($sql, 0, -2);

        $sql .= " WHERE {$model->primaryKey} = ?";

        $param[] = $id;

        $rows = self::$db->statementC_U_D($sql, $param);

        if ($rows > 0) {
            $model->attributes[$model->primaryKey] = $id;

            foreach ($model->attributes as $key => $value) {
                // ocultar los atributos que estan en la propiedad $this->hidden
                if (!in_array($key, $model->hidden)) {
                    $model->{$key} = $value;
                }
            }

            return $model;
        } else {
            return null;
        }
    }

    //metodo para eliminar los datos en la base de datos
    public static function delete(int|string $id): bool
    {
        $model = new static();
        $sql = "DELETE FROM {$model->table} WHERE {$model->primaryKey} = ?";
        $param = [$id];
        $rows = self::$db->statementC_U_D($sql, $param);
        return $rows > 0;
    }

    //metodo statico para crear modelos a partir de los resultados de la consulta
    private static function createModelsFromResults(array $results): array
    {
        $models = [];

        foreach ($results as $row) {
            $models[] = self::createModelFromResult($row);
        }

        return $models;
    }

    //metodo statico para crear un modelo a partir de un resultado de la consulta
    private static function createModelFromResult(object $result): self
    {
        $model = new static();

        foreach ($result as $key => $value) {
            // ocultar los atributos que estan en la propiedad $this->hidden
            if (!in_array($key, $model->hidden)) {
                $model->{$key} = $value;
            }
        }

        return $model;
    }

    //metodo estatico para obtener todos los registros de la tabla
    public static function all(): array
    {
        $model = new static();

        $sql = "SELECT * FROM {$model->table}";

        $param = [];

        $result = self::$db->statement($sql, $param);

        if (count($result) == 0) {
            return [];
        }

        $models = [];

        return self::createModelsFromResults($result);
    }

    //metodo estatico para obtener un registro de la tabla
    public static function find(int|string $id): ?self
    {
        $model = new static();

        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ?";

        $param = [$id];

        $result = self::$db->statement($sql, $param);

        if (count($result) == 0) {
            return null;
        }

        return self::createModelFromResult($result[0]);
    }

    //metodo estatico para obtener el ultimo registro de la tabla
    public static function last(): ?self
    {
        $model = new static();

        $sql = "SELECT * FROM {$model->table} ORDER BY {$model->primaryKey} DESC LIMIT 1";

        $param = [];

        $result = self::$db->statement($sql, $param);

        if (count($result) == 0) {
            return null;
        }

        return self::createModelFromResult($result[0]);
    }

    //metodo para agregar las columnas a la consulta
    public static function select(string ...$select): self
    {
        //unir los elementos del array $select con una coma
        $select = implode(", ", $select);
        self::$select = $select;

        return new static;
    }

    //metodo para agregar condicione where a la consulta
    public static function where(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        self::$existWhere = true;

        if (is_null($valor)) {
            self::$where[] = "$columna = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$where[] = "$columna $operadorOvalor ?";
            self::$values[] = $valor;
        }

        return new static;
    }

    //metodo para agregar condicione where BETWEEN a la consulta
    public static function whereBetween(string $columna, string|int $valor1, string|int $valor2): self
    {
        self::$existWhere = true;

        self::$where[] = "$columna BETWEEN ? AND ?";
        self::$values[] = $valor1;
        self::$values[] = $valor2;
        return new static;
    }

    //metodo para agregar condicione where CONCAT a la consulta
    public static function whereConcat(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        if (is_null($valor)) {
            self::$where[] = "CONCAT($columna) = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$where[] = "CONCAT($columna) $operadorOvalor ?";
            self::$values[] = $valor;
        }
        return new static;
    }

    //metodo para agregar condicione where AND a la consulta   
    public static function andWhere(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        if (is_null($valor)) {
            self::$where[] = "AND $columna = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$where[] = "AND $columna $operadorOvalor ?";
            self::$values[] = $valor;
        }

        return new static;
    }

    //metodo para agregar condiciones OR desde el metodo where
    public static function orWhere(string $columna, string|int $operadorOvalor, string|int $valor = null): self
    {
        if (is_null($valor)) {
            self::$where[] = "OR $columna = ?";
            self::$values[] = $operadorOvalor;
        } else {
            self::$where[] = "OR $columna $operadorOvalor ?";
            self::$values[] = $valor;
        }
        return new static;
    }

    //metodo para unir tablas
    public static function join(string $tabla, string $columna1, string $operador, string $columna2, string $tipo = "INNER"): self
    {
        self::$join[] = "$tipo JOIN $tabla ON $columna1 $operador $columna2";
        return new static;
    }


    //metodo para ordenar los registros
    public static function orderBy(string $columna, string $orden): self
    {
        self::$orderby = "ORDER BY $columna $orden";
        return new static;
    }

    //metodo para limitar la cantidad de registros
    public static function limit(int $limit): self
    {
        //comprobar si el limite es un numero entero
        if (!is_int($limit)) {
            throw new \Error("El limite debe ser un numero entero");
        }

        //comprobar si el limite es mayor a 0
        if ($limit <= 0) {
            throw new \Error("El limite debe ser mayor a 0");
        }

        self::$limit = "LIMIT $limit";

        return new static;
    }

    //metodo para obtener la cantidad de registros despues de la anidacion de metodos
    private function executeResult(string $query): array|object
    {
        $statement = self::$db->statement($query, self::$values);

        self::$join = array();
        self::$select = '*';
        self::$where = array();
        self::$orderby = '';
        self::$values = array();

        return $statement;
    }

    public function firstNotHidden(): ?self
    {
        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT $select FROM {$this->table} $join WHERE $where $orderby LIMIT 1";
        } else {
            self::$query = "SELECT $select FROM {$this->table} $join $orderby LIMIT 1";
        }

        $result = $this->executeResult(self::$query);

        if (count($result) == 0) {
            return null;
        }

        $model = new static();

        foreach ($result[0] as $key => $value) {
            // ocultar los atributos que estan en la propiedad $this->hidden
            $model->{$key} = $value;
        }

        return $model;
    }


    //metodo para obtener el primer registro despues de la anidacion de metodos
    public function first(): ?self
    {
        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT $select FROM {$this->table} $join WHERE $where $orderby LIMIT 1";
        } else {
            self::$query = "SELECT $select FROM {$this->table} $join $orderby LIMIT 1";
        }

        $result = $this->executeResult(self::$query);

        if (count($result) == 0) {
            return null;
        }

        return self::createModelFromResult($result[0]);
    }

    //metodo para obtener todos los registros despues de la anidacion de metodos
    public function get(): ?array
    {
        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;
        $limit = self::$limit;

        if (self::$existWhere) {
            self::$query = "SELECT $select FROM {$this->table} $join WHERE $where $orderby $limit";
        } else {
            self::$query = "SELECT $select FROM {$this->table} $join $orderby $limit";
        }

        $result = $this->executeResult(self::$query);

        if (count($result) == 0) {
            return null;
        }

        return self::createModelsFromResults($result);
    }

    //metodo para obtener la valor maximo de una columna con valores numericos despues de la anidacion de metodos
    public function max(): int|float|string
    {
        $select = self::$select;
        if ($select == '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor maximo Model::select('columna')->max()");
        }

        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT MAX($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT MAX($select) FROM {$this->table} $join $orderby";
        }

        $result = $this->executeResult(self::$query);

        $result = (array) $result[0];

        return $result["MAX($select)"];
    }

    //metodo para obtener la valor minimo de una columna con valores numericos despues de la anidacion de metodos
    public function min(): int|float|string
    {
        $select = self::$select;
        if ($select == '*') {
            throw new \Error("no agrego ninguna columna para obtener el valor minimo Model::select('columna')->min()");
        }

        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT MIN($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT MIN($select) FROM {$this->table} $join $orderby";
        }

        $result = $this->executeResult(self::$query);

        $result = (array) $result[0];

        return $result["MIN($select)"];
    }

    //metodo para obtener la valor de la suma de una columna con valores numericos despues de la anidacion de metodos
    public function sum(): int|float|string
    {
        $select = self::$select;
        if ($select == '*') {
            throw new \Error("no agrego ninguna columna para obtener la suma Model::select('columna')->sum()");
        }

        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT SUM($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT SUM($select) FROM {$this->table} $join $orderby";
        }

        $result = $this->executeResult(self::$query);

        $result = (array) $result[0];

        return $result["SUM($select)"];
    }

    //metodo para obtener la valor promedio de una columna con valores numericos despues de la anidacion de metodos
    public function avg(): int|float|string
    {
        $select = self::$select;
        if ($select == '*') {
            throw new \Error("no agrego ninguna columna para obtener el promedio Model::select('columna')->avg()");
        }

        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT AVG($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT AVG($select) FROM {$this->table} $join $orderby";
        }

        $result = $this->executeResult(self::$query);

        $result = (array) $result[0];

        return $result["AVG($select)"];
    }

    //metodo para ejecutar una consulta personalizada y ser usada en el modelo hijo
    protected static function statement(string $query, array $values = []): array|object
    {
        $statement = self::$db->statement($query, $values);

        return $statement;
    }
}
