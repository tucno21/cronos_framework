<?php

namespace Cronos\Model;

use Cronos\Database\DatabaseDriver;

/**
 * Modelo base del ORM de Cronos.
 *
 * Uso estatico (facade): Usuario::where(...)->get(), Usuario::find(1), etc.
 * Cada cadena de llamadas crea su propio QueryBuilder, por lo que las
 * consultas de un modelo nunca contaminan las de otro.
 *
 * @phpstan-consistent-constructor
 *
 * API de consulta (compatible con versiones anteriores):
 *
 * @method static QueryBuilder select(string ...$select)
 * @method static QueryBuilder join(string $table, string $first, string $operator, string $second)
 * @method static QueryBuilder where(string $columna, string|int $operadorOvalor, string|int|null $valor = null)
 * @method static QueryBuilder andWhere(string $columna, string|int $operadorOvalor, string|int|null $valor = null)
 * @method static QueryBuilder orWhere(string $columna, string|int $operadorOvalor, string|int|null $valor = null)
 * @method static QueryBuilder whereConcat(string $columna, string|int $operadorOvalor, string|int|null $valor = null)
 * @method static QueryBuilder whereBetween(string $columna, string|int $valor1, string|int $valor2)
 * @method static QueryBuilder whereIn(string $columna, array $valores)
 * @method static QueryBuilder whereNotIn(string $columna, array $valores)
 * @method static QueryBuilder whereNull(string $columna)
 * @method static QueryBuilder whereNotNull(string $columna)
 * @method static QueryBuilder orderBy(string $column, string $direction = 'ASC')
 * @method static QueryBuilder limit(int $limit)
 * @method static QueryBuilder offset(int $offset)
 * @method static QueryBuilder with(string|array ...$relations)
 * @method static QueryBuilder has(string $relation, string $operador = '>=', int $cantidad = 1)
 * @method static QueryBuilder whereHas(string $relation, callable|null $callback = null, string $operador = '>=', int $cantidad = 1)
 * @method static QueryBuilder withCount(string|array ...$relations)
 * @method static QueryBuilder update(array|object $data)
 * @method static QueryBuilder delete()
 * @method static ModelCollection|null get()
 * @method static self|null first()
 * @method static self firstOrFail()
 * @method static self|null firstNotHidden()
 * @method static int count(?string $column = null)
 * @method static \Cronos\Model\Paginator paginate(int $porPagina = 15, int $pagina = 1)
 * @method static int|float|string max(?string $column = null)
 * @method static int|float|string min(?string $column = null)
 * @method static int|float|string sum(?string $column = null)
 * @method static int|float|string avg(?string $column = null)
 * @method static array dd()
 *
 * API de instancia (estilo Eloquent, sobre una fila ya cargada):
 *
 * @method bool update(array|object $data) actualiza esta fila por su clave primaria
 * @method bool delete() elimina esta fila (soft delete si el modelo lo usa)
 * @method bool forceDelete() elimina fisicamente esta fila
 * @method bool restore() recupera esta fila con soft delete
 */
abstract class Model
{
    //COLUMNAS DE TIMESTAMP POR DEFECTO (cada modelo puede sobreescribirlas
    //con las propiedades $created y $updated)
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    //DETECCION DE N+1 (Model::preventLazyLoading() en desarrollo)
    private static bool $preventLazyLoading = false;

    //DATOS BASICOS DEL MODELO DE LA TABLA
    protected string $table = '';
    protected string $primaryKey = '';
    protected array $fillable = [];
    protected array $hidden = [];
    protected bool $timestamps = false;
    protected string $created = self::CREATED_AT;
    protected string $updated = self::UPDATED_AT;

    //CASTS OPT-IN: convierten tipos al hidratar desde la BD (todo llega como
    //string de PDO). Sin casts, el comportamiento es identico al anterior.
    protected array $casts = [];

    //GUARDAR LOS ATRIBUTOS PARA CREATE, UPDATE
    protected array $attributes = [];

    //ATRIBUTOS TAL COMO VINIERON DE LA BD (para detectar cambios en save())
    protected array $original = [];

    //ACCESSORS EXPUESTOS EN toArray()/toObject()/toJson()
    protected array $appends = [];

    //LISTENERS DE EVENTOS DE MODELO POR CLASE (creating, created, updating...)
    private static array $modelEventListeners = [];

    //CLASES YA INICIALIZADAS (booted())
    private static array $booted = [];

    private const EVENTOS_MODELO = [
        'retrieved',
        'creating',
        'created',
        'updating',
        'updated',
        'saving',
        'saved',
        'deleting',
        'deleted',
        'restoring',
        'restored',
        'forceDeleted',
    ];

    public function __construct()
    {
        static::bootIfNotBooted();
    }

    /**
     * Inicializa la clase una sola vez (registra eventos definidos en booted()).
     */
    protected static function bootIfNotBooted(): void
    {
        if (!isset(self::$booted[static::class])) {
            self::$booted[static::class] = true;
            static::booted();
        }
    }

    /**
     * Hook de inicializacion del modelo: sobreescribelo en tu modelo para
     * registrar eventos, ej. static::creating(fn ($m) => ...);
     */
    protected static function booted(): void
    {
    }

    //******************************************************************
    // EVENTOS DE MODELO (estilo Laravel)
    //******************************************************************

    public static function observe(object|string $observador): void
    {
        $instancia = is_object($observador) ? $observador : new $observador();

        foreach (self::EVENTOS_MODELO as $evento) {
            if (method_exists($instancia, $evento)) {
                self::registerModelEvent($evento, fn ($modelo) => $instancia->{$evento}($modelo));
            }
        }
    }

    protected static function registerModelEvent(string $evento, callable $callback): void
    {
        self::$modelEventListeners[static::class][$evento][] = $callback;
    }

    /**
     * Elimina todos los listeners de eventos de la clase y la marca como
     * no inicializada (se re-registrara booted() en la proxima instancia).
     * Pensado para tests.
     */
    public static function flushEventListeners(): void
    {
        unset(self::$modelEventListeners[static::class]);
        unset(self::$booted[static::class]);
    }

    /**
     * Dispara un evento del modelo. Si un listener retorna false el
     * evento se considera "detenido" y la operacion se aborta.
     */
    protected function fireModelEvent(string $evento): bool
    {
        $listeners = self::$modelEventListeners[static::class][$evento] ?? [];

        foreach ($listeners as $listener) {
            if ($listener($this) === false) {
                return false;
            }
        }

        return true;
    }

    public static function retrieved(callable $callback): void
    {
        self::registerModelEvent('retrieved', $callback);
    }

    public static function creating(callable $callback): void
    {
        self::registerModelEvent('creating', $callback);
    }

    public static function created(callable $callback): void
    {
        self::registerModelEvent('created', $callback);
    }

    public static function updating(callable $callback): void
    {
        self::registerModelEvent('updating', $callback);
    }

    public static function updated(callable $callback): void
    {
        self::registerModelEvent('updated', $callback);
    }

    public static function saving(callable $callback): void
    {
        self::registerModelEvent('saving', $callback);
    }

    public static function saved(callable $callback): void
    {
        self::registerModelEvent('saved', $callback);
    }

    public static function deleting(callable $callback): void
    {
        self::registerModelEvent('deleting', $callback);
    }

    public static function deleted(callable $callback): void
    {
        self::registerModelEvent('deleted', $callback);
    }

    public static function restoring(callable $callback): void
    {
        self::registerModelEvent('restoring', $callback);
    }

    public static function restored(callable $callback): void
    {
        self::registerModelEvent('restored', $callback);
    }

    public static function forceDeleted(callable $callback): void
    {
        self::registerModelEvent('forceDeleted', $callback);
    }

    //RELACIONES CARGADAS (eager o lazy) sobre la instancia
    protected array $relations = [];

    private static ?DatabaseDriver $db = null;

    private static int $transactionLevel = 0;

    public static function setDB(DatabaseDriver $db): void
    {
        self::$db = $db;
    }

    public static function db(): DatabaseDriver
    {
        if (self::$db === null) {
            throw new \Error('No hay conexion de base de datos. Llame a Model::setDB() primero.');
        }

        return self::$db;
    }

    /**
     * Activa/desactiva la deteccion de N+1: al estar activa, acceder a una
     * relacion no cargada lanza un Error en lugar de lanzar la consulta
     * perezosa. Pensado para desarrollo (Laravel: Model::preventLazyLoading).
     */
    public static function preventLazyLoading(bool $prevent = true): void
    {
        self::$preventLazyLoading = $prevent;
    }

    public static function isLazyLoadingPrevented(): bool
    {
        return self::$preventLazyLoading;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function hasTimestamps(): bool
    {
        return $this->timestamps;
    }

    public function getCreatedAtColumn(): string
    {
        return $this->created;
    }

    public function getUpdatedAtColumn(): string
    {
        return $this->updated;
    }

    //******************************************************************
    // ACCESO A ATRIBUTOS Y RELACIONES
    //******************************************************************

    public function __get(string $property)
    {
        //ACCESSOR: get{Campo}Attribute transforma el valor en lectura
        $accessor = 'get' . self::studly($property) . 'Attribute';

        //Verifica si la propiedad existe en el arreglo de atributos
        if (array_key_exists($property, $this->attributes)) {
            $valor = $this->attributes[$property];

            return method_exists($this, $accessor) ? $this->{$accessor}($valor) : $valor;
        }

        //Relacion ya cargada (cache de la instancia)
        if (array_key_exists($property, $this->relations)) {
            return $this->relations[$property];
        }

        //Accessor sin columna detras (campos calculados / $appends)
        if (method_exists($this, $accessor)) {
            return $this->{$accessor}(null);
        }

        //Carga perezosa (lazy) de la relacion la primera vez que se accede
        if (method_exists($this, $property)) {
            if (self::$preventLazyLoading) {
                throw new \Error(
                    "Carga perezosa (N+1) bloqueada para la relacion [{$property}] en " . static::class
                    . ". Usa with('{$property}') o desactivala con Model::preventLazyLoading(false)."
                );
            }

            $value = $this->{$property}()->get();
            $this->relations[$property] = $value;

            return $value;
        }

        return null; //Devuelve null si la propiedad no existe
    }

    public function __set(string $name, mixed $value)
    {
        //MUTATOR: set{Campo}Attribute transforma el valor en escritura
        $mutator = 'set' . self::studly($name) . 'Attribute';

        if (method_exists($this, $mutator)) {
            $value = $this->{$mutator}($value);
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Convierte snake_case a StudlyCase: nombre_completo -> NombreCompleto.
     */
    public static function studly(string $valor): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $valor)));
    }

    public function setRelation(string $name, mixed $value): void
    {
        $this->relations[$name] = $value;
    }

    public function getRelation(string $name): mixed
    {
        return $this->relations[$name] ?? null;
    }

    public function relationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    protected function setAttributes(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Crea una instancia del modelo a partir de una fila de la BD
     * aplicando los casts definidos.
     */
    public static function hydrate(array $row): static
    {
        $model = new static();
        $model->setAttributes($row);
        $model->applyCasts();
        $model->original = $model->attributes;
        $model->fireModelEvent('retrieved');

        return $model;
    }

    private function applyCasts(): void
    {
        foreach ($this->casts as $attribute => $type) {
            if (!array_key_exists($attribute, $this->attributes)) {
                continue;
            }

            $this->attributes[$attribute] = $this->castValue($this->attributes[$attribute], (string) $type);
        }
    }

    /**
     * Convierte un valor segun el tipo de cast definido en $casts.
     * Tipos soportados: int, float, bool, string, datetime, date,
     * array, json y decimal:N (string con N decimales, estilo Laravel).
     * null siempre se preserva.
     */
    private function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        if (str_starts_with($type, 'decimal:')) {
            $decimales = (int) substr($type, 8);

            return number_format((float) $value, $decimales, '.', '');
        }

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'datetime', 'date' => new \DateTimeImmutable((string) $value),
            'array', 'json' => json_decode((string) $value, true),
            default => $value,
        };
    }

    //******************************************************************
    // VALIDACIONES INTERNAS DEL MODELO
    //******************************************************************

    public function validateModel(): void
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

    //verificar que todos los atributos esten dentro de $fillable
    public function validateColumns(array $data): void
    {
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $this->fillable, true)) {
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

    private function addTimestamps(string $type = 'created'): void
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

    /**
     * Serializa los atributos y las relaciones cargadas respetando $hidden.
     * NO modifica los atributos de la instancia (las llamadas repetidas dan
     * el mismo resultado y el modelo conserva sus valores internos).
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden, true)) {
                $data[$key] = $value;
            }
        }

        //campos calculados de $appends (usan accessors get{Campo}Attribute)
        foreach ($this->appends as $campo) {
            $data[$campo] = $this->{$campo};
        }

        //incluir SOLO las relaciones explicitamente cargadas (with() o acceso previo)
        foreach ($this->relations as $name => $relation) {
            $data[$name] = $relation instanceof ModelCollection
                ? $relation->toArray()
                : ($relation instanceof Model ? $relation->toArray() : $relation);
        }

        return $data;
    }

    public function toObject(): object
    {
        return (object) $this->toArray();
    }

    //******************************************************************
    // ESCRITURA (CREATE, UPDATE, DELETE)
    //******************************************************************

    public static function create(array|object $data): self|null
    {
        $model = new static();
        $model->setAttributes($model->conMutators(is_object($data) ? (array) $data : $data));

        return $model->insertThis() ? $model : null;
    }

    /**
     * Aplica los mutators set{Campo}Attribute a los datos antes de escribir.
     */
    private function conMutators(array $data): array
    {
        foreach ($data as $key => $value) {
            $mutator = 'set' . self::studly((string) $key) . 'Attribute';

            if (method_exists($this, $mutator)) {
                $data[$key] = $this->{$mutator}($value);
            }
        }

        return $data;
    }

    /**
     * Busca el primer registro con los atributos dados. Si no existe,
     * retorna una instancia NUEVA SIN GUARDAR con esos atributos (+extras).
     *
     * Ejemplo: $usuario = Usuario::firstOrNew(['correo' => $correo], ['rol' => 'usuario']);
     */
    public static function firstOrNew(array $atributos, array $extras = []): static
    {
        $query = (new static())->newQuery();

        foreach ($atributos as $columna => $valor) {
            $query->where($columna, $valor);
        }

        $encontrado = $query->first();

        if ($encontrado instanceof static) {
            return $encontrado;
        }

        $nuevo = new static();
        $nuevo->setAttributes($nuevo->conMutators(array_merge($atributos, $extras)));

        return $nuevo;
    }

    /**
     * Busca el primer registro con los atributos dados; si no existe lo crea.
     */
    public static function firstOrCreate(array $atributos, array $extras = []): static
    {
        $modelo = static::firstOrNew($atributos, $extras);

        if ($modelo->{$modelo->primaryKey} === null) {
            $modelo->save();
        }

        return $modelo;
    }

    /**
     * Busca el primer registro con los atributos dados y lo actualiza con
     * $valores; si no existe lo crea con atributos + valores.
     */
    public static function updateOrCreate(array $atributos, array $valores = []): static
    {
        $query = (new static())->newQuery();

        foreach ($atributos as $columna => $valor) {
            $query->where($columna, $valor);
        }

        $existente = $query->first();

        if ($existente instanceof static) {
            $existente->update($valores === [] ? $atributos : $valores);

            return $existente;
        }

        $nuevo = static::firstOrNew($atributos, $valores);
        $nuevo->save();

        return $nuevo;
    }

    /**
     * Actualiza esta instancia por su clave primaria (estilo Eloquent).
     * Los datos deben estar dentro de $fillable.
     *
     *   $publicacion = Publicacion::find(78);
     *   $publicacion->update(['titulo' => 'Nuevo']);
     */
    public function update(array|object $data): bool
    {
        $datos = is_array($data) ? $data : (array) $data;

        return $this->updateThis($this->conMutators($datos));
    }

    /**
     * Elimina esta instancia por su clave primaria.
     * Con SoftDeletes hace borrado logico; sin ellos, fisico.
     */
    public function delete(): bool
    {
        return $this->deleteThis();
    }

    /**
     * Elimina fisicamente esta instancia (ignora SoftDeletes).
     */
    public function forceDelete(): bool
    {
        return $this->forceDeleteThis();
    }

    /**
     * Recupera esta instancia con soft delete (eliminado_en vuelve a NULL).
     */
    public function restore(): bool
    {
        return $this->restoreThis();
    }

    //******************************************************************
    // ESCRITURA POR INSTANCIA (estilo Eloquent)
    //******************************************************************

    /**
     * Guarda el modelo: INSERT si no tiene clave primaria, UPDATE si ya
     * la tiene. Devuelve true si la operacion afecto filas.
     */
    public function save(): bool
    {
        $pk = $this->attributes[$this->primaryKey] ?? null;

        if ($pk === null) {
            return $this->insertThis();
        }

        //solo se envian los atributos que cambiaron desde la hidratacion
        //los atributos no fillable no pueden pasar por validateColumns()
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        unset($dirty[$this->primaryKey]);

        if ($dirty === []) {
            return true; //nada cambio
        }

        return $this->updateThis($dirty);
    }

    /**
     * Refresca los atributos del modelo desde la BD (por su clave primaria).
     * Lanza ModelNotFoundException si el registro ya no existe.
     */
    public function refresh(): static
    {
        $pk = $this->attributes[$this->primaryKey] ?? null;

        if ($pk === null) {
            throw new \Error('El modelo ' . static::class . ' no tiene clave primaria para refrescar.');
        }

        $fresh = static::findOrFail($pk);

        $this->attributes = $fresh->attributes;

        return $this;
    }

    private function insertThis(): bool
    {
        if (!$this->fireModelEvent('saving')) {
            return false;
        }

        if (!$this->fireModelEvent('creating')) {
            return false;
        }

        $this->validateModel();
        $this->validateColumns($this->attributes);
        $this->validateFillableOnAttibutes();
        $this->addTimestamps();

        $sql = "INSERT INTO {$this->table} (" . implode(',', array_keys($this->attributes)) . ') VALUES ('
            . implode(',', array_fill(0, count($this->attributes), '?')) . ')';
        $param = array_values($this->attributes);

        if (self::db()->statementC_U_D($sql, $param) > 0) {
            $this->attributes[$this->primaryKey] = self::db()->lastInsertId();
            $this->applyCasts();
            $this->original = $this->attributes;

            $this->fireModelEvent('created');
            $this->fireModelEvent('saved');

            return true;
        }

        return false;
    }

    private function updateThis(array $data): bool
    {
        if (!$this->fireModelEvent('saving')) {
            return false;
        }

        if (!$this->fireModelEvent('updating')) {
            return false;
        }
        $pk = $this->attributes[$this->primaryKey] ?? null;

        if ($pk === null) {
            throw new \Error('El modelo ' . static::class . ' no tiene clave primaria. Use save() para crear el registro.');
        }

        $this->validateModel();
        $this->validateColumns($data);
        $this->addTimestamps('updated');

        //si el modelo maneja timestamps, la columna updated_at se agrega al SET
        if ($this->timestamps && !array_key_exists($this->updated, $data)) {
            $data[$this->updated] = $this->attributes[$this->updated];
        }

        $sets = [];
        $param = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
            $param[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = ?";

        //los modelos con SoftDeletes no actualizan registros ya eliminados
        if ($this->usesSoftDeletes()) {
            $sql .= " AND {$this->getDeletedAtColumn()} IS NULL";
        }

        $param[] = $pk;

        if (self::db()->statementC_U_D($sql, $param) > 0) {
            $this->setAttributes($data);
            $this->applyCasts();
            $this->original = $this->attributes;

            $this->fireModelEvent('updated');
            $this->fireModelEvent('saved');

            return true;
        }

        return false;
    }

    private function deleteThis(): bool
    {
        $pk = $this->attributes[$this->primaryKey] ?? null;

        if ($pk === null) {
            throw new \Error('El modelo ' . static::class . ' no tiene clave primaria para eliminar.');
        }

        $this->validateModel();

        if (!$this->fireModelEvent('deleting')) {
            return false;
        }

        //soft delete: marca eliminado_en en lugar de borrar la fila
        if ($this->usesSoftDeletes()) {
            $columna = $this->getDeletedAtColumn();
            $marca = date('Y-m-d H:i:s');
            $sql = "UPDATE {$this->table} SET {$columna} = ? WHERE {$this->primaryKey} = ? AND {$columna} IS NULL";

            if (self::db()->statementC_U_D($sql, [$marca, $pk]) > 0) {
                //la instancia refleja el borrado logico (estilo Eloquent)
                $this->attributes[$columna] = $marca;

                $this->fireModelEvent('deleted');

                return true;
            }

            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";

        if (self::db()->statementC_U_D($sql, [$pk]) > 0) {
            $this->fireModelEvent('deleted');

            return true;
        }

        return false;
    }

    private function forceDeleteThis(): bool
    {
        $pk = $this->attributes[$this->primaryKey] ?? null;

        if ($pk === null) {
            throw new \Error('El modelo ' . static::class . ' no tiene clave primaria para eliminar.');
        }

        $this->validateModel();

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";

        if (self::db()->statementC_U_D($sql, [$pk]) > 0) {
            $this->fireModelEvent('forceDeleted');

            return true;
        }

        return false;
    }

    private function restoreThis(): bool
    {
        if (!$this->usesSoftDeletes()) {
            throw new \Error('El modelo ' . static::class . ' no usa el trait SoftDeletes');
        }

        $pk = $this->attributes[$this->primaryKey] ?? null;

        if ($pk === null) {
            throw new \Error('El modelo ' . static::class . ' no tiene clave primaria para restaurar.');
        }

        $this->validateModel();

        if (!$this->fireModelEvent('restoring')) {
            return false;
        }

        $columna = $this->getDeletedAtColumn();
        $sql = "UPDATE {$this->table} SET {$columna} = NULL WHERE {$this->primaryKey} = ? AND {$columna} IS NOT NULL";

        if (self::db()->statementC_U_D($sql, [$pk]) > 0) {
            //la instancia refleja la recuperacion (estilo Eloquent)
            $this->attributes[$columna] = null;

            $this->fireModelEvent('restored');

            return true;
        }

        return false;
    }

    //******************************************************************
    // LECTURA SIMPLE (POR CLAVE PRIMARIA O TABLA COMPLETA)
    //******************************************************************

    public static function find(int|string $id): static|null
    {
        $model = new static();
        $model->validateModel();

        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ?";

        //los modelos con SoftDeletes no ven registros eliminados
        if ($model->usesSoftDeletes()) {
            $sql .= " AND {$model->getDeletedAtColumn()} IS NULL";
        }

        $result = self::db()->statement($sql, [$id]);

        if (count($result) === 0) {
            return null;
        }

        return static::hydrate((array) $result[0]);
    }

    /**
     * Igual que find() pero lanza ModelNotFoundException si no existe.
     */
    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new ModelNotFoundException(
                'No hay resultados para el modelo ' . static::class . ' con ' . (new static())->primaryKey . " = {$id}"
            );
        }

        return $model;
    }

    public static function all(): ModelCollection|null
    {
        $model = new static();
        $model->validateModel();

        $sql = "SELECT * FROM {$model->table}";
        $values = [];

        //los modelos con SoftDeletes no ven registros eliminados
        if ($model->usesSoftDeletes()) {
            $columna = $model->getDeletedAtColumn();
            $sql .= " WHERE {$columna} IS NULL";
        }

        $result = self::db()->statement($sql, $values);

        if (count($result) === 0) {
            return null;
        }

        $arrayModels = array_map([static::class, 'hydrate'], $result);

        return new ModelCollection($arrayModels);
    }

    //******************************************************************
    // SOFT DELETES
    //******************************************************************

    public function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, self::classUsesRecursive(static::class), true);
    }

    public function getDeletedAtColumn(): string
    {
        return property_exists($this, 'deletedAt') ? $this->deletedAt : 'eliminado_en';
    }

    private static function classUsesRecursive(string $class): array
    {
        $uses = class_uses($class) ?: [];

        foreach (class_parents($class) ?: [] as $parent) {
            $uses = array_merge($uses, class_uses($parent) ?: []);
        }

        foreach (array_unique($uses) as $trait) {
            $uses = array_merge($uses, class_uses($trait) ?: []);
        }

        return array_unique($uses);
    }

    //******************************************************************
    // TRANSACCIONES
    //******************************************************************

    /**
     * Ejecuta el callback dentro de una transaccion.
     * Si el callback lanza una excepcion, se hace rollback y se re-lanza.
     * Las llamadas anidadas participan de la transaccion externa.
     */
    public static function transaction(callable $callback): mixed
    {
        $topLevel = self::$transactionLevel === 0;

        if ($topLevel) {
            self::db()->beginTransaction();
        }

        self::$transactionLevel++;

        try {
            $result = $callback();

            if ($topLevel) {
                self::db()->commit();
            }

            self::$transactionLevel--;

            return $result;
        } catch (\Throwable $e) {
            self::$transactionLevel--;

            if ($topLevel) {
                self::db()->rollBack();
            }

            throw $e;
        }
    }

    //******************************************************************
    // ACCESO AL QUERY BUILDER
    //******************************************************************

    /**
     * Crea un QueryBuilder nuevo para este modelo.
     * Cada consulta tiene su propio builder: el estado nunca se comparte.
     */
    public function newQuery(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    public function __call(string $method, array $arguments): mixed
    {
        //save() y refresh() son metodos reales de instancia. update(),
        //delete(), forceDelete() y restore() existen como estaticos pero
        //PHP liga $this al invocarlos via ->, por lo que atendian ambos
        //contextos sin pasar por aqui.
        return $this->newQuery()->{$method}(...$arguments);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return (new static())->newQuery()->{$method}(...$arguments);
    }

    /**
     * Consulta cruda parametrizada (compatible con versiones anteriores).
     */
    public static function customQuery(string $query, array|object $data = []): array|object
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        return self::db()->statement($query, $data);
    }

    //******************************************************************
    // RELACIONES
    //******************************************************************

    public function hasOne(string $related, ?string $foreignKey = null, string $localKey = 'id'): HasOne
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->table . '_id';

        return new HasOne($instance, $this, $foreignKey, $localKey);
    }

    public function hasMany(string $related, ?string $foreignKey = null, string $localKey = 'id'): HasMany
    {
        $foreignKey = $foreignKey ?: $this->table . '_id';

        return new HasMany(new $related(), $this, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, string $ownerKey = 'id'): BelongsTo
    {
        $foreignKey = $foreignKey ?: (new $related)->primaryKey;

        return new BelongsTo(new $related(), $this, $foreignKey, $ownerKey);
    }

    public function belongsToMany(
        string $related,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey
    ): BelongsToMany {
        return new BelongsToMany(new $related(), $this, $pivotTable, $foreignPivotKey, $relatedPivotKey);
    }
}
