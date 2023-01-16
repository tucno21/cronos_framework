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

    public function __set($name, $value)
    {
        //agregar los atributos(propiedades) al objeto para poder acceder a ellos y json
        $this->$name = $value;
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        return $this->$name ?? null;
    }

    //metodo para insertar registros en la base de datos
    public function save(): object
    {
        if ($this->timestamps) {
            $this->attributes[$this->created] = date('Y-m-d H:i:s');
            $this->attributes[$this->updated] = date('Y-m-d H:i:s');

            //agregar los atributos created y updated al objeto
            $this->{$this->created} = $this->attributes[$this->created];
            $this->{$this->updated} = $this->attributes[$this->updated];
        }

        //las claves de los atributos forma un string separado por comas
        $databaseColumns = implode(",", array_keys($this->attributes));
        //el numero de ? es igual al numero de atributos
        $bind = implode(",", array_fill(0, count($this->attributes), "?"));
        //el string de las columnas de la base de datos se une con el string de los ? para formar la consulta
        $sql = "INSERT INTO {$this->table} ({$databaseColumns}) VALUES ({$bind})";

        //los valores de los atributos se convierten en un array
        $param = array_values($this->attributes);

        //se ejecuta la consulta
        self::$db->statementC_U_D($sql, $param);

        //se obtiene el id del ultimo registro insertado
        $this->attributes[$this->primaryKey] = self::$db->lastInsertId();
        //agregar el atributo id al inicio del objeto
        $this->{$this->primaryKey} = $this->attributes[$this->primaryKey];

        return $this;
    }

    //metodo para asignar los atributos
    protected function attributesAsignCreate(array $attributes): static
    {
        if (count($this->fillable) == 0) {
            throw new \Error("Model " . static::class . " No tiene atributos asignables");
        }

        //extraer las claves del array $attributes
        $attributesKey = array_keys($attributes);

        if (count($this->fillable) != count($attributesKey)) {
            throw new \Error("Los datos enviados No tiene el mismo numero de atributos asignables al Model " . static::class);
        }

        //comparar el array $attributesKey con array $fillable y devolver booleano
        $bool = false;
        foreach ($this->fillable as $value) {
            //todos los valores de $fillable deben estar en $attributesKey
            if (in_array($value, $attributesKey)) {
                $bool = true;
            } else {
                $bool = false;
                break;
            }
        }

        if (!$bool) {
            throw new \Error("Los datos enviados No tiene los mismos atributos(campos) al Model " . static::class);
        }

        foreach ($this->fillable as $key => $value) {
            $this->attributes[$value] = $attributes[$value];
            $this->{$value} = $attributes[$value];
        }

        return $this;
    }

    //metodo estatico para crear un nuevo registro
    public static function create(array|object $data): object
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $model = new static();

        $model->attributesAsignCreate($data);

        return $model->save();
    }

    //metodo para comprobar si existe dentro $fillable los atributos que se quieren actualizar
    protected function checkFillable(array $attributes): bool
    {
        if (count($this->fillable) == 0) {
            throw new \Error("Model " . static::class . " No tiene atributos asignables");
        }

        //extraer las claves del array $attributes
        $attributesKey = array_keys($attributes);

        //comparar el array $attributesKey con array $fillable y devolver booleano
        $bool = false;
        foreach ($attributesKey as $value) {
            //comprobar que  $value este dentro de $fillable y si no esta devolver false
            if (in_array($value, $this->fillable)) {
                $bool = true;
            } else {
                $bool = false;
                break;
            }
        }

        if (!$bool) {
            throw new \Error("Los datos enviados no tiene los mismos atributos(campos) del Model " . static::class);
        }

        return $bool;
    }

    //metodo para asignar los atributos
    protected function attributesAsign(array $data)
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    //metodo para actualizar registros
    public static function update($id, array|object $data): object|int
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $model = new static();

        $model->checkFillable($data);

        $model->attributesAsign($data);

        if ($model->timestamps) {
            $model->attributes[$model->updated] = date('Y-m-d H:i:s');
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
            //agregar el atributo id al inicio del objeto
            return $model->setAttributes($model->attributes);
        } else {
            return $rows;
        }
    }

    //metodo para eliminar registros
    public static function delete($id): bool
    {
        $model = new static();

        $sql = "DELETE FROM {$model->table} WHERE {$model->primaryKey} = ?";

        $param = [$id];

        $rows = self::$db->statementC_U_D($sql, $param);

        if ($rows > 0) {
            return true;
        } else {
            return false;
        }
    }

    //metodo para obtener todos los registros
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

        foreach ($result as $row) {
            //enviamos la respuesta como un objeto de la clase instanciada
            $models[] = (new static())->setAttributes($row);
        }

        return $models;
    }

    //metodo para obtener un registro
    public static function find(int|string $id): ?static
    {
        $model = new static();

        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ?";

        $param = [$id];

        $result = self::$db->statement($sql, $param);

        if (count($result) == 0) {
            throw new \Error("No se encontro el registro con el id {$id} en la tabla {$model->table}");
        }

        // return (object) $result[0];
        return $model->setAttributes($result[0]);
    }

    protected function setAttributes(array|object $attributes): static
    {
        foreach ($attributes as $key => $value) {
            //agregamos a la clase propiedades dinamicas con si nombre y valor
            //haciendo uso del metodo magico __set
            $this->__set($key, $value);
        }

        return $this;
    }

    //metodo para un registro pero con un orden descendente
    public static function last(): ?static
    {
        $model = new static();

        $sql = "SELECT * FROM {$model->table} ORDER BY {$model->primaryKey} DESC LIMIT 1";

        $param = [];

        $result = self::$db->statement($sql, $param);

        if (count($result) == 0) {
            throw new \Error("No se encontro el registro en la tabla {$model->table}");
        }

        //enviamos la respuesta como un objeto de la clase instanciada
        return $model->setAttributes($result[0]);
    }

    //metodo para agregar las columnas que se quieren obtener
    public static function select(string ...$select): self
    {
        //unir los elementos del array $select con una coma
        $select = implode(", ", $select);
        self::$select = $select;

        return new static;
    }

    //metodo para agregar condiciones a la consulta
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

    //metodo para agregar condiciones a la consulta mediante dos valores
    public static function whereBetween(string $columna, string|int $valor1, string|int $valor2): self
    {
        self::$existWhere = true;

        self::$where[] = "$columna BETWEEN ? AND ?";
        self::$values[] = $valor1;
        self::$values[] = $valor2;
        return new static;
    }

    //metodo para agregar condiciones mediante concatenacion de columnas
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

    //metodo para agregar condiciones AND desde el metodo where
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

    //metodo para obtener los registros despues de la anidacion de metodos
    public function get(): ?static
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

        $statement = $this->executeResult(self::$query);

        foreach ($statement as $key => $value) {
            //enviamos la respuesta como un objeto de la clase instanciada
            $statement[$key] = $this->setAttributes($value);
        }

        return $statement;
    }

    //metodo para obtener un solo registro despues de la anidacion de metodos
    public function first(): ?static
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

        $statement = $this->executeResult(self::$query);

        //enviamos la respuesta como un objeto de la clase instanciada
        $this->setAttributes($statement[0]);
        return $this;
    }

    //metodo para obtener la valor maximo de una columna con valores numericos despues de la anidacion de metodos
    public function max(): int
    {

        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT MAX($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT MAX($select) FROM {$this->table} $join $orderby";
        }

        $statement = $this->executeResult(self::$query);

        //obejto a array
        $statement = (array) $statement[0];

        return $statement["MAX($select)"];
    }

    //metodo para obtener la valor minimo de una columna con valores numericos despues de la anidacion de metodos
    public function min(): int
    {

        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT MIN($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT MIN($select) FROM {$this->table} $join $orderby";
        }

        $statement = $this->executeResult(self::$query);

        //obejto a array
        $statement = (array) $statement[0];

        return $statement["MIN($select)"];
    }

    //metodo para obtener la valor de la suma de una columna con valores numericos despues de la anidacion de metodos
    public function sum(): int
    {
        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT SUM($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT SUM($select) FROM {$this->table} $join $orderby";
        }

        $statement = $this->executeResult(self::$query);

        //obejto a array
        $statement = (array) $statement[0];

        return $statement["SUM($select)"];
    }

    //metodo para obtener la valor promedio de una columna con valores numericos despues de la anidacion de metodos
    public function avg(): int
    {
        $select = self::$select;
        $join = implode(" ", self::$join);
        $where = implode(" ", self::$where);
        $orderby = self::$orderby;

        if (self::$existWhere) {
            self::$query = "SELECT AVG($select) FROM {$this->table} $join WHERE $where $orderby";
        } else {
            self::$query = "SELECT AVG($select) FROM {$this->table} $join $orderby";
        }

        $statement = $this->executeResult(self::$query);

        //obejto a array
        $statement = (array) $statement[0];

        return $statement["AVG($select)"];
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


    //metodo para ejecutar una consulta personalizada y ser usada en el modelo hijo
    protected function statement(string $query, array $values = []): array|object
    {
        $statement = self::$db->statement($query, $values);

        return $statement;
    }
}
