# Modelos

Todos los modelos se ubican en `App/Models/` y heredan de `Cronos\Model\Model`.

## Convencion de Nombres

- Nombre en **PascalCase**, singular
- Archivo: `App/Models/User.php`
- Clase: `class User extends Model`
- Namespace: `namespace App\Models;`

## Definicion de un Modelo

```php
<?php

namespace App\Models;

use Cronos\Model\Model;

class User extends Model
{
    protected string $table = 'users';            // Nombre de la tabla (obligatorio)
    protected string $primaryKey = 'id';          // Clave primaria (obligatorio)
    protected array $fillable = ['name', 'email', 'password']; // Campos asignables
    protected array $hidden = ['password'];       // Campos ocultos en toArray/toObject
    protected bool $timestamps = true;            // Habilita timestamps automaticos
    protected string $created = 'created_at';    // Campo created
    protected string $updated = 'updated_at';    // Campo updated
}
```

> **Importante**: Todos los modelos deben definir `$table` y `$primaryKey`, de lo contrario lanzara error.

## CRUD Basico

### Guardar Datos

```php
$user = User::create([
    'name' => 'name',
    'email' => 'email',
    'password' => 'password'
]);

// Retorna el objeto guardado con el id
```

### Actualizar Datos

```php
$user = User::update($id, [
    'name' => 'nuevo nombre',
    'email' => 'nuevo email'
]);

// Retorna el objeto actualizado o false/"0" si falla
```

### Eliminar Datos

```php
User::delete($id);

// Retorna un booleano
```

### Buscar por ID

```php
$user = User::find($id);
```

### Obtener Todos

```php
$users = User::all();
```

## Query Builder

Los metodos se encadenan y deben terminar con `->get()` o `->first()`.

```php
User::select()
    ->join()
    ->where()
    ->andWhere()
    ->orWhere()
    ->orderBy()
    ->limit()
    ->get();
```

> **Nota**: `all()` y `find()` no se pueden anidar. `first()` y `get()` solo funcionan en anidaciones.

### Select

```php
// Seleccionar columnas especificas
User::select('name', 'email')->get();
```

### Join

```php
User::join('nombreOtraTabla', 'clientes.id', '=', 'ventas.cliente_id')->get();
```

### Where

```php
// Dos parametros: busca por igualdad
User::where('column', 'valueColumn')->get();

// Tres parametros: busca por operador
User::where('column', 'operador', 'valueColumn')->get();
```

### AndWhere / OrWhere

```php
// AND: condicion adicional
User::where('column', 'valueColumn')->andWhere('colum', 'valueColumn')->get();
User::where('column', 'operador', 'valueColumn')->andWhere('column', 'operador', 'valueColumn')->get();

// OR: condicion alternativa
User::where('column', 'valueColumn')->orWhere('colum', 'valueColumn')->get();
User::where('column', 'operador', 'valueColumn')->orWhere('columm', 'operador', 'valueColumn')->get();
```

### WhereBetween

```php
User::whereBetween('column', 'valueMin', 'valueMax')->get();
```

### WhereConcat

```php
// Dos parametros
User::whereConcat('column1 - column2', 'value')->get();

// Tres parametros
User::whereConcat('column1 - column2', 'operador', 'value')->get();
```

> **Nota**: `where()` puede ser reemplazado por `whereBetween()` o `whereConcat()`, pero no se pueden anidar entre si. `andWhere()` y `orWhere()` funcionan con cualquiera de los tres.

### OrderBy

```php
User::orderBy('column', 'desc')->get(); // 'asc' o 'desc'
```

### Limit

```php
User::limit(10)->get();
```

### First

```php
// Retorna el primer resultado
$user = User::where('email', 'test@test.com')->first();
```

## Funciones de Agregacion

```php
User::select('views')->max();
User::select('views')->min();
User::select('views')->sum();
User::select('views')->avg();
```

## Ejemplos de Consultas

```php
// Todos los datos ordenados descendente
User::orderBy('id', 'desc')->get();

// Ordenados con limite
User::orderBy('id', 'asc')->limit(10)->get();

// Con select de columnas especificas
User::select('name', 'email')->orderBy('id', 'asc')->limit(10)->get();

// Con join y where
User::select('name', 'email', 'roles.name')
    ->join('roles', 'users.id', '=', 'roles.user_id')
    ->where('roles.id', 1)
    ->andWhere('users.id', 1)
    ->orderBy('id', 'asc')
    ->limit(10)
    ->get();

// Obtener blogs de un usuario
$blogs = Blog::select('blogs.*', 'users.name as author')
    ->join('users', 'blogs.user_id', '=', 'users.id')
    ->where('users.id', '1')
    ->orderBy('blogs.created_at', 'DESC')
    ->get();
```

## Depuracion de Consultas

```php
// No ejecuta la consulta, muestra la sentencia SQL y los datos
$posts = Blog::select('blogs.title', 'users.email')
    ->join('users', 'blogs.user_id', '=', 'users.id')
    ->where('blogs.content', 'LIKE', '%esta%')
    ->limit(5)
    ->dd();
```

## Visualizar Datos de Resultados

```php
$data = User::all();

// Ver por propiedades
$data->name;

// Convertir a array
$data->toArray();

// Convertir a objeto
$data->toObject();

// Generar JSON para respuesta
return json($data);
```

## Consultas Personalizadas (SQL Directo)

```php
// Desde el modelo, definir un metodo con SQL personalizado
public static function getVentasEstado($estado)
{
    $sql = "SELECT * FROM ventas WHERE estado = ?";
    return self::statement($sql, [$estado]);
}
```

## Relaciones

```php
// HasOne: relacion 1:1
public function profile()
{
    return $this->hasOne(Profile::class, 'user_id');
}

// HasMany: relacion 1:N
public function blogs()
{
    return $this->hasMany(Blog::class, 'user_id');
}

// BelongsTo: relacion N:1
public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

// BelongsToMany: relacion N:M
public function roles()
{
    return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
}
```

## ModelCollection

Las consultas que retornan multiples registros devuelven un `ModelCollection` con los siguientes metodos:

| Metodo | Descripcion | Ejemplo |
|---|---|---|
| `map(callable $cb)` | Aplica funcion a cada elemento | `$collection->map(fn($b) => $b->title)` |
| `filter(callable $cb)` | Filtra elementos con callback | `$collection->filter(fn($b) => $b->status === 'published')` |
| `first()` | Primer elemento | `$blogs->first()` |
| `last()` | Ultimo elemento | `$blogs->last()` |
| `count()` | Cantidad de elementos | `$blogs->count()` |
| `toArray()` | Convierte todos a array | `$blogs->toArray()` |
| `toObject()` | Convierte todos a objeto | `$blogs->toObject()` |
| `toJson()` | Convierte a JSON | `$blogs->toJson()` |
| `pluck(string $key)` | Extrae una propiedad de todos | `$blogs->pluck('title')` |
| `getIterator()` | Iterador para foreach | `foreach ($col as $item) { ... }` |

## Ejemplo Real

**App/Models/User.php:**
```php
<?php

namespace App\Models;

use Cronos\Model\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected bool $timestamps = true;
    protected string $created = 'created_at';
    protected string $updated = 'updated_at';

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'user_id');
    }
}
```

**App/Models/Blog.php:**
```php
<?php

namespace App\Models;

use Cronos\Model\Model;

class Blog extends Model
{
    protected string $table = 'blogs';
    protected string $primaryKey = 'id';
    protected array $fillable = ['title', 'slug', 'content', 'user_id'];
    protected bool $timestamps = true;
    protected string $created = 'created_at';
    protected string $updated = 'updated_at';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

## Lo que NO Soporta (vs Eloquent)

- Migraciones individuales (solo archivo unico)
- Factories y Seeders
- Scopes
- Accessors y Mutators
- Casting de tipos automaticos
- Soft Deletes
- Events de modelo
- Observers
- Eager Loading (`with`)
- Polymorphic relations
- Subqueries
- Paginacion

---

> **Anterior**: [03 - Controladores](03-controladores.md)
> **Siguiente**: [05 - Validaciones](05-validaciones.md)
