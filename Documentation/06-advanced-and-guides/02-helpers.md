# Helpers y Funciones Auxiliares

## Helpers de Depuración (Estilo Laravel / VarDumper)

Cronos Framework cuenta con un subsistema nativo de depuración (`Cronos\Debug\Dumper`) con detección de origen (`📍 Archivo:línea`), soporte para múltiples argumentos, colores ANSI en terminal y visor interactivo con tema oscuro y acordeones colapsables en navegador:

```php
// Vuelca una o más variables y CONTINÚA la ejecución
dump($user, $params, 'Paso 1 completado');

// Alias corto de dump() para continuar la ejecución
d($user, $params);

// Vuelca una o más variables y TERMINA la ejecución inmediatamente (exit con código 500 en web)
dd($user, $request->all(), 'Depuración crítica');
```

> **Detección automática de contexto:**
> - En **Terminal / CLI**: Renderiza con colores ANSI y formateo estructurado limpio.
> - En **Navegador Web**: Renderiza un panel oscuro con botón de colapsar/expandir nodos y origen del archivo.
> - En **Peticiones API / JSON**: Emite una respuesta JSON estructurada (`__cronos_debug: true`) sin romper el cliente.


## Helpers Globales

### Aplicacion

| Helper | Descripcion | Ejemplo |
|---|---|---|
| `app($class)` | Resuelve instancia del contenedor | `$hasher = app(Hasher::class)` |
| `configGet($key, $default)` | Obtiene valor de configuracion | `configGet('app.name')` |
| `env($key, $default)` | Obtiene variable de entorno | `env('DB_HOST', 'localhost')` |

### HTTP

| Helper | Descripcion | Ejemplo |
|---|---|---|
| `view($name, $params)` | Renderiza una vista | `view('dashboard.index', ['data' => $data])` |
| `json($data, $code)` | Retorna respuesta JSON | `json(['status' => 'success'], 200)` |
| `redirect($url)` | Redirecciona a URL | `redirect('/login')` |
| `back()` | Redirecciona a la pagina anterior | `back()` |
| `route($name, $params)` | Genera URL desde nombre de ruta | `route('dashboard.index')` |

### Sesion

| Helper | Descripcion | Ejemplo |
|---|---|---|
| `session()` | Retorna instancia de sesion | `session()->get('key')` |

## Helpers para Archivos e Imagenes

### Obtener URL de Archivos

```php
// Archivo en PATH_FILE_STORAGE
$blog->imagen = LInkFile::setName($blog->imagen);

// Archivo en subcarpeta de PATH_FILE_STORAGE
$blog->documento = LInkFile::setName($blog->documento, 'archivos');
```

### Almacenar Imagenes

Usa la libreria `intervention/image`. Las imagenes se guardan en la carpeta `PATH_FILE_STORAGE` definida en `.env`.

```php
// Uso completo
$nameFoto = MoveFileImagen::setImage($request->file('image'))
    ->size(400, 100)                    // width, height
    ->delete('nombreimagen.png')        // eliminar imagen anterior del store
    ->format(ImageFormat::WEBP)         // WEBP (default), JPG, PNG
    ->quality(90)                       // calidad (default 90)
    ->maintainAspectRatio(true)         // mantener proporcion (default false)
    ->save();                           // retorna el nombre del archivo

// Uso basico
$nameFoto = MoveFileImagen::setImage($request->file('image'))
    ->size(400)
    ->save();
```

### Almacenar Archivos

Los archivos se guardan en una carpeta "archivos" dentro de `PATH_FILE_STORAGE`.

```php
// Guardar un solo archivo (retorna el nombre del archivo)
$nameArchivo = MoveFile::storeSingle($request->file('archivo'))->originalName()->save();

// Guardar multiples archivos (retorna array de nombres)
$nameArchivos = MoveFile::storeMultiple($request->file('archivos'))->originalName()->save();

// Tipos de nombres disponibles
MoveFile::storeSingle($file)->save();              // Nombre aleatorio: 37e2cb859bbc06c421a695014043d23d.docx
MoveFile::storeSingle($file)->originalName()->save(); // Nombre original
MoveFile::storeSingle($file)->dateName()->save();    // Fecha y hora: 12-02-25-193938.docx
```

## Constantes Generales

```php
ROOT        // Path raiz del proyecto
DIR_PUBLIC  // path.../public
DIR_IMG     // path.../public/PATH_FILE_STORAGE (definido en .env)
```

## Donde Agregar Nuevos Helpers

Los helpers se definen en `System/Helpers/`:

| Archivo | Contenido |
|---|---|
| `app.php` | app(), configGet(), env() |
| `http.php` | json(), redirect(), view(), route() |
| `session.php` | session() |
| `variable.php` | Variables globales, constantes |
| `debug.php` | dd(), d() |

Para agregar nuevos helpers:
1. Crear archivo en `System/Helpers/nuevo_helper.php`
2. Definir funciones globales
3. Se carga automaticamente (`Cronos\App::variableGlobal()` hace `require_once` de todos los archivos en `System/Helpers/`)

---

> **Anterior**: [08 - Middleware](08-middleware.md)
> **Siguiente**: [10 - Configuracion](10-configuracion.md)
