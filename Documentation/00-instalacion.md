# Instalacion y Estructura del Proyecto

## Concepto: este repositorio es la BASE de Cronos Framework

Este proyecto cumple DOS roles y es critico no confundirlos:

1. **Base/plantilla**: de aqui se COPIA el proyecto para crear aplicaciones nuevas (blogs, dashboards, APIs, lo que sea). Cada proyecto nuevo nace de una copia de este repositorio.
2. **Demo de referencia**: `App/` y `resources/views/` (menos `spa/`) contienen una aplicacion de ejemplo (blog con auth) que demuestra como se usa el framework. Ese codigo **NO es del framework**: es material de demostracion que se LIMPIA al iniciar un proyecto nuevo.

La division de capas que define que se limpia y que no:

| Capa | Carpeta | Rol | Se limpia al crear un proyecto? |
|---|---|---|---|
| Nucleo del framework | `System/` (incluye `System/View/`, el motor Blade) | El framework mismo | **NUNCA**. No se borra ni se modifica |
| Configuracion | `config/` | Config general (app, BD, sesion, vistas...) | **NUNCA** (solo se ajustan valores en `.env`) |
| Punto de entrada | `public/`, `cronos`, `composer.json` | Infraestructura de arranque y CLI | **NUNCA** |
| Codigo de la aplicacion | `App/` (Models, Controllers, Migrations, Seeders, Middlewares...) | Todo del proyecto DEMO | **SI: se limpia casi todo** |
| Vistas de la aplicacion | `resources/views/` | Vistas demo + esqueleto base | **SI, con excepciones** (ver protocolo; `spa/` jamas) |
| Tests del framework | `tests/` | Suite que valida System | En el repo base NUNCA; en un proyecto derivado se recorta (ver paso 8) |

> **Regla de oro:** si un archivo vive en `System/`, `config/` o es el punto de entrada, pertenece al framework y no se toca. Si vive en `App/` o en `resources/views/` (fuera de `spa/`), es del proyecto demo y se limpia/reemplaza.

## Orden de lectura de la documentacion (OBLIGATORIO antes de tocar codigo)

Antes de limpiar o construir, leer la documentacion en este orden. Cada doc es el contrato de una pieza: no adivinar comportamientos.

| # | Documento | Que cubre | Leer cuando |
|---|---|---|---|
| 00 | `00-instalacion.md` (este) | Instalacion, estructura, capas, protocolo de limpieza | Primero, siempre |
| 01 | `01-rutas.md` | Registro de rutas web/api, nombres, parametros, grupos | Antes de tocar `routes/` |
| 02 | `02-consola.md` | CLI `php cronos`: make:*, migrate*, db:seed | Antes de generar archivos o migrar |
| 03 | `03-controladores.md` | Controladores, request/response, redirect, validate | Antes de escribir controladores |
| 04 | `04-modelos.md` | ORM completo: relaciones, query builder, casts, eventos | Antes de crear modelos (checklist del final) |
| 05 | `05-validaciones.md` | Reglas de validacion y mensajes | Antes de validar formularios/API |
| 06 | `06-vistas.md` | Motor Blade: herencia, componentes, slots, escape, cheat sheet | ANTES de escribir o editar cualquier vista |
| 07 | `07-sesiones.md` | Sesiones, flash, old(), csrf | Antes de formularios y auth |
| 08 | `08-middleware.md` | Middlewares, pipeline, grupos | Antes de proteger rutas |
| 09 | `09-helpers.md` | Helpers globales disponibles | Consulta rapida permanente |
| 10 | `10-configuracion.md` | Cada archivo de `config/` y `.env` | Al configurar BD, sesiones, CORS |
| 11 | `11-guia-nuevo-modulo.md` | Receta integrada: modulo completo de punta a punta | Al agregar el primer modulo real |
| 12 | `12-tailwindcss.md` | Compilacion de CSS (Tailwind v4) | Al tocar estilos |
| 13 | `13-reactapp-spa.md` | La SPA (`spa/index.php` + assets React) | Si el proyecto usa la parte SPA |
| 14 | `14-migraciones-y-seeders.md` | Schema Builder, migraciones, seeders | Antes de crear tablas |
| 15 | `15-form-requests.md` | Validación desacoplada y tipada en clases Request | Al crear endpoints complejos |
| 16 | `16-api-resources.md` | Capa de transformación JSON de respuestas de API | Al estructurar respuestas REST |
| 17 | `17-testing-http.md` | Testing funcional HTTP sintético en memoria sin servidor | Al testear rutas, middlewares y controladores |
| — | `PROYECTO_CRONOS.md` | Vision general y roadmap del framework | Contexto, al inicio |

## Requisitos

- PHP >= 8.3
- Composer
- MySQL (para migraciones y aplicacion)

## Instalacion

1. Clonar el repositorio
2. Ejecutar el comando `composer install`
3. Crear un archivo `.env` en la raiz del proyecto
4. Configurar el archivo `.env` con los datos de la base de datos
5. `php cronos migrate` para crear las tablas del demo (y `php cronos db:seed` si se quieren datos de prueba)

## Estructura de Carpetas

```
📁 cronos_framework/
├── 📁 App/                        # CAPA DE LA APLICACION (se limpia al crear un proyecto)
│   ├── 📁 Controllers/            # Controladores (demo: Home, Spa, Auth, Publicacion)
│   ├── 📁 Middlewares/            # Middlewares de la aplicacion
│   ├── 📁 Models/                 # Modelos (demo: blog + auth; todos limpiables)
│   ├── 📁 Migrations/             # Migraciones timestamped (una por archivo)
│   ├── 📁 Seeders/                # Seeders (DatabaseSeeder es el punto de entrada)
│   ├── 📁 Providers/              # Service Providers de la app (registro de rutas)
│   ├── 📁 Help/                   # Clases auxiliares genericas (imagenes, archivos)
│   └── 📁 Library/                # Librerias del demo (JWT)
├── 📁 Documentation/              # ESTA documentacion (leer antes de construir)
├── 📁 config/                     # NUCLEO: no se limpia
│   ├── 📄 app.php                 # Configuracion general de la app
│   ├── 📄 cors.php                # Configuracion CORS
│   ├── 📄 database.php            # Configuracion de base de datos
│   ├── 📄 hashing.php             # Configuracion de hashing
│   ├── 📄 providers.php           # Service Providers a cargar
│   ├── 📄 session.php             # Configuracion de sesiones
│   └── 📄 view.php                # Configuracion de vistas (motor blade)
├── 📁 public/                     # NUCLEO: no se limpia (solo assets del demo)
│   ├── 📄 index.php               # Punto de entrada de la aplicacion
│   ├── 📄 .htaccess               # Configuracion Apache
│   └── 📁 assets/                 # Archivos estaticos (CSS, JS, imagenes)
├── 📁 resources/
│   ├── 📁 css/                    # CSS fuente compilado con Tailwind v4
│   └── 📁 views/                  # Vistas (notacion de punto: view('home.index'))
│       ├── 📁 components/         # Libreria x-* base (button, alert, badge, card, input, textarea)
│       ├── 📁 errors/             # Paginas de error por codigo HTTP (404.php: requerida)
│       ├── 📁 home/               # Vistas demo del blog publico (limpiable)
│       ├── 📁 layouts/            # Esqueleto base (app.php + @yield/@stack)
│       ├── 📁 partials/           # Fragmentos incluidos (nav.php)
│       └── 📁 spa/                # Shell de la SPA — JAMAS se limpia
├── 📁 routes/
│   ├── 📄 web.php                 # Rutas web (paginas)
│   └── 📄 api.php                 # Rutas API (JSON)
├── 📁 System/                     # NUCLEO DEL FRAMEWORK: NUNCA se limpia ni modifica
│   ├── 📄 App.php                 # Clase principal (bootstrap + run)
│   ├── 📁 Config/                 # Sistema de configuracion
│   ├── 📁 ConsoleCLI/             # CLI (comandos php cronos)
│   ├── 📁 Container/              # Contenedor de dependencias
│   ├── 📁 Crypto/                 # Criptografia y hashing
│   ├── 📁 Database/               # Capa de base de datos (PDO, migrador, seeder)
│   ├── 📁 Errors/                 # Manejo de errores (ExceptionHandler)
│   ├── 📁 Exceptions/             # Excepciones personalizadas
│   ├── 📁 Helpers/                # Funciones helper globales
│   ├── 📁 Http/                   # Capa HTTP (Request, Response, Middleware, Pipeline)
│   ├── 📁 Model/                  # ORM simplificado
│   ├── 📁 Provider/               # Service Providers del framework
│   ├── 📁 Routing/                # Sistema de enrutamiento
│   ├── 📁 Session/                # Sistema de sesiones
│   ├── 📁 Storage/                # Almacenamiento
│   ├── 📁 Validation/             # Sistema de validacion
│   └── 📁 View/                   # Motor de plantillas Blade (BladeEngine)
├── 📁 storage/
│   ├── 📁 cache/                  # Cache de vistas compiladas (invalidacion automatica)
│   └── 📁 logs/                   # Logs de aplicacion
├── 📁 tests/                      # Suite del framework (Unit) + demo (Integration)
├── 📄 .env                        # Variables de entorno (no versionado)
├── 📄 .gitignore
├── 📄 composer.json
├── 📄 cronos                      # Ejecutable CLI
└── 📄 README.md
```

## Constantes Generales

```php
ROOT        // Path raiz del proyecto
DIR_PUBLIC  // path.../public
DIR_IMG     // path.../public/PATH_FILE_STORAGE (definido en .env)
```

## Flujo de Ejecucion (Request Lifecycle)

1. `public/index.php` recibe la peticion HTTP y llama a `Cronos\App::bootstrap()`
2. **Bootstrap** inicializa: ExceptionHandler, variables .env, archivos de config, Service Providers (boot), Request, Response, Router, sesiones, conexion a BD, Service Providers (runtime), helpers globales
3. **`App::run()`** llama a `$this->router->resolve($this->request)` dentro de un try-catch
4. **Router** busca la ruta coincidente, obtiene la accion y middlewares, parsea parametros dinamicos
5. **Pipeline de Middlewares**: globales → ruta → controlador (patron onion)
6. **Controlador**: se instancia y se llama al metodo con los parametros de la URL
7. **Modelo**: usa PdoDriver para ejecutar queries SQL
8. **Vista**: BladeEngine compila directivas Blade a PHP con cache por dependencias en `storage/cache/views/`
9. **Response**: se envian headers y contenido (JSON, HTML o redireccion)
10. **Finalizacion**: se cierra la sesion, se limpia flash data

---

## Protocolo de Limpieza: iniciar un proyecto nuevo desde esta base

**Cuando se aplica:** al copiar este repositorio para crear una aplicacion nueva. **Nunca en el repo base** (alli todo existe para que los tests del framework sigan en verde: los tests de integracion del ORM usan los modelos demo como fixtures).

Ejecutar los pasos EN ORDEN: cada paso deja el proyecto ejecutable. Al final hay un checklist de verificacion.

### Paso 0: leer la documentacion

Leer al menos 00 (este), 01, 02, 03, 04, 06 y 14 antes de borrar o escribir nada. La tabla de arriba indica que cubre cada una.

### Paso 1: rutas demo (`routes/`)

- `routes/api.php`: eliminar TODAS las rutas demo (register, login, me, logout, blogs). Queda el archivo con el `use Cronos\Routing\Route;` y nada mas.
- `routes/web.php`: eliminar login, register y dashboard (rutan hacia SpaController como demo). Dejar `/` apuntando a la portada que vaya a usar el proyecto:

```php
<?php

use Cronos\Routing\Route;
use App\Controllers\SpaController;

Route::get('/', [SpaController::class, 'index'])->name('home.index');

// rutas del proyecto nuevo aqui
```

> Si el proyecto sera 100% SPA, esa unica ruta basta. Si habra paginas server-side, apuntar `/` al controlador del proyecto y leer 01-rutas.md y 11-guia-nuevo-modulo.md.

### Paso 2: controladores demo (`App/Controllers/`)

| Archivo | Accion |
|---|---|
| `AuthController.php` | ELIMINAR (demo de autenticacion) |
| `PublicacionController.php` | ELIMINAR (demo del blog) |
| `HomeController.php` + vista `home/index.php` | ELIMINAR (landing demo) |
| `SpaController.php` | **CONSERVAR SIEMPRE**: sirve `resources/views/spa/index.php`, la shell de la SPA |

### Paso 3: modelos (`App/Models/`)

ELIMINAR TODOS: `Usuario`, `Perfil`, `TokenAcceso`, `RestablecimientoContrasena`, `Categoria`, `Etiqueta`, `Publicacion`, `Comentario`, `Rol`. Ninguno pertenece al framework. Los modelos del proyecto se crean con:

```bash
php cronos make:model Producto
```

Antes de escribir modelos, leer **04-modelos.md** completo (relaciones, casts, scopes y el checklist final para IAs).

### Paso 4: migraciones y seeders (`App/Migrations/`, `App/Seeders/`)

- ELIMINAR las 13 migraciones demo.
- ELIMINAR los 8 seeders de datos (`UsuarioSeeder`, `PerfilSeeder`, `CategoriaSeeder`, `EtiquetaSeeder`, `PublicacionSeeder`, `ComentarioSeeder`, `RolSeeder`, `TokenSeeder`).
- **CONSERVAR `DatabaseSeeder.php`** (es el punto de entrada de `db:seed`) dejandolo vacio:

```php
<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
```

- Limpiar la BD y dejarla lista para el proyecto:

```bash
php cronos migrate:fresh      # elimina TODAS las tablas y deja la BD vacia
```

- Crear las migraciones propias con `php cronos make:migration create_NOMBRE_table` (ver 02 y 14).

### Paso 5: vistas (`resources/views/`)

| Carpeta / archivo | Accion |
|---|---|
| `spa/` | **JAMAS TOCAR.** Shell de la SPA servida por SpaController |
| `home/` | ELIMINAR (landing demo; su controlador se borro en paso 2) |
| `errors/404.php` | **CONSERVAR OBLIGATORIO**: `ExceptionHandler` renderiza `view('errors.404')` ante un 404. Si se elimina, recrearla como HTML autonomo (sin layout) |
| `components/` | CONSERVAR: libreria base reutilizable (`x-button`, `x-alert`, `x-badge`, `x-card`, `x-input`, `x-textarea`). Cada archivo documenta sus props. Contrato completo en 06-vistas.md seccion 12 |
| `layouts/app.php` | CONSERVAR: esqueleto base (`@yield('content')`, `@yield('title')`, stacks de estilos/scripts) para las paginas server-side del proyecto |
| `partials/nav.php` | CONSERVAR pero **EDITAR**: sus links a `route('login.index')` y `route('register.index')` apuntan a rutas demo eliminadas en el paso 1 y romperian el render. Quitar esos `<li>` (o todo el menu demo) dejando solo la marca |

> Antes de escribir o editar CUALQUIER vista, leer **06-vistas.md** (herencia, componentes, slots, escape y el cheat sheet final para IAs).

### Paso 6: middlewares y librerias demo (`App/Middlewares/`, `App/Library/`, `App/Help/`)

| Archivo | Accion |
|---|---|
| `CorsMiddleware.php` | CONSERVAR si la SPA consumira la API (ver config/cors.php y 10-configuracion.md) |
| `AuthMiddleware.php`, `AuthApiMiddleware.php`, `TokenValidationMiddleware.php` | ELIMINAR (pertenecen a la demo de auth) |
| `DahboardMiddleware.php` | ELIMINAR (protegia la ruta `/` demo eliminada en paso 1) |
| `LogRequestMiddleware.php`, `ThrottleMiddleware.php` | OPCIONALES y genericos: conservar si el proyecto los usara |
| `App/Library/JWT/JWTAuth.php` | ELIMINAR (solo lo usa AuthController); si el proyecto necesitara JWT, reimplementar con 09/10 como guia |
| `App/Help/*` | Genericos (imagenes/archivos): conservar o eliminar segun uso |

### Paso 7: assets (`public/assets/`, `resources/css/`)

- `resources/css/app.css` (fuente Tailwind) y `error.css` (lo usa `errors/404.php`): CONSERVAR.
- Regla general: conservar solo los assets que las vistas conservadas referencien con `@asset()`. Ojo: `layouts/app.php` referencia `assets/css/home.css` y `assets/js/home.js`, asi que se quedan mientras el layout exista (renombrarlos a `app.css`/`app.js` es opcional, actualizando el layout). El resto de assets del demo, ELIMINAR. Al agregar estilos nuevos, leer 12-tailwindcss.md.
- Revisar `public/assets/` con la misma regla: se queda lo referenciado por vistas que siguen vivas.

### Paso 8: tests (`tests/`)

- **Repo base: NO tocar.** Los tests de `tests/Integration/` usan los modelos demo de `App/Models/` como fixtures del ORM y deben seguir en verde.
- **Proyecto derivado:** eliminar `tests/Integration/` (incluye `Fixtures/`) y `tests/TestCase/OrmTestCase.php`: prueban el framework contra los modelos demo que ya no existen. Los tests `tests/Unit/` NO dependen de `App/` y siguen en verde: son la red de seguridad del nucleo.

### Paso 9: verificacion final (checklist)

```bash
php vendor\bin\phpunit --no-coverage   # tests Unit en verde (Integration ya no aplica en proyecto derivado)
php -S localhost:8099 -t public        # o el servidor de preferencia
```

| Verificacion | Esperado |
|---|---|
| `GET /` responde | 200 con la shell SPA (o la portada del proyecto) |
| Ruta inexistente (ej. `/no-existe`) | pagina 404 renderizada desde `errors/404.php` |
| `php cronos migrate:status` | sin migraciones pendientes de la demo; solo las del proyecto |
| `php cronos db:seed` | corre sin error (DatabaseSeeder vacio) |
| `php vendor\bin\phpunit` | OK en tests Unit |
| `storage/cache/views` | se regenera solo; no requiere accion |

### Resumen del protocolo en una tabla

| Destino | Eliminar | Conservar |
|---|---|---|
| `routes/` | rutas demo (web y api) | estructura de archivos, `/` apuntando a la portada real |
| `App/Controllers` | AuthController, PublicacionController, HomeController | SpaController |
| `App/Models` | TODOS | (los nuevos del proyecto via make:model) |
| `App/Migrations` | TODAS | (las nuevas via make:migration) |
| `App/Seeders` | los 8 seeders de datos | DatabaseSeeder.php (vacio) |
| `App/Middlewares` | auth demo + DahboardMiddleware | CorsMiddleware (si hay API/SPA); Log/Throttle opcionales |
| `App/Library`, `App/Help` | JWT (sin auth demo) | Help si se usa |
| `resources/views` | `home/`, links demo del nav | `spa/` (JAMAS), `errors/404.php`, `components/`, `layouts/`, `partials/` |
| `public/assets`, `resources/css` | assets demo sin referencias | app.css, error.css y lo que `@asset()` referencie |
| `tests/` (solo proyecto derivado) | Integration/ + OrmTestCase | Unit/ |
| `System/`, `config/`, `public/index.php`, `cronos`, `storage/` | NADA | TODO (nucleo) |

---

> **Siguiente**: [01 - Rutas](01-rutas.md)
