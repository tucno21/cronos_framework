# Modelos y ORM Cronos — Guia completa

> **AVISO CRITICO PARA DESARROLLADORES E IAs**
>
> El ORM de Cronos **NO es Laravel Eloquent**. Se inspiro en Eloquent a proposito, pero
> tiene diferencias de comportamiento que rompen codigo escrito "a lo Laravel".
>
> **LAS 5 REGLAS DE ORO (memorizalas antes de escribir codigo):**
>
> 1. `get()` y `all()` retornan **`null`** cuando no hay filas (Eloquent retorna una
>    coleccion vacia). Compara siempre con `null`, nunca con `->count() === 0` sin antes
>    verificar que no es null.
> 2. `$fillable` son los campos **OBLIGATORIOS** en `create()` y `save()`: si falta
>    uno, lanza `Error`. En Eloquent solo son "permitidos"; en Cronos son requeridos.
> 3. No existe `Carbon`. Los casts `datetime`/`date` retornan `DateTimeImmutable`.
> 4. Los estaticos `User::update($id, ...)`, `User::delete($id)` y `User::restore($id)`
>    **NO existen**. Todo cambio es por instancia: `$user->update([...])`, `$user->delete()`.
> 5. Los identificadores (columnas, tablas, operadores) se **validan con regex y
>    whitelist**: cualquier caracter raro o operador no permitido lanza `Error` (no
>    inyecta silenciosamente).
>
> Si eres una IA: **NO supongas comportamiento de Laravel/Eloquent.** Basate UNICAMENTE
> en este documento y en el codigo de `System/Model/Model.php` y
> `System/Model/QueryBuilder.php`. Cada feature de esta guia esta cubierta por tests de
> integracion contra MySQL real (carpeta `tests/Integration/`).

## Indice

1. [Definir un modelo](#1-definir-un-modelo)
2. [CRUD por instancia](#2-crud-por-instancia)
3. [Query Builder: consultas](#3-query-builder-consultas)
4. [Relaciones: definir y leer](#4-relaciones-definir-y-leer)
5. [Eager Loading (with)](#5-eager-loading-with)
6. [Filtrar por relaciones: has / whereHas / withCount](#6-filtrar-por-relaciones-has--wherehas--withcount)
7. [Escribir a traves de relaciones](#7-escribir-a-traves-de-relaciones)
8. [Pivotes: attach / detach / sync](#8-pivotes-attach--detach--sync)
9. [firstOrNew / firstOrCreate / updateOrCreate](#9-firstornew--firstcreate--updateorcreate)
10. [Eventos de modelo y Observers](#10-eventos-de-modelo-y-observers)
11. [Casts de tipos](#11-casts-de-tipos)
12. [Accessors, Mutators y $appends](#12-accessors-mutators-y-appends)
13. [Soft Deletes](#13-soft-deletes)
14. [Timestamps](#14-timestamps)
15. [Paginacion](#15-paginacion)
16. [Transacciones](#16-transacciones)
17. [ModelCollection](#17-modelcollection)
18. [SQL directo y depuracion](#18-sql-directo-y-depuracion)
19. [Seguridad](#19-seguridad)
20. [Combinaciones: que se puede mezclar y que NO](#20-combinaciones-que-se-puede-mezclar-y-que-no)
21. [Errores y excepciones](#21-errores-y-excepciones)
22. [Lo que el ORM NO soporta](#22-lo-que-el-orm-no-soporta)
23. [Recetas de controlador](#23-recetas-de-controlador)
24. [Cheat sheet rapido (para IAs)](#24-cheat-sheet-rapido-para-ias)

---

## 1. Definir un modelo

Convencion: clase en **PascalCase singular**, archivo `App/Models/Usuario.php`, namespace `App\Models`.

```php
<?php

namespace App\Models;

use Cronos\Model\Model;

class Usuario extends Model
{
    //OBLIGATORIOS: si faltan, todo lanza Error
    protected string $table = 'usuarios';          //tabla
    protected string $primaryKey = 'id';           //clave primaria
    protected array $fillable = [                  //OBLIGATORIOS en create()/save()
        'nombre',
        'correo',
        'contrasena',
        'rol',
        'avatar',
        'correo_verificado_en',
        'token_recordar',
    ];

    //OPCIONALES
    protected array $hidden = ['contrasena', 'token_recordar']; //excluidos de toArray()/toJson()
    protected bool $timestamps = true;             //false: no toca created_at/updated_at
    protected string $created = Model::CREATED_AT; //'created_at'
    protected string $updated = Model::UPDATED_AT; //'updated_at'
    protected array $casts = [];                   //conversion de tipos (seccion 11)
    protected array $appends = [];                 //campos calculados (seccion 12)
}
```

**Tabla de propiedades:**

| Propiedad | Tipo | Obligatoria | Descripcion |
|---|---|---|---|
| `$table` | string | SI | Nombre de la tabla |
| `$primaryKey` | string | SI | Columna de clave primaria |
| `$fillable` | array | SI | Campos que `create()`/`save()` exigen (todos deben venir) |
| `$hidden` | array | No | Columnas excluidas de `toArray()`/`toObject()`/`toJson()` |
| `$timestamps` | bool | No | `true`: gestiona `created_at`/`updated_at` automaticamente |
| `$created` / `$updated` | string | No | Nombres de columnas de timestamp |
| `$casts` | array | No | Conversion de tipos al hidratar (seccion 11) |
| `$appends` | array | No | Accessors incluidos en la serializacion (seccion 12) |

---

## 2. CRUD por instancia

> Todo cambio de datos es por **instancia**. `create()` es el unico estatico de escritura.

### Crear

```sql
INSERT INTO publicaciones (usuario_id, titulo, slug, contenido, created_at, updated_at)
VALUES (3, 'Mi titulo', 'mi-titulo', 'contenido...', NOW(), NOW());
```

```php
$publicacion = Publicacion::create([
    'usuario_id' => 3,
    'titulo' => 'Mi titulo',
    'slug' => 'mi-titulo',
    'contenido' => 'contenido...',
]);

//Retorna el modelo con el id asignado, o null si falla.
//EXIGE que vengan TODOS los $fillable (usuario_id, titulo, slug, contenido).
//Los demas campos toman el default de la BD (estado='borrador', vistas=0...).
```

### Guardar con save() (insert o update)

```php
//INSERT: instancia sin clave primaria (requiere todos los $fillable)
$perfil = new Perfil();
$perfil->usuario_id = 3;
$perfil->biografia = 'Desarrollador';
$perfil->telefono = '555-1234';
$perfil->fecha_nacimiento = '1990-05-10';
$perfil->sitio_web = null;
$perfil->save();                 //true si inserto

//UPDATE: instancia cargada; SOLO envia las columnas que cambiaron
$usuario = Usuario::find(3);
$usuario->nombre = 'Nombre Nuevo';
$usuario->save();                //UPDATE usuarios SET nombre = ?, updated_at = ? WHERE id = 3

//sin cambios: no ejecuta SQL y retorna true
```

### Actualizar con update()

```sql
UPDATE publicaciones SET titulo = 'Nuevo', updated_at = NOW()
WHERE id = 7 AND eliminado_en IS NULL;
```

```php
$publicacion = Publicacion::find(7);
$ok = $publicacion->update(['titulo' => 'Nuevo']);  //true si afecto filas
```

- Solo acepta columnas de `$fillable` (otra columna lanza `Error`).
- Agrega `updated_at` si `$timestamps` esta activo.
- Respeta SoftDeletes: no actualiza registros ya borrados.

### Eliminar / restaurar

```sql
DELETE FROM publicaciones WHERE id = 7;
--o con SoftDeletes:
UPDATE publicaciones SET eliminado_en = NOW() WHERE id = 7 AND eliminado_en IS NULL;
```

```php
$publicacion = Publicacion::find(7);
$publicacion->delete();        //soft delete si el modelo usa el trait; fisico si no
$publicacion->forceDelete();   //DELETE fisico siempre
$publicacion->restore();       //recupera un soft delete (requiere el trait SoftDeletes)
```

### Buscar

```sql
SELECT * FROM usuarios WHERE id = 3;
```

```php
$usuario = Usuario::find(3);            //modelo o null
$usuario = Usuario::findOrFail(3);      //modelo o lanza Cronos\Model\ModelNotFoundException
$usuarios = Usuario::all();             //ModelCollection de TODA la tabla, o null si esta vacia

//refresh: relee la fila desde la BD (lanza ModelNotFoundException si ya no existe)
$usuario->refresh();
```

### Tabla de retornos (memorizar)

| Operacion | Retorna | Si falla / vacio |
|---|---|---|
| `Model::create($datos)` | el modelo con id | `null` |
| `$m->save()` | `bool` | `false` |
| `$m->update($datos)` | `bool` | `false` |
| `$m->delete()` / `forceDelete()` / `restore()` | `bool` | `false` |
| `Model::find($id)` | modelo | `null` |
| `Model::findOrFail($id)` | modelo | lanza `ModelNotFoundException` |
| `Model::all()` | `ModelCollection` | **`null`** si no hay filas |
| `->get()` | `ModelCollection` | **`null`** si no hay filas |
| `->first()` | modelo | `null` |
| `->firstOrFail()` | modelo | lanza `ModelNotFoundException` |
| `->value($col)` | valor de la columna | `null` |
| `->exists()` | `bool` | — |
| `->count()` | `int` | `0` |
| `->update($datos)` encadenado (builder) | `int` filas | `0` |

---

## 3. Query Builder: consultas

Los metodos se encadenan desde el nombre del modelo y terminan en un metodo terminal
(`get`, `first`, `firstOrFail`, `count`, `value`, `exists`, `paginate`, `dd`, ...).
Cada cadena tiene su propio builder: una consulta nunca contamina otra.

### SELECT basico

```sql
SELECT * FROM usuarios;
SELECT nombre, correo FROM usuarios;
```

```php
Usuario::all();                                  //toda la tabla
Usuario::select('nombre', 'correo')->get();      //columnas especificas
Usuario::select('usuarios.*', 'roles.nombre')->join(...)->get();  //con tabla.* y columnas calificadas
```

Validacion de `select()`: solo se aceptan `columna`, `tabla.columna` y `tabla.*`
(sin `AS`, sin funciones). Otra cosa lanza `Error`.

### WHERE

```sql
SELECT * FROM usuarios WHERE rol = 'admin';
SELECT * FROM publicaciones WHERE vistas >= 100;
SELECT * FROM usuarios WHERE rol = 'usuario' OR rol = 'editor';
SELECT * FROM publicaciones WHERE vistas BETWEEN 10 AND 100;
SELECT * FROM usuarios WHERE invitado_por IS NULL;
SELECT * FROM usuarios WHERE correo IN ('a@x.com', 'b@x.com');
```

```php
Usuario::where('rol', 'admin')->get();                        //2 args: igualdad
Publicacion::where('vistas', '>=', 100)->get();               //3 args: operador
Usuario::where('rol', 'usuario')->orWhere('rol', 'editor')->get();
Publicacion::whereBetween('vistas', 10, 100)->get();
Usuario::whereNull('invitado_por')->get();
Usuario::whereNotNull('invitado_por')->get();
Usuario::whereIn('correo', ['a@x.com', 'b@x.com'])->get();    //array NO vacio
Usuario::whereNotIn('id', [1, 2, 3])->get();
```

Operadores permitidos (whitelist): `=`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `LIKE`, `NOT LIKE`.

> `where('columna', null)` no es posible por firma: para NULL usa `whereNull()`/`whereNotNull()`.
> `whereIn('col', [])` lanza `Error` (usa `whereNull('col')` o no consultes).

### WHERE con CONCAT

```sql
SELECT * FROM usuarios WHERE CONCAT(nombre, ' ', apellido) LIKE '%carlos%';
```

```php
Usuario::whereConcat('nombre, apellido', 'LIKE', '%carlos%')->get();
//columnas separadas por coma dentro del string; solo letras, digitos, _ , . y espacio
```

> **Incompatibilidad**: `where()`, `whereBetween()` y `whereConcat()` son mutuamente
> excluyentes entre si. Combinar dos de ellos lanza `Error`. `andWhere()`/`orWhere()`
> si funcionan con cualquiera (despues de la condicion inicial).

### AND / OR adicionales

```sql
SELECT * FROM publicaciones
WHERE estado = 'publicado' AND vistas > 0 AND usuario_id = 3;
```

```php
Publicacion::where('estado', 'publicado')
    ->andWhere('vistas', '>', 0)
    ->andWhere('usuario_id', 3)
    ->get();
```

> Semantica: todas las condiciones base se unen con `AND`; luego se agregan las
> `andWhere`/`orWhere` en el orden en que se escribieron. No hay agrupacion con
> parentesis: para logicas `(A OR B) AND C` complejas usa SQL directo (seccion 18).

### JOIN (solo INNER JOIN)

```sql
SELECT publicaciones.*, usuarios.nombre
FROM publicaciones
JOIN usuarios ON usuarios.id = publicaciones.usuario_id
WHERE publicaciones.estado = 'publicado'
ORDER BY publicaciones.created_at DESC;
```

```php
Publicacion::select('publicaciones.*', 'usuarios.nombre')
    ->join('usuarios', 'usuarios.id', '=', 'publicaciones.usuario_id')
    ->where('publicaciones.estado', 'publicado')
    ->orderBy('publicaciones.created_at', 'DESC')
    ->get();
```

> No hay alias de tabla (`publicaciones p`) ni LEFT JOIN. Usa el nombre completo de la
> tabla en las columnas calificadas.

### Orden y paginado de filas

```sql
SELECT * FROM publicaciones ORDER BY created_at DESC, id ASC LIMIT 10 OFFSET 20;
```

```php
Publicacion::orderBy('created_at', 'DESC')->orderBy('id', 'ASC')->limit(10)->offset(20)->get();

//atajos por created_at:
Publicacion::latest()->get();          //ORDER BY created_at DESC
Publicacion::oldest('id')->get();      //ORDER BY id ASC
```

`orderBy()` acepta `'ASC'`/`'DESC'` (mayusculas o minusculas). Otra direccion lanza `Error`.

### Terminales de lectura

```sql
SELECT titulo FROM publicaciones WHERE slug = 'mi-post' LIMIT 1;
SELECT COUNT(*) FROM publicaciones WHERE estado = 'publicado';
SELECT SUM(vistas) FROM publicaciones WHERE usuario_id = 3;
SELECT AVG(vistas), MIN(vistas), MAX(vistas) FROM publicaciones;
```

```php
$titulo = Publicacion::where('slug', 'mi-post')->value('titulo');   //string o null
$total  = Publicacion::where('estado', 'publicado')->count();       //int

//agregados: por columna directa (estilo moderno)...
Publicacion::where('usuario_id', 3)->sum('vistas');
Publicacion::avg('vistas');
Publicacion::max('vistas');
Publicacion::min('vistas');

//...o estilo legacy (una sola columna por select()):
Publicacion::select('vistas')->sum();

//existencia (respeta wheres y soft deletes):
Publicacion::where('slug', $slug)->exists();         //bool
Publicacion::where('slug', $slug)->doesntExist();    //bool

//primero con excepcion si no hay:
$publicacion = Publicacion::where('usuario_id', 3)->firstOrFail();
```

> Los agregados aceptan UNA sola columna. Sin columna definida (ni directa ni por
> `select()`) lanzan `Error`. Con JOIN incluidos: el agregado respeta los joins de la consulta.

### UPDATE / DELETE encadenados (por condiciones)

```sql
UPDATE publicaciones SET estado = 'archivado', updated_at = NOW() WHERE estado = 'borrador';
DELETE FROM comentarios WHERE publicacion_id = 7;
```

```php
$filas = Publicacion::where('estado', 'borrador')->update(['estado' => 'archivado']); //int
$filas = Comentario::where('publicacion_id', 7)->delete();                            //int
```

- **Exigen** al menos un `where()` previo (proteccion contra borrados masivos accidentales).
- Validan que las columnas esten en `$fillable`.
- `update()` agrega `updated_at` si `$timestamps` esta activo.
- Con SoftDeletes, `delete()` marca `eliminado_en` (ver seccion 13).
- **No disparan eventos de modelo** (solo las operaciones por instancia los disparan).

### Consulta compleja combinada (todo junto)

```sql
SELECT publicaciones.*, usuarios.nombre, usuarios.correo
FROM publicaciones
JOIN usuarios ON usuarios.id = publicaciones.usuario_id
WHERE publicaciones.estado = 'publicado'
  AND publicaciones.vistas >= 50
  AND EXISTS (SELECT 1 FROM comentarios c WHERE c.publicacion_id = publicaciones.id)
ORDER BY publicaciones.vistas DESC
LIMIT 20 OFFSET 40;
```

```php
$publicaciones = Publicacion::select('publicaciones.*', 'usuarios.nombre', 'usuarios.correo')
    ->join('usuarios', 'usuarios.id', '=', 'publicaciones.usuario_id')
    ->where('publicaciones.estado', 'publicado')
    ->andWhere('publicaciones.vistas', '>=', 50)
    ->whereHas('comentarios')            //EXISTS automatico (ver seccion 6)
    ->orderBy('publicaciones.vistas', 'DESC')
    ->limit(20)
    ->offset(40)
    ->get();
```

---

## 4. Relaciones: definir y leer

Se declaran como metodos publicos en el modelo. Firmas:

| Tipo | Firma | Ejemplo |
|---|---|---|
| 1:1 | `hasOne(Clase::class, ?$foreignKey, $localKey = 'id')` | `$this->hasOne(Perfil::class, 'usuario_id')` |
| 1:N | `hasMany(Clase::class, ?$foreignKey, $localKey = 'id')` | `$this->hasMany(Publicacion::class, 'usuario_id')` |
| N:1 | `belongsTo(Clase::class, ?$foreignKey, $ownerKey = 'id')` | `$this->belongsTo(Usuario::class, 'usuario_id')` |
| N:M | `belongsToMany(Clase::class, $pivote, $fkLocal, $fkRemoto)` | `$this->belongsToMany(Rol::class, 'rol_usuario', 'usuario_id', 'rol_id')` |

- Si omites `$foreignKey` en hasOne/hasMany se asume `{tabla_padre}_id` (ej. `usuario_id`).
- En `belongsTo` si omites `$foreignKey` se asume la pk del modelo relacionado.

```php
//App/Models/Usuario.php
public function perfil(): \Cronos\Model\HasOne                 //1:1
{
    return $this->hasOne(Perfil::class, 'usuario_id');
}

public function publicaciones(): \Cronos\Model\HasMany         //1:N
{
    return $this->hasMany(Publicacion::class, 'usuario_id');
}

public function invitadoPor(): \Cronos\Model\BelongsTo         //N:1 auto-referencial
{
    return $this->belongsTo(Usuario::class, 'invitado_por', 'id');
}

public function roles(): \Cronos\Model\BelongsToMany           //N:M
{
    return $this->belongsToMany(Rol::class, 'rol_usuario', 'usuario_id', 'rol_id');
}

//App/Models/Publicacion.php
public function usuario()      { return $this->belongsTo(Usuario::class, 'usuario_id'); }
public function categoria()    { return $this->belongsTo(Categoria::class, 'categoria_id'); }
public function comentarios()  { return $this->hasMany(Comentario::class, 'publicacion_id'); }
public function etiquetas()    { return $this->belongsToMany(Etiqueta::class, 'publicacion_etiqueta', 'publicacion_id', 'etiqueta_id'); }
```

### Leer relaciones

```sql
SELECT * FROM perfiles WHERE usuario_id = 3 LIMIT 1;                    --hasOne
SELECT * FROM publicaciones WHERE usuario_id = 3;                       --hasMany
SELECT usuarios.* FROM usuarios JOIN rol_usuario ON ... ;               --belongsToMany
```

```php
$usuario = Usuario::find(3);

//1) via metodo (consulta directa):
$perfil         = $usuario->perfil()->get();           //modelo o null
$publicaciones  = $usuario->publicaciones()->get();    //ModelCollection o null
$roles          = $usuario->roles()->get();            //ModelCollection o null

//2) via acceso magico (lazy, cacheado en la instancia):
$perfil         = $usuario->perfil;
$publicaciones  = $usuario->publicaciones;

//3) relaciones ya cargadas (por with(), seccion 5):
$usuario->relationLoaded('perfil');   //bool
$usuario->getRelation('perfil');      //valor cargado o null
$usuario->setRelation('perfil', $otroPerfil);  //inyectar manualmente
```

---

## 5. Eager Loading (with)

Evita el problema N+1: 1 consulta extra por relacion (no por registro).

```sql
--MAL (N+1): 1 query de publicaciones + 1 query de usuarios POR publicacion
--BIEN (2 queries): SELECT * FROM publicaciones; SELECT * FROM usuarios WHERE id IN (3, 7, 9);
```

```php
//plano:
$publicaciones = Publicacion::with('usuario', 'comentarios')->get();

foreach ($publicaciones as $p) {
    $p->getRelation('usuario');     //modelo ya cargado
    $p->relationLoaded('usuario');  //true
}
```

### Anidado (dot notation)

```sql
--publicacion -> usuario -> perfil: resuelto con 3 queries totales (no 1 por registro)
```

```php
$publicacion = Publicacion::with('usuario.perfil')->first();
$publicacion->usuario->getRelation('perfil')->biografia;

//3 niveles (cada nivel debe existir como metodo en su modelo):
Usuario::with('invitadoPor.perfil')->get();

//arbol: usuario carga perfil E invitadoPor (cada uno con 1 query):
Publicacion::with('usuario.perfil', 'usuario.invitadoPor')->get();

//anidado desde hasMany:
Usuario::with('publicaciones.comentarios')->get();
```

Si una relacion anidada no existe como metodo, lanza `Error` al ejecutar la consulta.

### Constraints (closures)

El closure recibe el QueryBuilder del modelo relacionado y **NO debe terminarlo**
(sin `get()`/`first()`; solo condiciones, orden y columnas):

```php
$usuario = Usuario::with(['publicaciones' => fn ($q) => $q->where('estado', 'publicado')])->first();
$usuario->publicaciones;   //solo las publicadas

//mixto: strings y closures juntos:
Publicacion::with([
    'comentarios' => fn ($q) => $q->orderBy('id', 'DESC'),
    'usuario'     => fn ($q) => $q->select('id', 'nombre', 'correo'),
])->get();

//constraint + anidado en el mismo arbol:
Publicacion::with([
    'usuario' => fn ($q) => $q->where('rol', 'usuario'),
    'usuario.perfil',
])->get();
```

> **Limitacion**: closures en `belongsToMany` lanzan `Error` (solo `with('etiquetas')` plano).

### Acceso magico y load()

```php
$publicacion = Publicacion::find(1);
$autor = $publicacion->usuario;      //lazy: consulta al primer acceso y queda cacheado
$lista = $publicacion->comentarios;  //ModelCollection o null

//cargar relaciones sobre una coleccion ya obtenida:
$publicaciones->load('usuario', 'comentarios');
```

> **Ojo**: `load()` carga item por item (1 query por registro = N+1). Si conoces la
> consulta de antemano, usa `with()`.

Las relaciones cargadas (eager, lazy o con `load()`) se incluyen en `toArray()`/`toObject()`/`toJson()`.

### Deteccion de N+1

```php
Model::preventLazyLoading();     //activo: el lazy loading lanza Error con mensaje claro
Publicacion::first()->usuario;   //Error: "Carga perezosa (N+1) bloqueada..."
Publicacion::with('usuario')->first()->usuario;  //el eager SI funciona
Model::preventLazyLoading(false);
Model::isLazyLoadingPrevented(); //estado actual
```

> `find()` y `all()` NO pasan por el builder: **no soportan `with()`**. Para un registro
> con relaciones usa `Model::with(...)->where('id', $id)->first()`.

---

## 6. Filtrar por relaciones: has / whereHas / withCount

### has(): "tiene al menos N"

```sql
SELECT * FROM publicaciones p
WHERE EXISTS (SELECT 1 FROM comentarios c WHERE c.publicacion_id = p.id);

SELECT * FROM publicaciones p
WHERE (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) >= 3;
```

```php
$publicaciones = Publicacion::has('comentarios')->get();               //>= 1
$populares     = Publicacion::has('comentarios', '>=', 3)->get();      //operadores: =, !=, <>, >, >=, <, <=
$usuariosConRol = Usuario::has('roles')->get();                        //belongsToMany (via pivote)
```

### whereHas(): "tiene relaciones que cumplen..."

```sql
SELECT * FROM publicaciones p
WHERE EXISTS (
    SELECT 1 FROM comentarios c
    WHERE c.publicacion_id = p.id AND c.contenido LIKE '%cronos%'
);
```

```php
$publicaciones = Publicacion::whereHas(
    'comentarios',
    fn ($q) => $q->where('contenido', 'LIKE', '%cronos%')
)->get();

//cantidad + condiciones juntas:
Publicacion::whereHas('comentarios', fn ($q) => $q->where('activo', 1), '>=', 5)->get();

//sobre belongsTo:
Comentario::whereHas('usuario', fn ($q) => $q->where('rol', 'admin'))->get();
```

- El closure recibe el builder del modelo relacionado; **no lo termines**.
- El filtro de soft deletes del modelo relacionado se aplica automatico en la subconsulta.
- Relacion inexistente u operador invalido: `Error`.

### withCount(): contar sin N+1

```sql
--Laravel lo hace con subquery por fila. Cronos lo hace en 1 query extra agrupada:
SELECT c.publicacion_id, COUNT(*) FROM comentarios c
WHERE c.publicacion_id IN (1, 2, 3) GROUP BY c.publicacion_id;
```

```php
$publicaciones = Publicacion::withCount('comentarios')->get();
$publicaciones->first()->comentarios_count;    //int, 0 si no tiene

//con condiciones:
Usuario::withCount(['publicaciones' => fn ($q) => $q->where('estado', 'publicado')])->get();

//todo combinado en la misma consulta:
Publicacion::has('comentarios')
    ->withCount('comentarios')
    ->with('usuario')
    ->where('estado', 'publicado')
    ->orderBy('created_at', 'DESC')
    ->get();
```

- El atributo se llama `{relacion}_count` (int), incluido en `toArray()`.
- Funciona en `get()`, `first()` y `paginate()`.

---

## 7. Escribir a traves de relaciones

### hasOne / hasMany: create() y save()

```sql
INSERT INTO publicaciones (usuario_id, titulo, slug, contenido, ...) VALUES (3, ...);
--la FK la resuelve la relacion
```

```php
$usuario = Usuario::find(3);

//create(): asigna usuario_id automaticamente (los datos deben cumplir create() del modelo)
$publicacion = $usuario->publicaciones()->create([
    'titulo' => 'Nueva',
    'slug' => 'nueva',
    'contenido' => 'contenido',
]);

//save(): persiste un modelo ya construido asignandole antes la FK
$publicacion = new Publicacion();
$publicacion->titulo = 'Guardada';
$publicacion->slug = 'guardada';
$publicacion->contenido = 'contenido';
$usuario->publicaciones()->save($publicacion);
```

Sin clave primaria en el padre: `Error`.

### belongsTo: associate() y dissociate()

```sql
UPDATE comentarios SET publicacion_id = 7 WHERE id = 12;
```

```php
$comentario = new Comentario();
$comentario->contenido = 'Mi comentario';

//associate asigna la FK (modelo o id) y retorna el PADRE para encadenar save():
$comentario->publicacion()->associate($publicacion)->save();
$comentario->usuario()->associate(5)->save();

//dissociate limpia la FK a NULL en memoria:
$comentario->publicacion()->dissociate();
```

> `dissociate()` escribe NULL en memoria: si la columna es NOT NULL, el `save()`
> falla a nivel BD (el responsable es el esquema, igual que en Eloquent).

---

## 8. Pivotes: attach / detach / sync

```sql
INSERT INTO publicacion_etiqueta (publicacion_id, etiqueta_id) VALUES (7, 2);
DELETE FROM publicacion_etiqueta WHERE publicacion_id = 7 AND etiqueta_id = 2;
```

```php
$publicacion = Publicacion::find(7);

//ATTACH: agrega (duplicados ignorados); retorna filas insertadas
$publicacion->etiquetas()->attach(2);
$publicacion->etiquetas()->attach([1, 2, 3]);

//DETACH: elimina; retorna filas eliminadas
$publicacion->etiquetas()->detach(2);        //solo la 2
$publicacion->etiquetas()->detach([1, 2]);   //las 1 y 2
$publicacion->etiquetas()->detach();         //TODAS las de esta publicacion

//SYNC: deja el pivote exactamente como la lista; retorna attached/detached
$resultado = $usuario->roles()->sync([2, 3]);
// ['attached' => [3], 'detached' => [1], 'updated' => []]
$usuario->roles()->sync([]);                 //desasocia todo
```

- Requieren clave primaria en el padre (`Error` si falta).
- `sync([])` es valido (equivale a `detach()` sin argumentos).
- No hay columnas extra de pivote ni `withTimestamps` (pivotes de 2 claves).

---

## 9. firstOrNew / firstOrCreate / updateOrCreate

```sql
SELECT * FROM usuarios WHERE correo = 'x@test.com' LIMIT 1;
--si no vino nada: INSERT INTO usuarios (...) VALUES (...);
--si vino: UPDATE usuarios SET nombre = '...' WHERE id = ...;
```

```php
//busca; si no existe retorna instancia NUEVA SIN GUARDAR:
$usuario = Usuario::firstOrNew(['correo' => $correo], ['rol' => 'usuario']);

//busca; si no existe lo crea:
$usuario = Usuario::firstOrCreate(['correo' => $correo], $datosCompletos);

//busca; si existe lo actualiza; si no, crea:
$usuario = Usuario::updateOrCreate(
    ['correo' => $correo],               //condiciones
    ['nombre' => 'Nuevo Nombre']         //datos
);
```

- Primer array = condiciones; segundo = datos.
- Respetan SoftDeletes: un registro borrado no se encuentra (se crea uno nuevo).
- Disparan eventos (`creating/created`, `updating/updated`, `saving/saved`).

---

## 10. Eventos de modelo y Observers

Registra listeners en `booted()` (se ejecuta una vez por clase):

```php
class Publicacion extends Model
{
    protected static function booted(): void
    {
        static::creating(function ($publicacion) {
            $publicacion->slug = strtolower($publicacion->slug);
            //retornar false DETIENE la operacion
        });
    }
}
```

Orden de eventos por operacion:

| Operacion | Eventos (en orden) |
|---|---|
| INSERT (`create`, `save` nuevo) | `saving` -> `creating` -> (insert) -> `created` -> `saved` |
| UPDATE (`save`, `update` de instancia) | `saving` -> `updating` -> (update) -> `updated` -> `saved` |
| DELETE (instancia) | `deleting` -> (delete) -> `deleted` |
| RESTORE | `restoring` -> (restore) -> `restored` |
| FORCE DELETE | (delete) -> `forceDeleted` |
| LECTURA (`find`, `get`, `first`, `all`) | `retrieved` |

- Si un listener de `saving/creating/updating/deleting/restoring` retorna `false`, la
  operacion se aborta: `create()` retorna null y `save()/update()/delete()/restore()` retornan false.
- Los metodos estaticos de registro se llaman igual que el evento:
  `Publicacion::deleting(fn ($p) => ...)`, `Publicacion::retrieved(...)`, etc.

### Observers

```php
//App/Observers/PublicacionObserver.php
class PublicacionObserver
{
    public function created($publicacion): void { /* ... */ }
    public function updated($publicacion): void { /* ... */ }
    public function deleted($publicacion): void { /* ... */ }
}

//registrar (en booted() o en un provider):
Publicacion::observe(PublicacionObserver::class);
```

Para tests: `Publicacion::flushEventListeners()` elimina los listeners estaticos de la
clase y re-ejecuta `booted()` en la proxima instancia.

> Los `update()`/`delete()` por query builder (masivos) **no disparan eventos**: solo
> las operaciones por instancia.

---

## 11. Casts de tipos

Todo lo que llega de PDO puede venir como string. Con `$casts` el modelo convierte al hidratar:

```php
protected array $casts = [
    'usuario_id' => 'int',        //int|integer
    'peso'       => 'float',      //float|double|real
    'activa'     => 'bool',       //bool|boolean
    'nombre'     => 'string',
    'vistas'     => 'decimal:2',  //string "123.00" (N decimales, como Eloquent)
    'created_at' => 'datetime',   //DateTimeImmutable ('date' tambien existe)
    'metadata'   => 'array',      //json_decode a array ('json' es equivalente)
];
```

- `null` nunca se convierte.
- Sin `$casts`, el comportamiento es identico al clasico (sin conversion).
- Los casts se aplican al hidratar (`find`, `all`, `get`, `first`) y despues de
  `create()`/`save()`/`update()`.

---

## 12. Accessors, Mutators y $appends

```php
class Publicacion extends Model
{
    //campos calculados incluidos en toArray()/toObject()/toJson():
    protected array $appends = ['titulo_mayuscula'];

    //ACCESSOR: transformacion en LECTURA (get{CampoStudly}Attribute)
    public function getTituloMayusculaAttribute(?string $valor): ?string
    {
        $titulo = $valor ?? $this->attributes['titulo'] ?? null;

        return $titulo === null ? null : mb_strtoupper($titulo);
    }

    //MUTATOR: transformacion en ESCRITURA (set{CampoStudly}Attribute)
    public function setTituloAttribute(?string $valor): ?string
    {
        return $valor === null ? null : mb_strtolower(trim($valor));
    }
}
```

```php
$publicacion = PublicacionConAccesorios::find(1);
$publicacion->titulo;             //'titulo guardado' -> accessor de LECTURA solo si existe getTituloAttribute
$publicacion->titulo_mayuscula;   //dispara getTituloMayusculaAttribute (aunque no exista columna)

$array = $publicacion->toArray(); //incluye 'titulo_mayuscula' por $appends
```

- Precedencia de `__get`: atributo -> relacion cargada -> accessor -> relacion lazy -> null.
- El mutator se aplica en `create()`, `save()`, `update()` y `firstOrNew()`; **no** al
  hidratar desde la BD (los datos crudos quedan intactos en `$attributes`).
- Nombre: `snake_case` del campo -> `StudlyCase` del metodo (`titulo_mayuscula` -> `TituloMayuscula`).

---

## 13. Soft Deletes

```php
use Cronos\Model\Model;
use Cronos\Model\SoftDeletes;

class Publicacion extends Model
{
    use SoftDeletes;   //columna por defecto: SoftDeletes::DELETED_AT = 'eliminado_en'
    //personalizable: protected string $deletedAt = 'borrado_en';
}
```

```sql
--delete():
UPDATE publicaciones SET eliminado_en = '2026-09-03 12:00:00'
WHERE id = 7 AND eliminado_en IS NULL;
--restore():
UPDATE publicaciones SET eliminado_en = NULL WHERE id = 7 AND eliminado_en IS NOT NULL;
--consultas normales agregan automaticamente:
AND eliminado_en IS NULL
```

```php
//POR INSTANCIA:
$p = Publicacion::find(7);
$p->delete();         //marca eliminado_en; $p->trashed() pasa a true
$p->restore();        //vuelve a NULL; trashed() pasa a false
$p->forceDelete();    //DELETE fisico

//POR CONSULTA:
Publicacion::where('vistas', 0)->delete();                 //borrado logico; retorna int
Publicacion::onlyTrashed()->where('id', 7)->restore();     //restaura; retorna int
Publicacion::withTrashed()->where('id', 7)->delete();      //borrado FISICO

//CONSULTAS:
Publicacion::find(7);              //null si esta eliminado
Publicacion::withTrashed()->get(); //incluye eliminados
Publicacion::onlyTrashed()->get(); //SOLO eliminados
Publicacion::count();              //excluye eliminados (tambien agregados y exists)
```

- `withTrashed()`/`onlyTrashed()`/`restore()` en un modelo sin el trait: `Error`.
- Requiere la columna en la tabla (`$table->softDeletes('eliminado_en')` en la migracion).
- `firstOrCreate`/`updateOrCreate` no encuentran borrados (crean uno nuevo).

---

## 14. Timestamps

```php
protected bool $timestamps = true;   //false: ni INSERT ni UPDATE tocan timestamps

protected string $created = Model::CREATED_AT;  //'created_at'
protected string $updated = Model::UPDATED_AT;  //'updated_at'
```

- INSERT activo: setea `created_at` Y `updated_at`.
- UPDATE activo: setea solo `updated_at`.
- Con `false` no se incluyen (las columnas deben ser nullable o tener default).

---

## 15. Paginacion

```sql
SELECT COUNT(*) FROM publicaciones WHERE estado = 'publicado';          --total
SELECT * FROM publicaciones WHERE estado = 'publicado'
ORDER BY created_at DESC LIMIT 15 OFFSET 30;                            --pagina 3
```

```php
$pagina = Publicacion::where('estado', 'publicado')
    ->latest()
    ->paginate(15, (int) ($_GET['page'] ?? 1));   //(por pagina, numero de pagina)

$pagina->total;          //int: total real con el filtro aplicado
$pagina->porPagina;      //int
$pagina->paginaActual;   //int
$pagina->ultimaPagina;   //int (minimo 1)
$pagina->desde();        //?int: indice del primer item de la pagina
$pagina->hasta();        //?int: indice del ultimo item
$pagina->items;          //ModelCollection: SOPORTA with() y withCount()
$pagina->toArray();      //['data' => [...], 'total', 'por_pagina', 'pagina_actual', 'ultima_pagina', 'desde', 'hasta']

foreach ($pagina as $publicacion) { ... }   //iterable y Countable

//respuesta JSON tipica de controlador:
return json(['status' => 'success', 'paginacion' => $pagina->toArray()]);
```

- Ejecuta un COUNT + el SELECT de la pagina (2 queries); respeta wheres/joins/soft deletes.
- Pagina fuera de rango: `items` vacio, `total` conserva el conteo real.
- `paginate(0)` lanza `Error`.

---

## 16. Transacciones

```php
use Cronos\Model\Model;

$resultado = Model::transaction(function () {
    $usuario = Usuario::create([...]);
    $perfil = $usuario->perfil()->create([...]);

    return $usuario;
});
//si el callback lanza excepcion: rollback automatico y la excepcion se re-lanza.
//llamadas anidadas participan de la transaccion externa (contador de nivel).

//alternativa para codigo sin modelos:
use Cronos\Database\DBexecute;
DBexecute::transaction(fn () => Usuario::create([...]));
```

---

## 17. ModelCollection

Retornada por `get()`, `all()` y `Paginator->items`. **Puede ser `null`** (get/all cuando no hay filas).

| Metodo | Descripcion | Ejemplo |
|---|---|---|
| `map(callable)` | Transforma cada elemento | `$c->map(fn($p) => $p->titulo)` |
| `filter(callable)` | Filtra | `$c->filter(fn($p) => $p->vistas > 10)` |
| `first(?callable)` / `last(?callable)` | Primero/ultimo (o el que cumpla) | `$c->first(fn($p) => $p->slug === 'x')` |
| `count()` | Cantidad | `$c->count()` |
| `each(callable)` | Itera; retorna la coleccion | `$c->each(fn($p) => ...)` |
| `values()` | Reindexa | `$c->filter(...)->values()` |
| `contains($key, $val)` | Contiene valor o cumple callback | `$c->contains('slug', 'x')` |
| `sum/avg/min/max($key)` | Operaciones sobre una columna | `$c->sum('vistas')` |
| `groupBy($key)` | `array<string, ModelCollection>` | `$c->groupBy('estado')['borrador']` |
| `sortBy($key, $desc)` / `sortByDesc($key)` | Ordena en PHP | `$c->sortBy('titulo')` |
| `pluck($key)` | Extrae columna a array | `$c->pluck('titulo')` |
| `load($rel, ...)` | Carga relaciones (lazy: OJO N+1) | `$c->load('usuario')` |
| `isEmpty()` / `isNotEmpty()` | Vaciedad | `$c->isNotEmpty()` |
| `toArray()` / `toObject()` / `toJson()` | Serializa (respeta $hidden) | `$c->toArray()` |
| `getIterator()` | foreach | `foreach ($c as $p) { ... }` |

---

## 18. SQL directo y depuracion

### customQuery (parametrizado)

```sql
SELECT * FROM publicaciones WHERE vistas > ? AND estado = ?;
```

```php
//retorna array de objetos (stdClass); SIEMPRE con placeholders:
$filas = Publicacion::customQuery(
    'SELECT * FROM publicaciones WHERE vistas > ? AND estado = ?',
    [100, 'publicado']
);
```

### Acceso directo al driver

```php
$filas  = Model::db()->statement('SELECT id, nombre FROM usuarios WHERE rol = ?', ['admin']);
$filas  = Usuario::db()->statement(...);              //igual, desde el modelo
$filasAfectadas = Model::db()->statementC_U_D('UPDATE usuarios SET rol = ? WHERE id = ?', ['admin', 3]);
$ultimoId = Model::db()->lastInsertId();
```

> NUNCA concatenes input del usuario en el SQL: usa placeholders `?`.

### dd() del builder (no ejecuta la consulta y NO detiene el script)

```php
$info = Publicacion::where('estado', 'publicado')->limit(5)->dd();
// ['sql_raw' => 'SELECT * FROM publicaciones WHERE estado = ? LIMIT 5',
//  'sql_debug' => '...con los valores incrustados...',
//  'bindings' => ['publicado'],
//  'model' => 'App\Models\Publicacion']
```

---

## 19. Seguridad

- **Valores**: siempre parametrizados (`?`). Nunca se interpola input.
- **Identificadores**: columnas y tablas validadas con regex
  (`[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?`); `select()` admite ademas
  `tabla.*`; en `whereConcat()` solo letras, digitos, `_`, coma, punto y espacio.
- **Operadores**: whitelist cerrada; fuera de ella, `Error`.
- **Mass assignment**: `create()`/`save()`/`update()` rechazan columnas fuera de `$fillable`.
- Nunca pases el nombre de una columna desde input del usuario (whitelistala tu).

---

## 20. Combinaciones: que se puede mezclar y que NO

**Combinaciones validas (probadas en tests):**

```php
Publicacion::select('publicaciones.*', 'usuarios.nombre')
    ->join('usuarios', 'usuarios.id', '=', 'publicaciones.usuario_id')
    ->where('publicaciones.estado', 'publicado')
    ->andWhere('vistas', '>=', 50)
    ->orWhere('usuario_id', 3)
    ->whereHas('comentarios', fn ($q) => $q->where('activo', 1))
    ->with('usuario', 'comentarios')
    ->with('usuario.perfil')
    ->withCount('etiquetas')
    ->orderBy('publicaciones.vistas', 'DESC')
    ->limit(20)
    ->offset(40)
    ->get();
```

- `with()`/`withCount()`/`has()`/`whereHas()` se combinan libremente entre si y con
  el resto; funcionan en `get()`, `first()`/`firstOrFail()` y `paginate()`.
- Varios `orderBy()` se acumulan; varios `whereHas()` se acumulan con `AND`.

**Combinaciones INVALIDAS (lanzan `Error`):**

| Combinacion | Resultado |
|---|---|
| `where()` + `whereBetween()` | `Error` |
| `where()` + `whereConcat()` | `Error` |
| `whereBetween()` + `whereConcat()` | `Error` |
| `andWhere()`/`orWhere()` sin un `where()`/`whereBetween()`/`whereConcat()` previo | `Error` |
| `whereIn($col, [])` / `whereNotIn($col, [])` | `Error` |
| Agregado (`sum` etc.) sin columna o con mas de una | `Error` |
| Closure en `with()` sobre `belongsToMany` | `Error` |
| `withTrashed()`/`onlyTrashed()`/`restore()` sin el trait SoftDeletes | `Error` |
| Builder `update()`/`delete()` sin `where()` previo | `Error` |
| `paginate(0)` / `limit(0)` / `offset(-1)` | `Error` |
| Identificador con caracteres invalidos / operador fuera de whitelist | `Error` |
| `create()`/`save()` sin todos los `$fillable` | `Error` |
| `update()`/`create()` con columna fuera de `$fillable` | `Error` |
| Acceso lazy con `preventLazyLoading()` activo | `Error` |

---

## 21. Errores y excepciones

| Excepcion | Cuando |
|---|---|
| `\Error` | Uso incorrecto: validaciones de `$table/$primaryKey/$fillable`, identificadores invalidos, combinaciones prohibidas, N+1 bloqueado, pivotes sin pk del padre |
| `Cronos\Model\ModelNotFoundException` (extends `\RuntimeException`) | `findOrFail()`, `firstOrFail()`, `refresh()` sin resultados |
| `Cronos\Errors\HttpNotFoundException` | Ruta no encontrada (lo maneja el framework, no el ORM) |
| `PDOException` | Fallas reales de BD (conexion, constraint violada, sintaxis SQL en `customQuery`) |

```php
use Cronos\Model\ModelNotFoundException;

try {
    $publicacion = Publicacion::findOrFail($id);
} catch (ModelNotFoundException $e) {
    return json(['status' => 'error', 'message' => 'Publicacion no encontrada'], 404);
}
```

---

## 22. Lo que el ORM NO soporta

**Para que una IA no lo intente:**

- LEFT/RIGHT JOIN, alias de tabla (`publicaciones p`), subqueries en `where()`
- `groupBy()`/`having()`/`UNION` en el builder (usa `customQuery`)
- `whereRaw()`/`selectRaw()` (usa `customQuery` o `Model::db()`)
- Scopes (`scopeActivos()`)
- Relaciones `hasManyThrough` y morfologicas (`morphOne/morphMany/morphTo`; las tablas
  `comentables`/`etiquetables` se manejan con SQL directo)
- Closures en `with()` para `belongsToMany`
- Columnas extra de pivote, `withTimestamps`, `toggle()`, `syncWithoutDetaching()`
- `$modelo->relacion()->create()`/`save()` en `belongsToMany` (solo hasOne/hasMany)
- `chunk()`/`cursor()` para datasets grandes
- Conexiones multiples / read-write split
- Eventos en `update()`/`delete()` masivos del builder
- Carbon (los datetime son `DateTimeImmutable`)
- Colecciones avanzadas tipo `Support\Collection` (solo `ModelCollection`)

---

## 23. Recetas de controlador

### Listado paginado con filtro y relaciones

```php
public function index(Request $request)
{
    $pagina = Publicacion::with('usuario')
        ->withCount('comentarios')
        ->where('estado', 'publicado')
        ->orderBy('created_at', 'DESC')
        ->paginate(15, (int) ($request->page ?? 1));

    return json(['status' => 'success', 'paginacion' => $pagina->toArray()]);
}
```

### Detalle con relacion anidada

```php
public function show(Publicacion $publicacion)
{
    $publicacion = Publicacion::with('usuario.perfil', 'comentarios.usuario')
        ->where('slug', $publicacion->slug)
        ->firstOrFail();

    return json(['status' => 'success', 'publicacion' => $publicacion->toArray()]);
}
```

### Crear con validacion y transaccion

```php
public function store(Request $request)
{
    $valid = $this->validate($request->all(), [
        'titulo' => 'required|string|min:3|max:100',
        'slug' => 'required|slug|unique:Publicacion,slug',
        'contenido' => 'required|string|min:3|max:1000',
    ]);

    if ($valid !== true) {
        return json(['status' => 'error', 'message' => $valid], 422);
    }

    //resolver el usuario autenticado (patron actual del proyecto):
    $token = $request->headers('X-Token');
    $decoded = (new \App\Library\JWT\JWTAuth())->decodeToken($token);
    if (!$decoded) {
        return json(['status' => 'error', 'message' => 'No autenticado'], 401);
    }
    $usuario = Usuario::findOrFail($decoded->sub);

    $publicacion = Model::transaction(function () use ($request, $usuario) {
        $p = $usuario->publicaciones()->create([
            'titulo' => $request->titulo,
            'slug' => $request->slug,
            'contenido' => $request->contenido,
        ]);
        $p->etiquetas()->sync($request->etiquetas ?? []);

        return $p;
    });

    return json(['status' => 'success', 'publicacion' => $publicacion->toArray()], 201);
}
```

### Sincronizar relacion N:M

```php
public function syncEtiquetas(Request $request, Publicacion $publicacion)
{
    $resultado = $publicacion->etiquetas()->sync($request->etiquetas);

    return json([
        'status' => 'success',
        'agregadas' => $resultado['attached'],
        'eliminadas' => $resultado['detached'],
    ]);
}
```

---

## 24. Cheat sheet rapido (para IAs)

```php
//LECTURA
Model::all();                                    //ModelCollection|null
Model::find($id);                                //static|null
Model::findOrFail($id);                          //static, lanza ModelNotFoundException
Model::where('col', $v)->first();                //Model|null
Model::where('col', $v)->firstOrFail();          //Model
Model::where('col', 'op', $v)->get();            //ModelCollection|null
Model::whereIn('col', [1,2])->get();
Model::whereNull('col')->get();
Model::whereBetween('col', $a, $b)->get();
Model::latest()->limit(10)->offset(0)->get();
Model::value('col'); Model::count(); Model::exists();
Model::sum('col'); Model::avg('col'); Model::max('col'); Model::min('col');

//RELACIONES: LEER
$p->usuario;                        //lazy cacheado
Model::with('a', 'a.b')->get();     //eager anidado
Model::with(['a' => fn($q) => $q->where('x', 1)])->get();
Model::has('rel')->get();
Model::whereHas('rel', fn($q) => $q->where('y', 2), '>=', 3)->get();
Model::withCount('rel')->get();     //->rel_count

//ESCRITURA
Model::create([...]);               //todos los $fillable; retorna modelo|null
$m->save();                         //insert o update (solo dirty)
$m->update([...]);                  //bool
$m->delete(); $m->forceDelete(); $m->restore();
Model::where(...)->update([...]);   //masivo: int
Model::where(...)->delete();        //masivo: int
Model::firstOrCreate([...], [...]);
Model::updateOrCreate([...], [...]);
$u->publicaciones()->create([...]); //FK automatica
$c->publicacion()->associate($p)->save();
$c->publicacion()->dissociate();
$p->etiquetas()->attach([1,2]); $p->etiquetas()->detach(); $p->etiquetas()->sync([3]);

//SOFT DELETES
use SoftDeletes;                    //en el modelo
Model::withTrashed()->get(); Model::onlyTrashed()->get();
Model::onlyTrashed()->where('id', $x)->restore();
$m->trashed();

//UTILIDADES
Model::where(...)->paginate(15, 2); //Paginator
Model::where(...)->dd();            //array (no muere, no ejecuta)
Model::customQuery('...WHERE x = ?', [$v]);
Model::transaction(fn () => ...);
Model::preventLazyLoading();

//SERIALIZACION
$m->toArray(); $m->toObject();      //respeta $hidden, incluye $appends y relaciones cargadas
$c->toJson();                       //ModelCollection
$pagina->toArray();                 //Paginator
```

---

## Cobertura de tests (verificacion de esta guia)

| Archivo de test | Cubre |
|---|---|
| `tests/Integration/OrmQueryBuilderTest.php` | Builder clasico, aislamiento de estado entre modelos |
| `tests/Integration/OrmFeaturesTest.php` | whereIn/whereNull, count/offset, transacciones, eager basico, casts, soft deletes |
| `tests/Integration/OrmEloquentStyleTest.php` | CRUD de instancia, findOrFail, withTrashed/onlyTrashed, agregados, casts ricos, coleccion, preventLazyLoading, toArray no destructivo |
| `tests/Integration/OrmEagerAvanzadoTest.php` | Eager anidado, constraints en with |
| `tests/Integration/OrmRelationScopesTest.php` | has/whereHas/withCount |
| `tests/Integration/OrmPivotTest.php` | attach/detach/sync |
| `tests/Integration/OrmRelationCreateTest.php` | create/save via relacion, associate/dissociate |
| `tests/Integration/OrmFaseBTest.php` | firstOrCreate/updateOrCreate, eventos, observadores, paginacion, accessors/mutators |

---

> **Anterior**: [03 - Controladores](03-controladores.md)
> **Siguiente**: [05 - Validaciones](05-validaciones.md)
