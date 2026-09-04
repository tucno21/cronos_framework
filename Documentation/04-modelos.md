# Modelos

> **AVISO IMPORTANTE PARA DESARROLLADORES E IAs**
>
> El ORM de Cronos **NO es Eloquent**. El uso es parecido a proposito, pero el comportamiento exacto (retorno null vs coleccion vacia, `$fillable` obligatorio en `create()`, etc.) es propio de Cronos y esta verificado con tests (ver `tests/Integration/OrmQueryBuilderTest.php`, `tests/Integration/OrmFeaturesTest.php` y `tests/Integration/OrmEloquentStyleTest.php`).
>
> **Si eres una IA: NO supongas comportamiento de Laravel/Eloquent. Basate UNICAMENTE en esta documentacion y en el codigo de `System/Model/Model.php` y `System/Model/QueryBuilder.php`.**

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

> **CAMBIO IMPORTANTE (estilo Eloquent)**: `update()`, `delete()`, `forceDelete()` y `restore()` ahora son **metodos de instancia**. Los antiguos estaticos `User::update($id, [...])`, `User::delete($id)`, `User::forceDelete($id)` y `User::restore($id)` **ya NO existen**.

### Guardar Datos

```php
$user = User::create([
    'name' => 'name',
    'email' => 'email',
    'password' => 'password'
]);

// Retorna el objeto guardado con el id
```

### Actualizar Datos (por instancia)

```php
$user = User::find($id);
$user->update([
    'name' => 'nuevo nombre',
    'email' => 'nuevo email'
]);

// Retorna true si afecto filas. Los campos deben estar en $fillable.
// Agrega updated_at si $timestamps esta activo. Respeta SoftDeletes.
```

### Eliminar Datos (por instancia)

```php
$user = User::find($id);
$user->delete();          // true si afecto filas (soft delete si el modelo lo usa)

$user->forceDelete();     // DELETE fisico (ignora SoftDeletes)
$user->restore();         // recupera un soft delete (requiere el trait)
```

### Guardar Cambios con save()

```php
//INSERT: instancia sin clave primaria
$user = new User();
$user->name = 'Nuevo';
$user->email = 'nuevo@test.com';
$user->password = 'secreto';
$user->save();            //requiere TODOS los $fillable asignados

//UPDATE: instancia ya cargada, SOLO envia los atributos modificados
$user = User::find($id);
$user->name = 'Editado';
$user->save();            //UPDATE con solo las columnas cambiadas

$user->refresh();         //relee los atributos desde la BD
                          //lanza ModelNotFoundException si el registro ya no existe
```

### Buscar por ID

```php
$user = User::find($id);          //null si no existe
$user = User::findOrFail($id);    //lanza ModelNotFoundException si no existe
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
//Estilo legacy (una sola columna via select):
User::select('views')->max();
User::select('views')->min();
User::select('views')->sum();
User::select('views')->avg();

//Estilo directo (columna como argumento):
User::sum('views');
User::avg('views');
User::max('views');
User::min('views');
```

## Shortcuts Utiles

```php
//Valor de una columna del primer registro (null si no hay)
$nombre = User::where('email', 'a@b.com')->value('name');

//Existencia de registros (respeta SoftDeletes y wheres)
User::where('rol', 'admin')->exists();        //bool
User::where('rol', 'admin')->doesntExist();   //bool

//Orden por created_at (o la columna indicada)
User::latest()->first();                 //ORDER BY created_at DESC
User::oldest('created_at')->first();     //ORDER BY created_at ASC

//Sin resultados lanza ModelNotFoundException
$primer = User::where('activo', 1)->firstOrFail();
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

## has(), whereHas() y withCount()

### Filtrar por existencia de relacion

```php
//publicaciones con AL MENOS 1 comentario (subconsulta EXISTS, sin N+1)
$publicaciones = Publicacion::has('comentarios')->get();

//con cantidad minima (operadores: =, !=, <>, >, >=, <, <=)
$populares = Publicacion::has('comentarios', '>=', 3)->get();

//con condiciones sobre la relacion
$activas = Publicacion::whereHas('comentarios', fn ($q) => $q->where('activo', 1))->get();

//combinar cantidad y condiciones
$frecuentes = Publicacion::whereHas('comentarios', fn ($q) => $q->where('activo', 1), '>=', 5)->get();

//funciona con todos los tipos de relacion
Usuario::has('roles')->get();                                        //belongsToMany (via pivote)
Comentario::whereHas('usuario', fn ($q) => $q->where('rol', 'admin'))->get();  //belongsTo
```

- El closure de `whereHas()` recibe el QueryBuilder del modelo relacionado y NO debe terminarlo (solo condiciones).
- El filtro de soft deletes del modelo relacionado se aplica automaticamente en la subconsulta.

### Conteos de relaciones (sin N+1)

```php
//1 consulta extra total: cada publicacion queda con comentarios_count (int)
$publicaciones = Publicacion::withCount('comentarios')->get();
$publicaciones->first()->comentarios_count;   //0 si no tiene

//incluido en toArray()/toObject()/toJson()
$publicaciones->first()->toArray()['comentarios_count'];

//con condiciones sobre la relacion
Usuario::withCount(['publicaciones' => fn ($q) => $q->where('estado', 'publicado')])->get();

//combina con has()/whereHas()/with()/where() en la misma consulta
Publicacion::has('comentarios')->withCount('comentarios')->with('usuario')->get();
```

- Funciona en `get()` y `first()`. El atributo se llama `{relacion}_count`.
- Soporta hasOne/hasMany, belongsTo y belongsToMany.

## Escritura de Pivotes (attach / detach / sync)

Para relaciones `belongsToMany`, el objeto de relacion permite escribir la tabla pivote:

```php
//ATTACH: agrega ids al pivote (duplicados ignorados); retorna filas insertadas
$publicacion->etiquetas()->attach(3);
$publicacion->etiquetas()->attach([1, 2, 3]);

//DETACH: elimina ids del pivote; retorna filas eliminadas
$publicacion->etiquetas()->detach(3);       //solo la 3
$publicacion->etiquetas()->detach([1, 2]);  //las 1 y 2
$publicacion->etiquetas()->detach();        //TODAS las de esta publicacion

//SYNC: sincroniza el pivote con la lista exacta
$resultado = $usuario->roles()->sync([2, 3]);
// $resultado = ['attached' => [3], 'detached' => [1], 'updated' => []]

$usuario->roles()->sync([]);   //desasocia todo (equivale a detach() sin argumentos)
```

- Requieren que el modelo padre tenga clave primaria (si no, lanzan `Error`).
- El pivote queda reflejado al volver a consultar la relacion (`$publicacion->etiquetas()->get()`).
- No hay columnas extra de pivote ni `withTimestamps` (pivotes simples: 2 claves).

## firstOrNew / firstOrCreate / updateOrCreate

```php
//busca por los atributos; si no existe retorna instancia NUEVA SIN GUARDAR
$usuario = Usuario::firstOrNew(['correo' => $correo], ['rol' => 'usuario']);

//busca; si no existe lo crea (con atributos + extras)
$usuario = Usuario::firstOrCreate(['correo' => $correo], $datosCompletos);

//busca; si existe lo actualiza con $valores; si no, crea con atributos + valores
$usuario = Usuario::updateOrCreate(
    ['correo' => $correo],
    ['nombre' => 'Nuevo Nombre']
);
```

- El primer array son las condiciones de busqueda; el segundo los datos.
- `updateOrCreate()` dispara los eventos `updating/updated` al actualizar.
- Respetan SoftDeletes: los registros borrados no se encuentran (se crea uno nuevo).

## Guardar a traves de Relaciones (create / save / associate)

### hasOne y hasMany: create() y save()

`create()` agrega automaticamente la clave foranea del padre a los datos (que deben cumplir las reglas de `create()` del modelo relacionado, es decir, todos los `$fillable` presentes):

```php
$usuario = Usuario::find(1);

$publicacion = $usuario->publicaciones()->create([
    'titulo' => 'Nueva',
    'slug' => 'nueva',
    'contenido' => 'contenido',
]);   //usuario_id asignado automaticamente

$perfil = $usuario->perfil()->create([
    'biografia' => '...',
    'telefono' => '...',
    'fecha_nacimiento' => '...',
    'sitio_web' => null,
]);   //hasOne tambien soporta create()
```

`save()` persiste un modelo ya construido asignandole antes la clave foranea:

```php
$publicacion = new Publicacion();
$publicacion->titulo = 'Guardada';
$publicacion->slug = 'guardada';
$publicacion->contenido = 'contenido';

$usuario->publicaciones()->save($publicacion);   //asigna usuario_id y persiste
```

### belongsTo: associate() y dissociate()

`associate()` asigna la clave foranea (con un modelo o un id) y retorna el modelo padre para encadenar `->save()`. `dissociate()` la limpia a NULL en memoria:

```php
$comentario = new Comentario();
$comentario->contenido = 'Mi comentario';

$comentario->publicacion()->associate($publicacion)->save();   //con modelo
$comentario->usuario()->associate(5);                          //con id

$comentario->publicacion()->dissociate();   //publicacion_id = null en memoria
```

> **Nota**: `dissociate()` escribe NULL en memoria; si la columna es NOT NULL el `save()` fallara a nivel BD (igual que en Eloquent, el responsable es el esquema).

## Eventos de Modelo y Observers

El modelo dispara eventos de ciclo de vida. Registratelos en el hook `booted()` (se ejecuta una vez por clase):

```php
class Publicacion extends Model
{
    protected static function booted(): void
    {
        static::creating(function ($publicacion) {
            $publicacion->slug = strtolower($publicacion->slug);
            //retornar false DETIENE la operacion
        });

        static::created(fn ($p) => Log::info('creada', ['id' => $p->id]));
    }
}
```

Eventos disponibles y orden en `save()`:

| Momento | Eventos (en orden) |
|---|---|
| INSERT | `saving` -> `creating` -> (insert) -> `created` -> `saved` |
| UPDATE | `saving` -> `updating` -> (update) -> `updated` -> `saved` |
| DELETE | `deleting` -> (delete) -> `deleted` |
| RESTORE | `restoring` -> (restore) -> `restored` |
| FORCE DELETE | (delete) -> `forceDeleted` |
| LECTURA (find/get/first) | `retrieved` |

- Si un listener de `saving/creating/updating/deleting/restoring` retorna `false`, la operacion se aborta (`create()` retorna null, `save/update/delete/restore()` retornan false).
- Los metodos estaticos para registrar tienen el nombre del evento: `Publicacion::deleting(fn ($p) => ...)`.

### Observers

```php
//App/Observers/PublicacionObserver.php
class PublicacionObserver
{
    public function created($publicacion): void { /* ... */ }
    public function updated($publicacion): void { /* ... */ }
    public function deleted($publicacion): void { /* ... */ }
}

//en booted():
Publicacion::observe(PublicacionObserver::class);
```

### Utilidades para tests

```php
Publicacion::flushEventListeners();  //elimina listeners estaticos y re-ejecuta booted() en la proxima instancia
```

## Paginacion

```php
$pagina = Publicacion::where('estado', 'publicado')->paginate(15, (int) ($_GET['page'] ?? 1));

$pagina->total;           //registros totales con el filtro aplicado
$pagina->porPagina;
$pagina->paginaActual;
$pagina->ultimaPagina;
$pagina->desde();         //indice del primer item (null si la pagina esta vacia)
$pagina->hasta();         //indice del ultimo item (null si la pagina esta vacia)
$pagina->items;           //ModelCollection (soporta with() y withCount())
$pagina->toArray();       //['data' => [...], 'total' =>, 'por_pagina' =>, 'pagina_actual' =>, 'ultima_pagina' =>, 'desde' =>, 'hasta' =>]

foreach ($pagina as $publicacion) { ... }   //iterable y Countable
```

- Ejecuta un COUNT + el SELECT de la pagina; respeta wheres/joins/soft deletes.
- Si la pagina pedida queda fuera de rango, `items` viene vacio y `total` conserva el conteo real.

## Accessors, Mutators y $appends

```php
class Publicacion extends Model
{
    //campos calculados incluidos en toArray()/toJson()
    protected array $appends = ['titulo_mayuscula'];

    //ACCESSOR: transformacion en LECTURA (get{Campo}Attribute)
    public function getTituloMayusculaAttribute(?string $valor): ?string
    {
        $titulo = $valor ?? $this->attributes['titulo'] ?? null;

        return $titulo === null ? null : mb_strtoupper($titulo);
    }

    //MUTATOR: transformacion en ESCRITURA (set{Campo}Attribute)
    public function setTituloAttribute(?string $valor): ?string
    {
        return $valor === null ? null : mb_strtolower(trim($valor));
    }
}
```

- `$publicacion->titulo_mayuscula` dispara el accessor en lectura (y funciona aunque no exista columna; `null` si no hay valor detras).
- El mutator se aplica en `create()`, `save()`, `update()` y `firstOrNew()` (no al hidratar desde la BD).
- `$appends` agrega los campos calculados a `toArray()`/`toObject()`/`toJson()`.

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
| `first(?callable $cb)` | Primer elemento (o el primero que cumpla) | `$blogs->first(fn($b) => $b->vistas > 10)` |
| `last(?callable $cb)` | Ultimo elemento (o el ultimo que cumpla) | `$blogs->last()` |
| `count()` | Cantidad de elementos | `$blogs->count()` |
| `each(callable $cb)` | Ejecuta callback por item, retorna la coleccion | `$blogs->each(fn($b) => ...)` |
| `values()` | Reindexa (0,1,2...) | `$blogs->filter(...)->values()` |
| `contains($key, $val)` | Si contiene un valor o cumple un callback | `$blogs->contains('slug', 'x')` / `$blogs->contains(fn($b) => ...)` |
| `sum(string $key)` | Suma una columna (null cuenta 0) | `$blogs->sum('vistas')` |
| `avg(string $key)` | Promedio de una columna | `$blogs->avg('vistas')` |
| `min(string $key)` | Minimo de una columna | `$blogs->min('vistas')` |
| `max(string $key)` | Maximo de una columna | `$blogs->max('vistas')` |
| `groupBy(string $key)` | Agrupa: `array<string, ModelCollection>` | `$blogs->groupBy('estado')['borrador']` |
| `sortBy(string $key, bool $desc)` | Ordena por columna | `$blogs->sortBy('titulo')` |
| `sortByDesc(string $key)` | Ordena descendente | `$blogs->sortByDesc('vistas')` |
| `toArray()` | Convierte todos a array | `$blogs->toArray()` |
| `toObject()` | Convierte todos a objeto | `$blogs->toObject()` |
| `toJson()` | Convierte a JSON (respeta $hidden) | `$blogs->toJson()` |
| `pluck(string $key)` | Extrae una propiedad de todos | `$blogs->pluck('title')` |
| `load(string ...$rel)` | Carga relaciones en cada item | `$blogs->load('usuario')` |
| `isEmpty()` / `isNotEmpty()` | Vaciedad | `$blogs->isNotEmpty()` |
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

## Transacciones

```php
use Cronos\Model\Model;

Model::transaction(function () {
    $usuario = Usuario::create([...]);
    Perfil::create(['usuario_id' => $usuario->id, ...]);

    return $usuario;
});

//Si el callback lanza una excepcion se hace rollback automatico y la
//excepcion se re-lanza. Las llamadas anidadas participan de la transaccion externa.

//Alternativa via contenedor (para codigo fuera de los modelos):
use Cronos\Database\DBexecute;

DBexecute::transaction(fn () => Usuario::create([...]));
```

## Condiciones Adicionales

```php
Usuario::whereIn('correo', [$a, $b])->get();          //requiere al menos un valor
Usuario::whereNotIn('id', [1, 2, 3])->get();          //requiere al menos un valor
Usuario::whereNull('invitado_por')->get();
Usuario::whereNotNull('invitado_por')->first();
```

## count y offset (Paginacion Manual)

```php
$total = Usuario::where('rol', 'usuario')->count();   //retorna int

//pagina 2 de 10 registros:
$pagina = Usuario::orderBy('id')->limit(10)->offset(10)->get();
```

## update y delete Encadenables

```php
//actualiza todos los registros que cumplen la condicion; retorna int (filas afectadas)
Publicacion::where('estado', 'borrador')->update(['estado' => 'archivado']);

//elimina los registros que cumplen la condicion; retorna int
Publicacion::where('vistas', 0)->delete();

//Ambos EXIGEN al menos un where() previo (proteccion contra
//actualizaciones/borrados masivos accidentales) y validan que las
//columnas esten en $fillable. update() agrega updated_at si $timestamps esta activo.
```

## Eager Loading (with)

Evita el problema N+1 cargando relaciones con 1 consulta extra por relacion:

```php
//lazy (1 query por registro): N+1
foreach ($publicaciones as $p) {
    $p->usuario()->get();
}

//eager (2 queries totales):
$publicaciones = Publicacion::with('usuario', 'comentarios')->get();

foreach ($publicaciones as $p) {
    $p->getRelation('usuario');     //Model ya cargado
    $p->relationLoaded('usuario');  //true
}
```

### Eager Loading Anidado (dot notation)

```php
//publicacion -> usuario -> perfil (2 niveles)
$publicacion = Publicacion::with('usuario.perfil')->first();
$publicacion->usuario->getRelation('perfil')->biografia;

//3 niveles
Usuario::with('invitadoPor.perfil.telefono')->get();  //cada nivel debe existir como metodo

//arbol: usuario carga perfil E invitadoPor, cada uno con 1 query
Publicacion::with('usuario.perfil', 'usuario.invitadoPor')->get();

//desde hasMany tambien
Usuario::with('publicaciones.comentarios')->get();
```

Si una relacion anidada no existe como metodo en el modelo relacionado, lanza `Error` al ejecutar la consulta.

### Constraints (closures) en with()

El closure recibe el QueryBuilder del modelo relacionado y NO debe terminarlo (sin `get()`/`first()`):

```php
//filtrar la relacion cargada
$usuario = Usuario::with(['publicaciones' => fn ($q) => $q->where('estado', 'publicado')])->first();
$usuario->publicaciones;   //solo las publicadas

//ordenar o limitar columnas
Publicacion::with([
    'comentarios' => fn ($q) => $q->orderBy('id', 'DESC'),
    'usuario'     => fn ($q) => $q->select('id', 'nombre', 'correo'),
])->get();

//combinar constraints y anidado en el mismo arbol
Publicacion::with([
    'usuario' => fn ($q) => $q->where('rol', 'usuario'),
    'usuario.perfil',
])->get();

//arrays mixtos: strings y closures juntos
Usuario::with(['perfil', 'publicaciones' => fn ($q) => $q->latest()])->get();
```

> **Limitacion**: los closures NO estan soportados en relaciones `belongsToMany` (lanza `Error`). Los constraints no pueden usar `whereBetween()`/`whereConcat()` si agregan `where()` (mismas reglas del builder).

Tambien hay **acceso magico** con cache en la instancia (la primera carga consulta la BD, las siguientes usan la cache):

```php
$publicacion = Publicacion::find(1);
$autor = $publicacion->usuario;      //Model o null (lazy, cacheado)
$lista = $publicacion->comentarios;  //ModelCollection o null
```

Las relaciones cargadas se incluyen en `toArray()`/`toObject()`. Las relaciones soportadas por `with()` son `hasOne`, `hasMany`, `belongsTo` y `belongsToMany`.

En una coleccion: `$publicaciones->load('usuario')` carga la relacion en cada item.

## Casts de Tipos (opt-in)

Todo lo que llega de PDO puede venir como string. Con `$casts` el modelo convierte los tipos al hidratar (find, all, get, first):

```php
class Publicacion extends Model
{
    protected array $casts = [
        'usuario_id' => 'int',               //int|integer, float|double|real, bool|boolean, string
        'vistas'     => 'decimal:2',         //string con 2 decimales: "123.00"
        'created_at' => 'datetime',          //DateTimeImmutable ('date' tambien existe)
        'metadata'   => 'array',             //json_decode a array ('json' es equivalente)
    ];
}
```

- Los valores null se preservan (no se convierten).
- `decimal:N` retorna un string formateado con N decimales (igual que Eloquent).
- `datetime`/`date` retornan `DateTimeImmutable`.
- Sin `$casts` el comportamiento es identico al de siempre (sin conversion).

## Timestamps

```php
class User extends Model
{
    protected bool $timestamps = true;   //false: create()/save() no tocan timestamps

    //columnas personalizables (por defecto las constantes del modelo base):
    protected string $created = Model::CREATED_AT;  //'created_at'
    protected string $updated = Model::UPDATED_AT;  //'updated_at'
}
```

Con `$timestamps = false` los INSERT/UPDATE no incluyen `created_at` ni `updated_at`.

## Prevencion de N+1 (preventLazyLoading)

En desarrollo puedes bloquear el lazy loading para detectar consultas N+1:

```php
Model::preventLazyLoading();     //activa la deteccion (tipicamente en un provider o bootstrap)

$publicacion = Publicacion::first();
$publicacion->usuario;           //lanza Error: "Carga perezosa (N+1) bloqueada..."

Publicacion::with('usuario')->first()->usuario;   //el eager loading SI funciona

Model::preventLazyLoading(false); //desactiva
Model::isLazyLoadingPrevented();  //consulta el estado
```

## toArray() y $hidden

`toArray()`/`toObject()`/`toJson()` excluyen los campos de `$hidden` **sin destruir** los atributos de la instancia: serializar no altera el modelo, las llamadas repetidas dan el mismo resultado y el modelo sigue siendo utilizable despues (`update()`, `save()`, etc.). Los modelos anidados en relaciones aplican su propio `$hidden`.

## Soft Deletes (opt-in)

Con el trait `SoftDeletes`, el modelo marca `eliminado_en` en lugar de borrar y todas las consultas filtran automaticamente los registros eliminados:

```php
use Cronos\Model\Model;
use Cronos\Model\SoftDeletes;

class Publicacion extends Model
{
    use SoftDeletes;

    //columna personalizable (por defecto la constante DELETED_AT = 'eliminado_en'):
    //protected string $deletedAt = 'borrado_en';
}
```

```php
//POR INSTANCIA (estilo Eloquent):
$publicacion = Publicacion::find($id);
$publicacion->delete();          //UPDATE eliminado_en (borrado logico); $publicacion->trashed() pasa a true
$publicacion->forceDelete();     //DELETE fisico
$publicacion->restore();         //vuelve a NULL; trashed() pasa a false

//POR CONSULTA (encadenables):
Publicacion::where('vistas', 0)->delete();              //borrado logico; retorna int
Publicacion::onlyTrashed()->where('id', $id)->restore(); //restaura; retorna int
Publicacion::withTrashed()->where('id', $id)->delete();  //borrado FISICO aunque haya trait

//CONSULTAS:
Publicacion::find($id);              //null si esta eliminado
Publicacion::all();                  //excluye eliminados
Publicacion::where(...)->get();      //excluye eliminados
Publicacion::withTrashed()->get();   //incluye eliminados
Publicacion::onlyTrashed()->get();   //SOLO eliminados

$publicacion->trashed();             //true si eliminado_en NO es null
```

- `withTrashed()`/`onlyTrashed()`/`restore()` en un modelo sin el trait lanzan `Error`.
- Requiere la columna en la tabla (`$table->softDeletes('eliminado_en')` en la migracion).

## Seguridad del Query Builder

El ORM **parametriza los valores** y ademas **valida los identificadores** (columnas, tablas, operadores) para evitar inyeccion SQL:

- Columnas/tablas validas: letras, digitos, `_` y punto (`tabla.columna`). Cualquier otro caracter lanza `Error`.
- Operadores permitidos en `where()` de 3 argumentos: `=`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `LIKE`, `NOT LIKE`.
- Nunca pases columnas desde input del usuario.

## Lo que NO Soporta (vs Eloquent)

- Scopes de query (`scopeActivos()`)
- Closures en `with()` para relaciones `belongsToMany` (el resto si las soporta)
- Columnas extra en pivotes y `withTimestamps` (pivotes de 2 claves; usar SQL directo para extras)
- `toggle()` y `syncWithoutDetaching()` en pivotes
- `$modelo->relacion()->create()`/`save()` en `belongsToMany` (solo en hasOne/hasMany)
- Relaciones `hasManyThrough` y morfologicas (`morphOne`/`morphMany`/`morphTo`; las tablas `comentables`/`etiquetables` se manejan con SQL directo)
- Subqueries en `where()` (las de `has()`/`whereHas()` son generadas por el ORM)
- `chunk()`/`cursor()` para recorrer datasets grandes
- Eventos en `update()`/`delete()` por query builder (solo se disparan en operaciones por instancia)

---

> **Anterior**: [03 - Controladores](03-controladores.md)
> **Siguiente**: [05 - Validaciones](05-validaciones.md)
