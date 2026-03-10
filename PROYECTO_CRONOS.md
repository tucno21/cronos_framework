# PROYECTO CRONOS — Documentación Técnica para IAs

## 1. Descripción General

Cronos Framework es un mini-framework PHP inspirado en Laravel, diseñado específicamente para proyectos pequeños como proyectos académicos o prototipos simples. Su propósito principal es imitar las funcionalidades básicas y más utilizadas de Laravel, proporcionando una estructura MVC simplificada pero funcional que permite a los desarrolladores construir aplicaciones web sin la complejidad de Laravel completo.

La filosofía de diseño de Cronos se centra en la simplicidad y el aprendizaje: implementa los patrones fundamentales de Laravel (enrutamiento, controladores, modelos con ORM básico, sistema de vistas, middleware, validación y sesiones) pero de manera simplificada. Por ejemplo, su sistema de vistas es un motor de plantillas personalizado que compila directivas tipo Blade a PHP puro, su ORM soporta relaciones básicas (hasOne, hasMany, belongsTo, belongsToMany) y consultas encadenadas, y su sistema de enrutamiento imita la sintaxis de Laravel con apoyo para parámetros dinámicos y grupos.

Cronos simplifica aspectos avanzados de Laravel: no implementa Service Providers complejos, no tiene sistema de eventos, no soporta colas, jobs o notificaciones, y su sistema de migraciones es básico (un solo archivo Database.php). Sin embargo, mantiene la filosofía de Laravel en cuanto a separación de responsabilidades, uso de contenedor de dependencias simple, y estructura de carpetas convencional. El framework incluye un CLI propio para generar controladores, modelos, middleware y migraciones, similar a Artisan de Laravel.

El framework está diseñado para ser educativo y funcional, permitiendo a los desarrolladores comprender cómo funciona un framework MVC mientras construyen aplicaciones reales. Incluye funcionalidades como autenticación de sesión y JWT API, validación robusta con múltiples reglas, sistema de middleware en cadena, y un motor de plantillas con directivas personalizables.

## 2. Árbol de Archivos y Carpetas

```
cronos_framework/
├── public/
│   ├── index.php                 # Punto de entrada de la aplicación
│   ├── .htaccess                  # Configuración Apache para reescritura de URLs
│   ├── assets/                   # Archivos estáticos (CSS, JS, imágenes)
│   │   ├── index.css
│   │   ├── index.js
│   │   ├── cronos.dashboard.css
│   │   ├── cronos.dashboard.js
│   │   └── blog.js
│   └── imagenes/                 # Carpeta para imágenes subidas
├── App/
│   ├── Controllers/              # Controladores de la aplicación
│   │   ├── HomeController.php    # Controlador de página principal
│   │   ├── LoginController.php   # Controlador de autenticación
│   │   ├── RegisterController.php # Controlador de registro
│   │   ├── DashboardController.php # Controlador del dashboard
│   │   └── ApiController.php     # Controlador para API endpoints
│   ├── Models/                   # Modelos de la aplicación
│   │   ├── User.php              # Modelo de usuarios
│   │   └── Blog.php              # Modelo de blogs
│   ├── Middlewares/              # Middleware personalizados
│   │   ├── AuthMiddleware.php    # Middleware de autenticación web
│   │   ├── AuthApiMiddleware.php # Middleware de autenticación API (JWT)
│   │   ├── DahboardMiddleware.php # Middleware de dashboard
│   │   ├── TokenValidationMiddleware.php # Middleware de validación de tokens
│   │   ├── CorsMiddleware.php    # Middleware para manejo de CORS (Cross-Origin Resource Sharing)
│   │   ├── ThrottleMiddleware.php # Middleware de rate limiting (limitación de peticiones)
│   │   └── LogRequestMiddleware.php # Middleware para logging de todas las peticiones HTTP
│   ├── Migrations/               # Migraciones de base de datos
│   │   └── Database.php          # Archivo único de migraciones
│   ├── Providers/                # Service Providers
│   │   └── RouteServiceProvider.php # Provider que carga las rutas
│   ├── Help/                     # Clases auxiliares
│   │   ├── ImageBuilder.php      # Constructor de imágenes
│   │   ├── LInkFile.php          # Manejo de enlaces
│   │   ├── MoveFile.php          # Mover archivos
│   │   └── MoveFileImagen.php    # Mover imágenes
│   └── Library/                  # Librerías personalizadas
│       └── JWT/
│           └── JWTAuth.php       # Clase para autenticación JWT
├── System/                      # Núcleo del framework
│   ├── App.php                   # Clase principal de la aplicación
│   ├── Config/                   # Sistema de configuración
│   │   └── Config.php            # Manejo de archivos de configuración
│   ├── ConsoleCLI/               # Sistema CLI (Artisan-like)
│   │   ├── ConsoleCLI.php       # Interfaz de línea de comandos
│   │   └── templates/           # Plantillas para generadores
│   │       ├── controller.php  # Plantilla de controlador
│   │       ├── model.php        # Plantilla de modelo
│   │       ├── middleware.php   # Plantilla de middleware
│   │       └── migration.php    # Plantilla de migración
│   ├── Container/               # Contenedor de dependencias
│   │   ├── Container.php        # Contenedor simple
│   │   └── DependencyInjection.php # Inyección de dependencias
│   ├── Crypto/                  # Criptografía y hashing
│   │   ├── Bcrypt.php           # Implementación de bcrypt
│   │   └── Hasher.php           # Interfaz de hashing
│   ├── Database/                # Capa de abstracción de base de datos
│   │   ├── DatabaseDriver.php   # Interfaz de driver de BD
│   │   ├── DatabaseMigrate.php  # Ejecutor de migraciones
│   │   ├── DBexecute.php        # Ejecutor de queries
│   │   └── PdoDriver.php        # Implementación PDO
│   ├── Errors/                  # Manejo de errores
│   │   ├── ExceptionHandler.php # Manejador centralizado de excepciones (captura Exception, Error y errores fatales)
│   │   ├── HttpNotFoundException.php # Excepción HTTP 404 Not Found
│   │   ├── HttpException.php    # Excepción HTTP genérica con código de estado configurable
│   │   ├── RouteException.php   # Excepción de rutas
│   │   ├── ValidationException.php # Excepción para errores de validación
│   │   ├── AuthorizationException.php # Excepción HTTP 403 Forbidden (acceso denegado)
│   │   └── MethodNotAllowedException.php # Excepción HTTP 405 Method Not Allowed
│   ├── Exceptions/              # Excepciones personalizadas
│   │   └── CronosException.php  # Excepción base del framework
│   ├── Helpers/                 # Funciones helper globales
│   │   ├── app.php              # Helpers de aplicación (app, configGet, env)
│   │   ├── debug.php            # Helpers de debug
│   │   ├── http.php             # Helpers HTTP (json, redirect, view, route)
│   │   ├── session.php          # Helpers de sesión
│   │   └── variable.php         # Variables globales
│   ├── Http/                    # Capa HTTP
│   │   ├── Controller.php       # Clase base de controladores
│   │   ├── HttpMethod.php       # Enum de métodos HTTP
│   │   ├── JsonResponse.php     # Respuestas JSON
│   │   ├── Middleware.php       # Interfaz de middleware
│   │   ├── MiddlewareGroup.php # Gestión de middlewares globales
│   │   ├── Pipeline.php        # Ejecución de middlewares en cadena (patrón onion)
│   │   ├── Request.php          # Manejo de peticiones HTTP
│   │   └── Response.php         # Manejo de respuestas HTTP
│   ├── Model/                   # ORM simplificado
│   │   ├── Model.php            # Clase base de modelos
│   │   └── ModelCollection.php   # Colección de modelos
│   ├── Provider/                # Service Providers del framework
│   │   ├── DatabaseDriverServiceProvider.php # Provider de BD
│   │   ├── HasherServiceProvider.php        # Provider de hashing
│   │   ├── ServiceProvider.php              # Interfaz base
│   │   ├── SessionStorageServiceProvider.php # Provider de sesión
│   │   └── ViewServiceProvider.php          # Provider de vistas
│   ├── Routing/                 # Sistema de enrutamiento
│   │   ├── Router.php          # Enrutador principal
│   │   └── Route.php           # Clase de ruta individual
│   ├── Session/                 # Sistema de sesiones
│   │   ├── Session.php         # Manejo de sesiones
│   │   ├── SessionStorage.php # Interfaz de almacenamiento
│   │   └── PhpNativeSessionStorage.php # Implementación nativa
│   ├── Storage/                 # Sistema de almacenamiento
│   │   └── Image.php           # Manejo de imágenes
│   ├── Validation/              # Sistema de validación
│   │   ├── Validation.php      # Validador principal
│   │   └── MessageError.php    # Mensajes de error
│   └── View/                    # Sistema de vistas
│       ├── View.php            # Interfaz de vista
│       └── CronosEngine.php    # Motor de plantillas (Blade-like)
├── routes/                      # Definición de rutas
│   ├── web.php                  # Rutas web (páginas)
│   └── api.php                  # Rutas API (JSON)
├── config/                      # Archivos de configuración
│   ├── app.php                  # Configuración de la app
│   ├── cors.php                 # Configuración CORS
│   ├── database.php             # Configuración de base de datos
│   ├── hashing.php              # Configuración de hashing
│   ├── providers.php            # Providers a cargar
│   ├── session.php              # Configuración de sesión
│   └── view.php                 # Configuración de vistas
├── resources/
│   └── views/                   # Archivos de vistas
│       ├── home/                # Vistas de home
│       │   ├── layouts/         # Layouts base
│       │   │   ├── head.php    # Head de HTML
│       │   │   └── footer.php  # Footer de HTML
│       │   ├── index.php       # Página principal
│       │   ├── login.php       # Formulario de login
│       │   └── register.php    # Formulario de registro
│       ├── dashboard/           # Vistas de dashboard
│       │   ├── layouts/         # Layouts de dashboard
│       │   │   ├── head.php    # Head de dashboard
│       │   │   └── footer.php  # Footer de dashboard
│       │   ├── index.php       # Página de dashboard
│       │   ├── show.php        # Vista de detalle
│       │   └── create.php      # Formulario de creación
│       └── error/               # Vistas de error
│           ├── 404.php         # Página no encontrada
│           ├── 403.php         # Acceso denegado
│           ├── 405.php         # Método no permitido
│           ├── 500.php         # Error interno del servidor
│           └── error.php       # Página genérica para errores HTTP
<diff>
  ------- SEARCH
### Logging
- ✅ Completo | ❌ No implementado
### Logging
- ✅ Completo | ✅ Implementado (Logger con rotación diaria)
├── storage/                     # Almacenamiento
│   ├── cache/                   # Cache de vistas compiladas
│   └── logs/                    # Logs de aplicación
├── tests/                       # Tests unitarios e integración
│   ├── RESUMEN_TESTS.md         # Resumen de tests
│   ├── Container/               # Tests de contenedor
│   ├── Crypto/                  # Tests de criptografía
│   ├── Helpers/                 # Tests de helpers
│   ├── Integration/             # Tests de integración
│   ├── Routing/                 # Tests de rutas
│   ├── Session/                 # Tests de sesión
│   ├── TestCase/                # Casos de test base
│   ├── Unit/                    # Tests unitarios
│   └── Validation/              # Tests de validación
├── .env.example                 # Ejemplo de archivo .env
├── .env.example2                # Segundo ejemplo de .env
├── .gitignore                   # Archivos a ignorar en Git
├── composer.json                 # Dependencias de Composer
├── cronos                       # Archivo ejecutable CLI (sin extensión)
├── cronos.sql                   # SQL de base de datos
├── phpunit.xml                  # Configuración de PHPUnit
├── Procfile                     # Configuración de despliegue
├── README.md                    # Documentación del proyecto
└── tarea.md                     # Especificación de tarea actual
```

## 3. Flujo de Ejecución (Request Lifecycle)

1. **`public/index.php`** recibe la petición HTTP → requiere el autoload de Composer y llama a `Cronos\App::bootstrap()`
2. **`Cronos\App::bootstrap()`** inicializa la aplicación:
   - Registra el ExceptionHandler global para capturar excepciones no manejadas
   - Carga las variables de entorno del archivo `.env` usando Dotenv
   - Carga los archivos de configuración de la carpeta `config/`
   - Ejecuta los Service Providers de tipo 'boot' (DatabaseDriver, SessionStorage, View, Hasher)
   - Instancia y configura Request, Response, Router
   - Inicializa el sistema de sesiones
   - Establece la conexión a la base de datos
   - Ejecuta los Service Providers de tipo 'runtime' (RouteServiceProvider)
   - Carga las funciones helper globales
3. **`Cronos\App::run()`** inicia el procesamiento:
   - Llama a `$this->router->resolve($this->request)` dentro de un try-catch
   - Si ocurre una excepción, el ExceptionHandler la captura y maneja
4. **`Cronos\Routing\Router::resolve()`** procesa la petición:
   - Busca la ruta que coincida con la URI actual y el método HTTP
   - Si no encuentra la ruta, lanza `HttpNotFoundException`
   - Obtiene la acción (controlador+método o closure) de la ruta
   - Obtiene los middlewares de la ruta
   - Si la acción es un array, instancia el controlador y une sus middlewares
   - Parsea los parámetros dinámicos de la URL
   - Construye el Pipeline con middlewares globales + middlewares de ruta + middlewares de controlador
5. **Ejecución del Pipeline de Middlewares**:
   - Se crea una instancia de `Pipeline` con el Request actual
   - El Pipeline recibe los middlewares en orden: globales → ruta → controlador
   - El Pipeline construye una cadena de closures usando el patrón "onion" (cebolla)
   - Cada middleware envuelve al siguiente, creando capas anidadas
   - El Request entra desde afuera hacia adentro (primer middleware → último middleware)
   - La Response retorna desde adentro hacia afuera (acción → último middleware → primer middleware)
6. **Ejecución de Middlewares individuales**:
   - Cada middleware implementa `handle(Request $request, Closure $next): Response`
   - El middleware puede procesar el Request (ej: CORS, rate limiting, logging)
   - Si el middleware NO llama a `$next($request)`, la cadena se rompe y se retorna la Response inmediatamente
   - Si el middleware llama a `$next($request)`, el Request pasa al siguiente middleware o acción
   - Ejemplos:
     - `LogRequestMiddleware` registra la petición y llama a `$next`
     - `CorsMiddleware` agrega headers CORS y llama a `$next`
     - `ThrottleMiddleware` verifica límite de peticiones; si excede, retorna 429; si no, llama a `$next`
     - `AuthMiddleware` verifica autenticación; si no autenticado, redirecciona; si autenticado, llama a `$next`
7. **Ejecución del Controlador**:
   - Se instancia el controlador especificado en la ruta
   - Se llama al método indicado con los parámetros de la URL
   - El controlador puede usar Modelos para consultar la base de datos
8. **Procesamiento del Modelo**:
   - El modelo usa `DatabaseDriver` (PdoDriver) para ejecutar queries SQL
   - Las queries se construyen mediante métodos encadenados (where, join, orderBy, etc.)
   - Los resultados se convierten en instancias del modelo
9. **Renderizado de la Vista**:
   - El controlador llama a `view('nombre.vista', ['data' => $data])`
   - `CronosViewEngine` compila las directivas Blade-like (`@foreach`, `@if`, etc.) a PHP
   - Las vistas se cachean en `storage/cache/`
   - Las variables se pasan a la vista usando `extract()`
10. **Envío de la Response**:
    - `Cronos\Http\Response::sendResponse()` envía los headers y el contenido
    - Para JSON: envía `Content-Type: application/json` y codifica los datos
    - Para vistas: envía el HTML renderizado
    - Para redirecciones: envía header `Location:`
11. **Finalización**:
    - Se cierra la sesión (se guardan los cambios)
    - Se ejecutan destructores para limpiar datos flash
    - El script termina

## 4. Componentes del "Mini-Framework" (Core)

### App — `System/App.php`
- **Responsabilidad:** Clase principal que orquesta toda la inicialización y ejecución de la aplicación. Es el punto central que conecta todos los componentes.
- **Métodos principales:**
  - `bootstrap(string $root)` - Inicializa la aplicación con la ruta raíz del proyecto
  - `loadConfig()` - Carga variables de entorno y archivos de configuración
  - `runServiceProvider(string $type)` - Ejecuta los service providers (boot o runtime)
  - `setHttpStartHandlers()` - Configura Request, Response y Router
  - `setSessionHandler()` - Inicializa el sistema de sesiones
  - `setUpDatabaseConnection()` - Establece conexión a la base de datos
  - `run()` - Ejecuta el ciclo de petición-respuesta
  - `abort(Response $response)` - Termina la aplicación con una respuesta específica
  - `terminate(Response $response)` - Envía la respuesta y termina la ejecución
- **Cómo se usa:** Se instancia en `public/index.php` con `App::bootstrap(__DIR__)->run()`
- **Equivalente en Laravel:** `Illuminate\Foundation\Application`

### Router — `System/Routing/Router.php`
- **Responsabilidad:** Sistema de enrutamiento que registra rutas y las resuelve contra peticiones HTTP
- **Métodos principales:**
  - `get(string $uri, Closure|array $action)` - Registra ruta GET
  - `post(string $uri, Closure|array $action)` - Registra ruta POST
  - `put(string $uri, Closure|array $action)` - Registra ruta PUT
  - `patch(string $uri, Closure|array $action)` - Registra ruta PATCH
  - `delete(string $uri, Closure|array $action)` - Registra ruta DELETE
  - `resolve(Request $request)` - Resuelve la ruta coincidente con la petición
  - `resolveRoute(Request $request)` - Busca la ruta que coincide con URI y método HTTP
  - `runMiddlewares(Request $request, array $middlewares, $target)` - Ejecuta la cadena de middlewares
  - `route(string $nameRoute, string|array $params)` - Genera URL desde nombre de ruta
  - `name(string $name)` - Asigna un nombre a la última ruta registrada
  - `setPrefix(string $prefix)` / `clearPrefix()` - Maneja prefijos de ruta
- **Cómo se usa:** Se instancia automáticamente por RouteServiceProvider, usado en archivos `routes/web.php` y `routes/api.php`
- **Equivalente en Laravel:** `Illuminate\Routing\Router`

### Route — `System/Routing/Route.php`
- **Responsabilidad:** Representa una ruta individual con su URI, acción, middlewares y parámetros
- **Métodos principales:**
  - `matches(string $uri)` - Verifica si la ruta coincide con una URI
  - `hasParameters()` - Indica si la ruta tiene parámetros dinámicos
  - `parseParameters(string $uri)` - Extrae los valores de los parámetros de la URL
  - `middleware(string|array $middlewares)` - Asigna middlewares a la ruta
  - `name(string $name)` - Asigna un nombre a la ruta
  - `group(array $attributes, callable $callback)` - Crea grupo de rutas con prefijos y middlewares
  - `load(string $routesDirectory)` - Carga todos los archivos PHP de una carpeta de rutas
- **Cómo se usa:** Se usa en archivos de rutas con `Route::get('/', ...)`
- **Equivalente en Laravel:** `Illuminate\Routing\Route`

### Controller — `System/Http/Controller.php`
- **Responsabilidad:** Clase base para todos los controladores de la aplicación
- **Métodos principales:**
  - `validate(array|object $inputs, array $rules)` - Valida datos usando el sistema de validación
  - `middleware(string|array $middlewares)` - Asigna middlewares al controlador
  - `middlewares()` - Retorna los middlewares del controlador
- **Cómo se usa:** Todos los controladores de `App/Controllers/` heredan de esta clase
- **Equivalente en Laravel:** `Illuminate\Routing\Controller`

### Model — `System/Model/Model.php`
- **Responsabilidad:** ORM simplificado que proporciona abstracción de base de datos con relaciones
- **Métodos principales:**
  - `create(array|object $data)` - Crea un nuevo registro en la BD
  - `update(int|string $id, array|object $data)` - Actualiza un registro existente
  - `delete(int|string $id)` - Elimina un registro
  - `find(int|string $id)` - Busca un registro por ID
  - `all()` - Retorna todos los registros
  - `select(string ...$select)` - Selecciona columnas específicas
  - `join(string $table, string $first, string $operator, string $second)` - Realiza JOIN
  - `where(string $columna, string|int $operadorOvalor, string|int|null $valor)` - Agrega condición WHERE
  - `andWhere(string $columna, ...)` / `orWhere(string $columna, ...)` - Agrega condiciones adicionales
  - `whereBetween(string $columna, string|int $valor1, string|int $valor2)` - Condición BETWEEN
  - `orderBy(string $column, string $direction)` - Ordena resultados
  - `limit(int $limit)` - Limita cantidad de resultados
  - `first()` - Retorna el primer resultado
  - `get()` - Ejecuta la consulta y retorna colección
  - `max()` / `min()` / `sum()` / `avg()` - Funciones de agregación
  - `hasOne()` / `hasMany()` / `belongsTo()` / `belongsToMany()` - Define relaciones
  - `toArray()` / `toObject()` - Convierte modelo a array u objeto
- **Cómo se usa:** Los modelos en `App/Models/` heredan de esta clase y definen `$table`, `$primaryKey`, `$fillable`
- **Equivalente en Laravel:** `Illuminate\Database\Eloquent\Model` (versión simplificada)

### CronosEngine — `System/View/CronosEngine.php`
- **Responsabilidad:** Motor de plantillas que compila directivas Blade-like a PHP puro
- **Métodos principales:**
  - `render(string $view, array $params)` - Renderiza una vista con parámetros
  - `directive(string $name, callable $handler)` - Registra directiva personalizada
- **Cómo se usa:** Instanciado por ViewServiceProvider, usado a través de la función `view()`
- **Equivalente en Laravel:** `Illuminate\View\Compilers\BladeCompiler`

### Session — `System/Session/Session.php`
- **Responsabilidad:** Manejo de sesiones con soporte para datos flash y persistente
- **Métodos principales:**
  - `set(string $key, array|object $value)` - Almacena valor en sesión
  - `get(string $key, $default)` - Obtiene valor de sesión
  - `has(string $key)` - Verifica si existe clave
  - `remove(string $key)` / `forget(string $key)` - Elimina valor
  - `flash(string $key, mixed $value)` - Almacena valor solo para la siguiente petición
  - `attempt(mixed $value)` / `user()` / `hasUser()` / `logout()` - Autenticación
  - `setErrorsInputs(array|object $dataInput, array|object $errors)` - Guarda errores de validación
  - `error(string $key)` / `old(string $key)` - Obtiene errores y datos antiguos
  - `deleteErrorsInputs()` - Limpia errores de validación
  - `previousPath(string $path)` - Guarda ruta anterior para redirección
  - `flush()` - Elimina toda la sesión excepto claves del framework
  - `destroy()` - Destruye la sesión completa
- **Cómo se usa:** Disponible globalmente como `session()`
- **Equivalente en Laravel:** `Illuminate\Session\Store`

### Validation — `System/Validation/Validation.php`
- **Responsabilidad:** Sistema de validación de datos con múltiples reglas predefinidas
- **Métodos principales:**
  - `validate(array|object $inputs, array $rules)` - Valida datos contra reglas
- **Reglas soportadas:** required, email, url, alpha, alpha_dash, alpha_space, alpha_numeric, alpha_numeric_space, decimal, integer, is_natural, is_natural_no_zero, numeric, string, text, min, max, between, date, time, datetime, confirm, matches, slug, choice, unique, not_unique, password_verify, requiredFile, maxSize, type
- **Cómo se usa:** Desde controladores con `$this->validate($request->all(), $rules)`
- **Equivalente en Laravel:** `Illuminate\Validation\Validator`

### Request — `System/Http/Request.php`
- **Responsabilidad:** Encapsula la petición HTTP y permite acceder a sus datos
- **Métodos principales:**
  - `all()` - Retorna todos los datos de la petición
  - `input(string $key, $default)` - Obtiene un dato específico
  - `has(string $key)` - Verifica si existe un dato
  - `method()` - Retorna el método HTTP
  - `uri()` - Retorna la URI de la petición
  - `isPost()` / `isGet()` - Verifica método HTTP
- **Cómo se usa:** Se inyecta automáticamente en los métodos de controlador
- **Equivalente en Laravel:** `Illuminate\Http\Request`

### Response — `System/Http/Response.php`
- **Responsabilidad:** Genera diferentes tipos de respuestas HTTP
- **Métodos principales:**
  - `static json(array|object $data, int $statusCode = 200)` - Respuesta JSON
  - `static redirect(string $url = null)` - Redirección HTTP
  - `static back()` - Redirección a la página anterior
  - `static view(string $viewName, array $params = [], string $layout = null)` - Renderiza vista
  - `sendResponse(mixed $response)` - Envía la respuesta al cliente
  - `setStatusCode(int $code)` - Establece código de estado HTTP
- **Cómo se usa:** A través de helpers globales: `json()`, `redirect()`, `back()`, `view()`
- **Equivalente en Laravel:** `Illuminate\Http\Response` y `Illuminate\Http\RedirectResponse`

### ConsoleCLI — `System/ConsoleCLI/ConsoleCLI.php`
- **Responsabilidad:** Interfaz de línea de comandos para generar código (Artisan-like)
- **Métodos principales:**
  - `run()` - Ejecuta el comando especificado
  - `make:controller name folderName(optional)` - Genera controlador
  - `make:model name folderName(optional)` - Genera modelo
  - `make:middleware name` - Genera middleware
  - `make:migration database` - Genera archivo de migración
  - `migrate` - Ejecuta las migraciones
- **Cómo se usa:** Desde terminal: `php cronos make:controller UserController`
- **Equivalente en Laravel:** `Illuminate\Console\Application` (Artisan)

### PdoDriver — `System/Database/PdoDriver.php`
- **Responsabilidad:** Implementación de DatabaseDriver usando PDO
- **Métodos principales:**
  - `connect(string $protocol, string $host, int $port, string $database, string $username, string $password)` - Conecta a BD
  - `statement(string $query, array $bind)` - Ejecuta query SELECT
  - `statementC_U_D(string $query, array $bind)` - Ejecuta query INSERT/UPDATE/DELETE
  - `lastInsertId()` - Retorna el último ID insertado
  - `close()` - Cierra la conexión
- **Cómo se usa:** Instanciado por DatabaseDriverServiceProvider
- **Equivalente en Laravel:** `Illuminate\Database\Connection`

### Pipeline — `System/Http/Pipeline.php`
- **Responsabilidad:** Ejecuta middlewares en cadena usando el patrón "onion" (cebolla)
- **Métodos principales:**
  - `send(Request $request)` - Envía la petición al pipeline
  - `through(array $middlewares)` - Define los middlewares a ejecutar
  - `then(callable $destination)` - Ejecuta el pipeline con el destino final
  - `run(callable $destination)` - Alias de then()
  - `when(bool $condition, array $middlewares)` - Agrega middlewares condicionalmente
  - `unless(bool $condition, array $middlewares)` - Agrega middlewares si la condición es false
- **Cómo se usa:** El Router usa Pipeline para ejecutar middlewares globales, de ruta y de controlador
- **Equivalente en Laravel:** `Illuminate\Pipeline\Pipeline`

### MiddlewareGroup — `System/Http/MiddlewareGroup.php`
- **Responsabilidad:** Gestiona los middlewares globales configurados en config/app.php
- **Métodos principales:**
  - `getGlobalMiddlewares()` - Retorna los middlewares globales configurados
  - Soporta tanto nombres de clase como instancias ya creadas
- **Cómo se usa:** Router llama a MiddlewareGroup::getGlobalMiddlewares() para obtener los middlewares que se ejecutan en todas las rutas
- **Equivalente en Laravel:** `Illuminate\Routing\MiddlewareNameResolver` (función similar)

### Container — `System/Container/Container.php`
- **Responsabilidad:** Contenedor de dependencias simple para inyección y singletons
- **Métodos principales:**
  - `singleton(string $class, string|callable|null $build)` - Registra singleton
  - `resolve(string $class)` - Resuelve una instancia del contenedor
- **Cómo se usa:** Usado internamente por todo el framework para gestionar instancias
- **Equivalente en Laravel:** `Illuminate\Container\Container`

## 5. Sistema de Enrutamiento

### Cómo se registran las rutas

Las rutas se definen en los archivos `routes/web.php` (para páginas web) y `routes/api.php` (para API endpoints). Las rutas se cargan automáticamente por `RouteServiceProvider` que ejecuta `Route::load(App::$root . "/routes")`.

**Sintaxis básica:**
```php
use Cronos\Routing\Route;

// Ruta con closure
Route::get('/', function() {
    return view('home.index');
});

// Ruta con controlador
Route::get('/dashboard', [DashboardController::class, 'index']);

// Ruta con parámetro dinámico
Route::get('/dashboard/{blog:slug}', [DashboardController::class, 'show']);

// Ruta con parámetro sin restricción
Route::get('/user/{id}', [UserController::class, 'show']);

// Ruta nombrada
Route::get('/login', [LoginController::class, 'index'])->name('login.index');

// Ruta con middleware
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(AuthMiddleware::class);
```

### Tipos de rutas soportadas

- **GET**: `Route::get($uri, $action)`
- **POST**: `Route::post($uri, $action)`
- **PUT**: `Route::put($uri, $action)`
- **PATCH**: `Route::patch($uri, $action)`
- **DELETE**: `Route::delete($uri, $action)`

### Cómo se pasan parámetros dinámicos en la URL

Los parámetros dinámicos se definen con llaves `{}`. Se pueden agregar restricciones usando dos puntos `{parametro:restriccion}`.

**Ejemplos:**
```php
// Parámetro sin restricción: acepta cualquier valor alfanumérico
Route::get('/user/{id}', [UserController::class, 'show']);

// Parámetro con restricción específica (slug)
Route::get('/blog/{blog:slug}', [BlogController::class, 'show']);

// Múltiples parámetros
Route::get('/category/{category}/post/{post}', [PostController::class, 'show']);
```

En el controlador, los parámetros se inyectan automáticamente:
```php
public function show(Blog $blog) {
    // $blog ya está resuelto por el framework
    return view('blog.show', ['blog' => $blog]);
}
```

### Ejemplo real de una ruta definida en el proyecto

Desde `routes/web.php`:
```php
Route::get('/dashboard/{blog:slug}', [DashboardController::class, 'show']);
```

### Cómo conecta la ruta con el controlador

Cuando se define una ruta como `[DashboardController::class, 'show']`:
1. El Router instancia `DashboardController`
2. El Router llama al método `show($blog)` del controlador
3. Si la URI es `/dashboard/mi-primer-blog`, el parámetro `$blog` se resuelve usando el modelo Blog buscando por el campo `slug` con valor `'mi-primer-blog'`
4. El resultado se usa para inyectar el modelo en el método del controlador

Para generar URLs desde el nombre de ruta:
```php
// En un controlador o vista
$url = route('dashboard.index');
// Genera: http://cronos_framework.test/dashboard

// Con parámetros
$url = route('dashboard.show', 'mi-blog');
// Genera: http://cronos_framework.test/dashboard/mi-blog
```

## 6. Controladores

### Convención de nombres

- Los controladores deben estar en `App/Controllers/`
- El nombre del archivo debe coincidir con el nombre de la clase en **PascalCase**
- Ejemplo: `DashboardController.php` contiene la clase `DashboardController`
- Todos los controladores deben terminar con el sufijo `Controller`
- Namespace: `namespace App\Controllers;`

### Cómo se estructura un controlador típico

```php
<?php

namespace App\Controllers;

use Cronos\Http\Controller;
use Cronos\Http\Request;
use App\Middlewares\AuthMiddleware; // Opcional

class DashboardController extends Controller
{
    // Constructor opcional para middlewares
    public function __construct()
    {
        $this->middleware(AuthMiddleware::class);
    }

    // Métodos públicos que corresponden a rutas
    public function index()
    {
        return view('dashboard.index', [
            'pageTitle' => 'Dashboard'
        ]);
    }

    public function create()
    {
        return view('dashboard.create');
    }

    public function store(Request $request)
    {
        // Validación
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string'
        ]);

        if ($valid !== true) {
            return json(['status' => 'error', 'message' => $valid]);
        }

        // Lógica de negocio
        $blog = Blog::create($request->all());

        return json(['status' => 'success', 'blog' => $blog]);
    }

    public function show(Blog $blog)
    {
        return view('dashboard.show', ['blog' => $blog]);
    }

    public function edit(Blog $blog)
    {
        return json($blog);
    }

    public function update(Request $request, Blog $blog)
    {
        // Validación y actualización
        $blog = Blog::update($blog->id, $request->all());
        return json(['status' => 'success', 'blog' => $blog]);
    }

    public function destroy(Blog $blog)
    {
        Blog::delete($blog->id);
        return json(['status' => 'success']);
    }
}
```

### Clase base de la que heredan

Todos los controladores heredan de `Cronos\Http\Controller`.

### Métodos auxiliares disponibles

Desde `Cronos\Http\Controller`:
- `validate(array|object $inputs, array $rules)` - Valida datos
- `middleware(string|array $middlewares)` - Asigna middlewares al controlador

Desde helpers globales (`System/Helpers/http.php`):
- `view(string $viewName, array $params = [])` - Renderiza una vista
- `json(array|object $data, int $statusCode = 200)` - Retorna JSON
- `redirect(string $url = null)` - Redirecciona a URL
- `back()` - Redirecciona a la página anterior
- `route(string $nameRoute, string|array $params = null)` - Genera URL desde nombre

### Ejemplo real de un controlador del proyecto

`App/Controllers/DashboardController.php`:
```php
<?php

namespace App\Controllers;

use App\Models\Blog;
use App\Models\User;
use Cronos\Http\Request;
use Cronos\Http\Controller;
use App\Middlewares\AuthMiddleware;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(AuthMiddleware::class);
    }

    public function index()
    {
        return view('dashboard/index', [
            'pageTitle' => 'Dashboard'
        ]);
    }

    public function blogs()
    {
        $blogs = Blog::select('blogs.*', 'users.name')
            ->join('users', 'users.id', '=', 'blogs.user_id')
            ->get();

        return json($blogs);
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'slug' => 'required|slug|unique:Blog,slug',
            'content' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            $data = [
                'status' => 'error',
                'message' => $valid
            ];
            return json($data);
        }

        $data = $request->all();
        $data->user_id = session()->user()->id;

        $blog = Blog::create($data);

        return json([
            'status' => 'success',
            'message' => 'Blog created successfully',
            'blog' => $blog
        ]);
    }

    public function show(Blog $blog)
    {
        $user = User::find($blog->user_id);
        $blog->name = $user->name;

        return view('dashboard/show', [
            'blog' => $blog,
            'pageTitle' => $blog->name
        ]);
    }
}
```

## 7. Modelos

### Cómo se conectan a la base de datos

Los modelos usan la clase `DatabaseDriver` (implementación `PdoDriver`) que se configura en `Cronos\App::setUpDatabaseConnection()`. La conexión se establece usando los valores del archivo `config/database.php` que a su vez lee del archivo `.env`.

Para usar un modelo, no es necesario instanciar la conexión manualmente; la clase `Model` base maneja la conexión de forma estática.

### Clase base y métodos disponibles

Todos los modelos heredan de `Cronos\Model\Model` y deben definir:

```php
class User extends Model
{
    protected string $table = 'users';           // Nombre de la tabla
    protected string $primaryKey = 'id';        // Clave primaria
    protected array $fillable = ['name', 'email', 'password']; // Campos asignables
    protected array $hidden = ['password'];      // Campos ocultos en toArray/toObject
    protected bool $timestamps = true;           // Habilita timestamps automáticos
    protected string $created = 'created_at';   // Nombre campo created
    protected string $updated = 'updated_at';   // Nombre campo updated
}
```

**Métodos de consulta:**
- `create(array|object $data)` - Crea nuevo registro
- `update(int|string $id, array|object $data)` - Actualiza registro
- `delete(int|string $id)` - Elimina registro
- `find(int|string $id)` - Busca por ID
- `all()` - Retorna todos los registros
- `first()` - Retorna el primer resultado de la consulta
- `get()` - Ejecuta consulta y retorna colección

**Métodos de construcción de query:**
- `select(string ...$select)` - Selecciona columnas
- `join(string $table, string $first, string $operator, string $second)` - JOIN
- `where(string $columna, string|int $operadorOvalor, string|int|null $valor)` - WHERE
- `andWhere(string $columna, ...)` / `orWhere(string $columna, ...)` - WHERE adicionales
- `whereBetween(string $columna, string|int $valor1, string|int $valor2)` - BETWEEN
- `orderBy(string $column, string $direction)` - ORDER BY
- `limit(int $limit)` - LIMIT

**Funciones de agregación:**
- `max()` - Valor máximo
- `min()` - Valor mínimo
- `sum()` - Suma
- `avg()` - Promedio

**Relaciones:**
- `hasOne(string $related, ?string $foreignKey, string $localKey)` - Relación 1:1
- `hasMany(string $related, ?string $foreignKey, string $localKey)` - Relación 1:N
- `belongsTo(string $related, ?string $foreignKey, string $ownerKey)` - Relación N:1
- `belongsToMany(string $related, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey)` - Relación N:M

**Otros métodos:**
- `toArray()` / `toObject()` - Convierte a array u objeto
- `dd()` - Debug de la query SQL generada
- `customQuery(string $query, array|object $data)` - Ejecuta query personalizada

### Cómo se hacen consultas

Las consultas se construyen encadenando métodos:

```php
// Consulta básica
$blogs = Blog::all();
$blog = Blog::find(1);

// Con filtros
$blogs = Blog::where('status', 'published')->get();
$blog = Blog::where('slug', 'mi-blog')->first();

// Con joins
$blogs = Blog::select('blogs.*', 'users.name')
    ->join('users', 'users.id', '=', 'blogs.user_id')
    ->where('blogs.status', 'published')
    ->orderBy('blogs.created_at', 'DESC')
    ->limit(10)
    ->get();

// Con múltiples condiciones
$users = User::where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// Con BETWEEN
$posts = Post::whereBetween('created_at', '2024-01-01', '2024-12-31')
    ->get();

// Funciones de agregación
$maxViews = Blog::select('views')->max();
$avgRating = Product::select('rating')->avg();
```

### Equivalente a Eloquent: qué soporta y qué NO soporta

**Soportado:**
- ✅ CRUD básico (create, update, delete, find, all)
- ✅ Query builder encadenado (where, join, orderBy, limit)
- ✅ Relaciones (hasOne, hasMany, belongsTo, belongsToMany)
- ✅ Timestamps automáticos
- ✅ Campos fillable y hidden
- ✅ Funciones de agregación (min, max, sum, avg)
- ✅ Colecciones (ModelCollection)
- ✅ Conversión a array/object
- ✅ Custom queries
- ✅ Route model binding básico

**NO soportado:**
- ❌ Migrations (solo un archivo Database.php manual)
- ❌ Factories y Seeders
- ❌ Scopes
- ❌ Accessors y Mutators
- ❌ Casting de tipos automáticos
- ❌ Soft Deletes
- ❌ Events de modelo
- ❌ Observers
- ❌ Eager Loading (with)
- ❌ Polymorphic relations
- ❌ Query scopes globales y locales
- ❌ Validación de modelos
- ❌ Mass assignment protection más allá de $fillable
- ❌ Query avanzado (whereHas, whereDoesntHave, etc.)
- ❌ Subqueries
- ❌ Lazy loading de relaciones

### Ejemplo real de un modelo del proyecto

`App/Models/User.php`:
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

`App/Models/Blog.php`:
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

## 8. Vistas / Plantillas

### Dónde se ubican las vistas

Las vistas se ubican en `resources/views/`. Se organizan en carpetas según el área de la aplicación:
- `resources/views/home/` - Vistas públicas
- `resources/views/dashboard/` - Vistas del dashboard
- `resources/views/error/` - Vistas de error

### Cómo se renderizan desde los controladores

Las vistas se renderizan usando la función helper `view()`:

```php
// Vista simple
return view('home.index');

// Vista con parámetros
return view('dashboard.show', [
    'blog' => $blog,
    'pageTitle' => $blog->title
]);

// Vista con layout específico
return view('dashboard.index', ['data' => $data], 'layouts.admin');
```

La función `view()`:
1. Busca el archivo en `resources/views/` reemplazando `.` por `/`
2. Agrega la extensión `.php`
3. Compila las directivas Blade-like
4. Pasa las variables usando `extract()`
5. Retorna el HTML renderizado como Response

### Si hay sistema de layouts o plantillas base

Sí, el framework soporta layouts usando la directiva `@extends`:

**Layout base** (`resources/views/home/layouts/head.php`):
```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Cronos Framework' }}</title>
    <link rel="stylesheet" href="{{ base_url }}/assets/index.css">
</head>
<body>
    @yield('content')
</body>
</html>
```

**Vista que extiende el layout** (`resources/views/home/index.php`):
```php
@extends('home.layouts.head')

@section('content')
<div class="container">
    <h1>Bienvenido</h1>
    <p>{{ $message }}</p>
</div>
@endsection
```

### Cómo se pasan variables a las vistas

Las variables se pasan como array asociativo en el segundo parámetro de `view()`:

```php
return view('dashboard.index', [
    'pageTitle' => 'Dashboard',
    'blogs' => $blogs,
    'user' => $user,
    'error' => $error
]);
```

En la vista, las variables están disponibles directamente:
```php
<h1>{{ $pageTitle }}</h1>
@foreach($blogs as $blog)
    <div>{{ $blog->title }}</div>
@endforeach
```

### Sintaxis para imprimir variables

El framework usa sintaxis tipo Blade:

- `{{ $variable }}` - Imprime variable con escape de HTML (seguro contra XSS)
- `{!! $variable !!}` - Imprime variable sin escape (¡peligro! solo confiable)
- `{{ base_url }}` - Imprime la URL base del proyecto
- `{{ $array['key'] }}` o `{{ $object->property }}` - Acceso a datos

### Ejemplo real de una vista del proyecto

`resources/views/home/index.php`:
```php
@include('home.layouts.head')

<div class="bg-slate-100 mt-16 min-h-[calc(100vh-4rem)] flex items-center">
    <div class="container mx-auto">
        <div class="h-full flex flex-col md:flex-row gap-4 md:gap-6 p-3 md:p-6 justify-center items-center">
            <div class="flex-1 flex flex-col p-4">
                <h1 class="font-bold text-3xl md:text-4xl leading-tight">
                    Bienvenido
                </h1>
                <p class="mt-4 mb-4">
                    Este mini-framework, inspirado en Laravel y diseñado para proyectos pequeños, 
                    fue creado como parte de mi aprendizaje en PHP.
                </p>
                <a href="https://github.com/tucno21/cronos_framework" target="_blank" class="bg-blue-800 text-white px-3 py-2 rounded text-center">
                    Ver Proyecto
                </a>
            </div>
            <div class="flex-1 flex flex-col justify-center items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="currentColor" 
                     class="bi bi-cpu-fill text-blue-800" viewBox="0 0 16 16">
                    <!-- Icon SVG -->
                </svg>
                <span class="block font-bold text-2xl drop-shadow-lg text-blue-800">Cronos Framework</span>
                <span class="block font-bold text-2xl drop-shadow-lg text-blue-800">PHP</span>
            </div>
        </div>
    </div>
</div>

@include('home.layouts.footer')
```

**Directivas disponibles en CronosEngine:**
- `@extends('vista')` - Extiende un layout
- `@include('vista')` - Incluye otra vista
- `@section('nombre')` ... `@endsection` - Define una sección
- `@yield('nombre')` - Muestra una sección del layout
- `@foreach($array as $item)` ... `@endforeach` - Bucle foreach
- `@if($condition)` ... `@endif` - Condicionales (incluye `@elseif`, `@else`)
- `@for($i=0; $i<10; $i++)` ... `@endfor` - Bucle for
- `@while($condition)` ... `@endwhile` - Bucle while
- `@switch($var)` ... `@endswitch` - Switch
- `@isset($var)` ... `@endisset` - Verifica si existe
- `@empty($var)` ... `@endempty` - Verifica si está vacío
- `@component('vista', $params)` ... `@slot('nombre')` ... `@endslot` ... `@endcomponent` - Componentes con slots
- Directivas personalizadas registradas con `CronosEngine::directive('nombre', $handler)`

## 9. Configuración y Variables de Entorno

### Cómo se configura la base de datos

La configuración de la base de datos se hace en dos archivos:

**Archivo `.env` (en la raíz del proyecto):**
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cronos
DB_USERNAME=root
DB_PASSWORD=
```

**Archivo `config/database.php`:**
```php
<?php

return [
    'connection' => env('DB_CONNECTION', 'mysql'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'cronos'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
];
```

### Cómo se definen variables de entorno

Las variables de entorno se definen en el archivo `.env` en la raíz del proyecto. Se cargan automáticamente usando `vlucas/phpdotenv` en `Cronos\App::loadConfig()`.

**Ejemplo de `.env`:**
```env
APP_NAME=Cronos
APP_URL=http://cronos_framework.test
APP_FORMAT=web
TIME_ZONE=GMT

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cronos
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET_KEY=mi-clave-secreta-super-segura
SESSION_DRIVER=php
```

Para acceder a variables de entorno:
```php
// Desde código
$valor = env('NOMBRE_VARIABLE', 'valor_por_defecto');

// Desde archivos de configuración
'nombre' => env('APP_NAME', 'Cronos')
```

### Lista de todas las variables de configuración disponibles con su descripción

**Variables de entorno (.env):**
- `APP_NAME` - Nombre de la aplicación (default: 'Cronos')
- `APP_URL` - URL base de la aplicación (default: 'http://cronos_framework.test')
- `APP_FORMAT` - Formato de la aplicación: 'web' o 'api' (default: 'web')
- `TIME_ZONE` - Zona horaria de la aplicación (default: 'GMT')
- `DB_CONNECTION` - Protocolo de conexión a BD (default: 'mysql')
- `DB_HOST` - Host de base de datos (default: 'localhost')
- `DB_PORT` - Puerto de base de datos (default: 3306)
- `DB_DATABASE` - Nombre de la base de datos (default: 'cronos')
- `DB_USERNAME` - Usuario de base de datos (default: 'root')
- `DB_PASSWORD` - Contraseña de base de datos (default: '')
- `JWT_SECRET_KEY` - Clave secreta para tokens JWT (default: 'secret')

**Archivos de configuración:**

**`config/app.php`:**
```php
return [
    'name' => env('APP_NAME', 'Cronos'),
    'url' => env('APP_URL', 'http://cronos_framework.test'),
    'app_format' => env('APP_FORMAT', 'web'),
];
```

**`config/database.php`:** (ver arriba)

**`config/session.php`:**
Configuración del sistema de sesiones (no mostrado en archivos leídos)

**`config/view.php`:**
Configuración del sistema de vistas (no mostrado en archivos leídos)

**`config/hashing.php`:**
Configuración del sistema de hashing (no mostrado en archivos leídos)

**`config/cors.php`:**
Configuración de CORS (no mostrado en archivos leídos)

**`config/providers.php`:**
```php
return [
    'boot' => [
        Cronos\Provider\DatabaseDriverServiceProvider::class,
        Cronos\Provider\SessionStorageServiceProvider::class,
        Cronos\Provider\ViewServiceProvider::class,
        Cronos\Provider\HasherServiceProvider::class,
    ],
    'runtime' => [
        App\Providers\RouteServiceProvider::class,
    ]
];
```

## 10. Convenciones del Proyecto

### Nombres de archivos y clases

- **Controladores:** PascalCase con sufijo `Controller`
  - Archivo: `App/Controllers/UserController.php`
  - Clase: `class UserController extends Controller`
  
- **Modelos:** PascalCase, singular
  - Archivo: `App/Models/User.php`
  - Clase: `class User extends Model`
  
- **Middlewares:** PascalCase con sufijo `Middleware`
  - Archivo: `App/Middlewares/AuthMiddleware.php`
  - Clase: `class AuthMiddleware implements Middleware`
  
- **Vistas:** lowercase con puntos para separar carpetas
  - Archivo: `resources/views/dashboard/index.php`
  - Referencia: `view('dashboard.index')`
  
- **Rutas:** lowercase con guiones para palabras compuestas
  - URL: `/user-profile`
  
- **Tablas de BD:** lowercase, plural con guiones bajos
  - Tabla: `user_profiles`
  
- **Nombres de métodos:** camelCase
  - `getUserProfile()`, `createBlog()`

### Estructura de un nuevo módulo

Para agregar un nuevo módulo completo (ej: "Products"):

1. **Crear modelo:**
   - `App/Models/Product.php` con propiedades `$table`, `$primaryKey`, `$fillable`

2. **Crear controlador:**
   - `App/Controllers/ProductController.php` extendiendo `Controller`
   - Métodos: `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`

3. **Crear vistas:**
   - `resources/views/products/index.php`
   - `resources/views/products/show.php`
   - `resources/views/products/create.php`
   - `resources/views/products/edit.php`

4. **Crear migración:**
   - Editar `App/Migrations/Database.php` y agregar tabla `products`

5. **Registrar rutas:**
   - En `routes/web.php` agregar las rutas del módulo

### Cómo registrar una nueva ruta

En `routes/web.php` (para web) o `routes/api.php` (para API):

```php
use Cronos\Routing\Route;

// Ruta simple
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Ruta con parámetro
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

// Ruta POST
Route::post('/products', [ProductController::class, 'store']);

// Rutas con grupo y middleware
Route::group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function () {
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
});
```

### Cómo crear un nuevo controlador

**Manualmente:**
1. Crear archivo `App/Controllers/NewController.php`
2. Extender de `Cronos\Http\Controller`
3. Namespace: `namespace App\Controllers;`
4. Definir métodos públicos

**Usando CLI:**
```bash
php cronos make:controller NewController
# Para crear en subcarpeta:
php cronos make:controller Admin/NewController
```

Ejemplo generado:
```php
<?php

namespace App\Controllers;

use Cronos\Http\Controller;

class NewController extends Controller
{
    public function index()
    {
        return view('new.index');
    }
}
```

### Cómo crear un nuevo modelo

**Manualmente:**
1. Crear archivo `App/Models/NewModel.php`
2. Extender de `Cronos\Model\Model`
3. Namespace: `namespace App\Models;`
4. Definir `$table`, `$primaryKey`, `$fillable`

**Usando CLI:**
```bash
php cronos make:model NewModel
# Para crear en subcarpeta:
php cronos make:model Admin/NewModel
```

Ejemplo generado:
```php
<?php

namespace App\Models;

use Cronos\Model\Model;

class NewModel extends Model
{
    protected string $table = 'new_models';

    protected string $primaryKey = 'id';

    protected array $fillable = [];
}
```

### Dónde agregar helpers o funciones globales

Los helpers globales ya están definidos en `System/Helpers/`:
- `app.php` - Helpers de aplicación (app, configGet, env)
- `http.php` - Helpers HTTP (json, redirect, view, route)
- `session.php` - Helpers de sesión
- `variable.php` - Variables globales
- `debug.php` - Helpers de debug

Para agregar nuevos helpers:
1. Crear archivo en `System/Helpers/nuevo_helper.php`
2. Definir funciones globales
3. El archivo se carga automáticamente en `Cronos\App::variableGlobal()` que hace `require_once` de todos los archivos en `System/Helpers/`

## 11. Funcionalidades Implementadas

### Enrutamiento
- **Registro de rutas HTTP:** GET, POST, PUT, PATCH, DELETE
- **Route model binding:** Inyección automática de modelos en controladores
- **Rutas nombradas:** Asignación de nombres a rutas para generación de URLs
- **Grupos de rutas:** Prefijos y middlewares compartidos
- **Parámetros dinámicos:** `{id}`, `{blog:slug}` con restricciones
- **Auto-loading de rutas:** Carga automática de `routes/web.php` y `routes/api.php`
- **Prefijo automático para API:** Las rutas en `api.php` tienen prefijo `/api`
- **Archivos involucrados:** `System/Routing/Router.php`, `System/Routing/Route.php`, `routes/web.php`, `routes/api.php`, `App/Providers/RouteServiceProvider.php`

### Controladores
- **Clase base de controladores:** `Cronos\Http\Controller`
- **Inyección de dependencias:** Request, modelos y otros servicios
- **Middleware en controlador:** Asignación en constructor
- **Métodos auxiliares:** Validación integrada
- **Archivos involucrados:** `System/Http/Controller.php`, `App/Controllers/*`

### Modelos y ORM
- **CRUD básico:** create, update, delete, find, all
- **Query builder encadenado:** where, andWhere, orWhere, join, orderBy, limit
- **Funciones de agregación:** max, min, sum, avg
- **Relaciones:** hasOne, hasMany, belongsTo, belongsToMany
- **Timestamps automáticos:** created_at, updated_at
- **Campos fillable y hidden:** Protección de asignación masiva
- **Custom queries:** Ejecución de SQL personalizado
- **Route model binding básico:** Resolución de parámetros a modelos
- **Colecciones:** ModelCollection para colecciones de resultados
- **Archivos involucrados:** `System/Model/Model.php`, `System/Model/ModelCollection.php`, `App/Models/*`

### Sistema de Vistas
- **Motor de plantillas Blade-like:** Compilación de directivas a PHP
- **Layouts y herencia:** @extends, @section, @yield
- **Includes:** @include para reutilizar vistas
- **Control de flujo:** @if, @foreach, @for, @while, @switch
- **Variables seguras:** {{ $var }} con escape HTML
- **Cache de vistas:** Compilación en `storage/cache/`
- **Componentes con slots:** @component, @slot
- **Directivas personalizadas:** Registro de directivas custom
- **Archivos involucrados:** `System/View/View.php`, `System/View/CronosEngine.php`, `resources/views/*`

### Middleware
- **Sistema de middleware en cadena con Pipeline:** Ejecución secuencial usando patro de Pipeline
- **Middlewares globales:** Se ejecutan en TODAS las rutas (configurables en config/app.php)
- **Middleware de ruta:** Asignación individual con ->middleware()
- **Middleware de controlador:** Asignación en constructor con $this->middleware()
- **Middleware de grupo:** Asignación a grupos de rutas
- **MiddlewareGroup:** Clase para gestionar middlewares globales
- **Pipeline:** Clase para ejecutar middlewares en cadena
- **CorsMiddleware:** Manejo de encabezados CORS para APIs
- **ThrottleMiddleware:** Limitación de tasa de peticiones (Rate Limiting)
- **LogRequestMiddleware:** Registro de todas las peticiones HTTP
- **Middleware de autenticación:** AuthMiddleware (web) y AuthApiMiddleware (JWT)
- **Middleware de dashboard:** DahboardMiddleware
- **Middleware de validación de tokens:** TokenValidationMiddleware
- **Interfaz Middleware:** Contrato para implementar middlewares
- **Archivos involucrados:** `System/Http/Middleware.php`, `System/Http/Pipeline.php`, `System/Http/MiddlewareGroup.php`, `App/Middlewares/*`, `config/app.php`

### Sesiones
- **Sistema de sesiones nativo PHP:** PhpNativeSessionStorage
- **Datos flash:** Almacenamiento solo para la siguiente petición
- **Persistencia de usuario:** attempt(), user(), hasUser(), logout()
- **Errores de validación:** setErrorsInputs(), error(), old(), ifError()
- **Ruta anterior:** previousPath(), back()
- **Limpieza automática:** Flash data se elimina automáticamente
- **Archivos involucrados:** `System/Session/Session.php`, `System/Session/SessionStorage.php`, `System/Session/PhpNativeSessionStorage.php`

### Validación
- **Validación de datos:** Múltiples reglas predefinidas
- **Mensajes de error personalizables:** MessageError
- **Integración con sesiones:** Auto-guardado de errores y old input
- **Reglas soportadas:** required, email, url, alpha, alpha_dash, numeric, string, text, min, max, between, date, time, datetime, confirm, matches, slug, choice, unique, not_unique, password_verify, requiredFile, maxSize, type
- **Archivos involucrados:** `System/Validation/Validation.php`, `System/Validation/MessageError.php`

### Base de Datos
- **Abstracción PDO:** PdoDriver
- **Conexión configurada:** Desde .env y config/database.php
- **Prepared statements:** Protección contra SQL injection
- **Soporte UTF-8:** Configuración charset utf8mb4
- **Migraciones básicas:** Archivo único Database.php
- **Archivos involucrados:** `System/Database/DatabaseDriver.php`, `System/Database/PdoDriver.php`, `App/Migrations/Database.php`

### Autenticación
- **Autenticación web:** Sesión con session()->attempt()
- **Autenticación API:** JWT usando firebase/php-jwt
- **Middleware de autenticación:** AuthMiddleware y AuthApiMiddleware
- **Archivos involucrados:** `App/Middlewares/AuthMiddleware.php`, `App/Middlewares/AuthApiMiddleware.php`, `App/Library/JWT/JWTAuth.php`

### Respuestas HTTP
- **Respuestas JSON:** json($data, $statusCode)
- **Redirecciones:** redirect($url), back()
- **Respuestas de vistas:** view($name, $params)
- **Códigos de estado:** Soporte para todos los códigos HTTP
- **Archivos involucrados:** `System/Http/Response.php`, `System/Http/JsonResponse.php`

### Contenedor de Dependencias
- **Contenedor simple:** Singleton y resolve
- **Inyección de dependencias:** Resolución automática en controladores
- **Archivos involucrados:** `System/Container/Container.php`, `System/Container/DependencyInjection.php`

### Service Providers
- **Sistema de providers:** boot y runtime
- **Providers del framework:** Database, Session, View, Hasher
- **Providers de la aplicación:** RouteServiceProvider
- **Archivos involucrados:** `System/Provider/ServiceProvider.php`, `System/Provider/*`, `App/Providers/RouteServiceProvider.php`

### CLI (Artisan-like)
- **Generador de controladores:** make:controller
- **Generador de modelos:** make:model
- **Generador de middlewares:** make:middleware
- **Generador de migraciones:** make:migration
- **Ejecución de migraciones:** migrate
- **Archivos involucrados:** `cronos`, `System/ConsoleCLI/ConsoleCLI.php`, `System/ConsoleCLI/templates/*`

### Helpers Globales
- **app($class)** - Resuelve instancia del contenedor
- **configGet($key, $default)** - Obtiene valor de configuración
- **env($key, $default)** - Obtiene variable de entorno
- **resource_path($path)** - Obtiene la ruta a la carpeta resources
- **abort($code, $message)** - Lanza HttpException con código de estado HTTP
- **abort_if($condition, $code, $message)** - Lanza HttpException si la condición es verdadera
- **logger()** - Retorna clase Logger para acceso a métodos estáticos (error, warning, info)
- **json($data, $code)** - Retorna respuesta JSON
- **redirect($url)** - Redirecciona a URL
- **back()** - Redirecciona atrás
- **view($name, $params)** - Renderiza vista
- **route($name, $params)** - Genera URL desde nombre
- **session()** - Retorna instancia de sesión
- **Archivos involucrados:** `System/Helpers/*`

### Manejo de Errores
- **ExceptionHandler centralizado:** Captura Exception, Error y errores fatales de PHP con set_exception_handler, set_error_handler y register_shutdown_function
- **Detección automática de API requests:** Detecta si la petición es API (Accept: application/json o URI /api/*) y retorna JSON en ese caso
- **Modo debug vs producción:** En modo debug muestra stack trace detallado; en producción muestra mensajes genéricos sin exponer detalles
- **Logging automático de errores:** Registra TODOS los errores en el sistema de Log con contexto completo (URL, método HTTP, IP, user-agent)
- **Excepciones específicas:** HttpException (genérica), HttpNotFoundException (404), AuthorizationException (403), MethodNotAllowedException (405), ValidationException (422), RouteException
- **Vistas de error específicas:** 404.php, 403.php, 405.php, 500.php y error.php (genérica)
- **Archivos involucrados:** `System/Errors/ExceptionHandler.php`, `System/Errors/*`, `System/Exceptions/*`, `System/Log/Logger.php`, `resources/views/error/*`

### Logging
- **Sistema de logging completo:** Logger con métodos estáticos error(), warning() e info()
- **Rotación diaria de logs:** Archivos rotan automáticamente por día (formato: cronos-YYYY-MM-DD.log)
- **Formato de logs:** `[datetime] LEVEL: message | context_json`
- **Almacenamiento:** Logs guardados en `storage/logs/`
- **Archivos involucrados:** `System/Log/Logger.php`

### Criptografía
- **Hashing Bcrypt:** Implementación de password_hash
- **Hasher:** Servicio de hashing
- **Archivos involucrados:** `System/Crypto/Bcrypt.php`, `System/Crypto/Hasher.php`

## 12. Limitaciones Actuales vs. Laravel

| Funcionalidad | Laravel | Este proyecto |
|---------------|---------|---------------|
| Enrutamiento | ✅ Completo | ✅ Básico pero funcional (GET, POST, PUT, PATCH, DELETE, grupos, nombres) |
| Middleware | ✅ Completo | ✅ Implementado (en cadena, por ruta, por controlador, por grupo) |
| ORM (Eloquent) | ✅ Muy completo | ⚠️ Parcial (CRUD básico, query builder, relaciones, sin scopes, sin casting) |
| Migraciones | ✅ Sistema completo | ⚠️ Básico (solo archivo único Database.php manual) |
| Autenticación | ✅ Completa (Auth, Guards, Providers) | ⚠️ Básica (sesión y JWT sin Guards ni Providers) |
| Validación | ✅ Muy completa | ✅ Implementada con reglas principales |
| Vistas (Blade) | ✅ Muy completo | ✅ Básico pero funcional (directivas principales, layouts, componentes) |
| Sesiones | ✅ Múltiples drivers | ⚠️ Solo nativo PHP |
| Container | ✅ Completo | ⚠️ Simple (solo singleton y resolve) |
| Service Providers | ✅ Completo | ⚠️ Básico (solo boot y runtime) |
| Jobs y Colas | ✅ Completo | ❌ No implementado |
| Eventos | ✅ Completo | ❌ No implementado |
| Notificaciones | ✅ Completo | ❌ No implementado |
| Broadcasting | ✅ Completo | ❌ No implementado |
| Factories | ✅ Completo | ❌ No implementado |
| Seeders | ✅ Completo | ❌ No implementado |
| Soft Deletes | ✅ Implementado | ❌ No implementado |
| Scopes | ✅ Implementado | ❌ No implementado |
| Accessors/Mutators | ✅ Implementado | ❌ No implementado |
| Casting | ✅ Implementado | ❌ No implementado |
| Eager Loading (with) | ✅ Implementado | ❌ No implementado |
| API Resources | ✅ Implementado | ❌ No implementado |
| CSRF Protection | ✅ Implementado | ❌ No implementado |
| Rate Limiting | ✅ Implementado | ✅ Implementado (ThrottleMiddleware) |
| File Storage | ✅ Completo | ⚠️ Básico (solo archivos locales) |
| Cache | ✅ Múltiples drivers | ⚠️ Solo cache de vistas |
| Logging | ✅ Completo | ❌ No implementado |
| Testing | ✅ Muy completo | ⚠️ Básico (PHPUnit simple) |
| Artisan | ✅ Muy completo | ⚠️ Básico (solo generadores) |
| Eloquent Relationships | ✅ Muy completo | ⚠️ Básico (hasOne, hasMany, belongsTo, belongsToMany) |
| Query Builder | ✅ Muy completo | ⚠️ Parcial (básico sin subqueries, withoutGlobalScopes, etc.) |
| Collections | ✅ Muy completo | ⚠️ Básico (ModelCollection simple) |
| HTTP Client | ✅ Implementado | ❌ No implementado |
| Pagination | ✅ Implementado | ❌ No implementado |
| Localization | ✅ Implementado | ❌ No implementado |

## 13. Guía para Implementar Nuevas Funcionalidades

### Para agregar una nueva ruta y página:

1. **Crear el controlador:**
   ```bash
   php cronos make:controller PageController
   ```

2. **Agregar método al controlador** (`App/Controllers/PageController.php`):
   ```php
   public function index()
   {
       return view('page.index', [
           'pageTitle' => 'Nueva Página'
       ]);
   }
   ```

3. **Crear la vista** (`resources/views/page/index.php`):
   ```php
   @extends('home.layouts.head')

   @section('content')
   <div class="container">
       <h1>{{ $pageTitle }}</h1>
   </div>
   @endsection
   ```

4. **Registrar la ruta** en `routes/web.php`:
   ```php
   use App\Controllers\PageController;
   
   Route::get('/nueva-pagina', [PageController::class, 'index'])->name('page.index');
   ```

5. **Probar la ruta:**
   - Visitar: `http://cronos_framework.test/nueva-pagina`

### Para agregar un nuevo modelo con CRUD:

1. **Crear el modelo:**
   ```bash
   php cronos make:model Product
   ```

2. **Configurar el modelo** (`App/Models/Product.php`):
   ```php
   <?php
   
   namespace App\Models;
   
   use Cronos\Model\Model;
   
   class Product extends Model
   {
       protected string $table = 'products';
       
       protected string $primaryKey = 'id';
       
       protected array $fillable = [
           'name',
           'description',
           'price',
           'stock'
       ];
       
       protected bool $timestamps = true;
   }
   ```

3. **Crear la tabla en la BD** (editar `App/Migrations/Database.php`):
   ```php
   $this->pdo->exec("
       CREATE TABLE IF NOT EXISTS products (
           id INT AUTO_INCREMENT PRIMARY KEY,
           name VARCHAR(255) NOT NULL,
           description TEXT,
           price DECIMAL(10, 2) NOT NULL,
           stock INT DEFAULT 0,
           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
           updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ");
   ```

4. **Ejecutar migración:**
   ```bash
   php cronos migrate
   ```

5. **Crear controlador:**
   ```bash
   php cronos make:controller ProductController
   ```

6. **Implementar métodos CRUD** (`App/Controllers/ProductController.php`):
   ```php
   <?php
   
   namespace App\Controllers;
   
   use App\Models\Product;
   use Cronos\Http\Controller;
   use Cronos\Http\Request;
   use App\Middlewares\AuthMiddleware;
   
   class ProductController extends Controller
   {
       public function __construct()
       {
           $this->middleware(AuthMiddleware::class);
       }
       
       public function index()
       {
           $products = Product::all();
           return view('products.index', ['products' => $products]);
       }
       
       public function create()
       {
           return view('products.create');
       }
       
       public function store(Request $request)
       {
           $valid = $this->validate($request->all(), [
               'name' => 'required|string|min:3|max:255',
               'description' => 'string|max:1000',
               'price' => 'required|numeric|min:0',
               'stock' => 'required|integer|min:0'
           ]);
           
           if ($valid !== true) {
               return json(['status' => 'error', 'message' => $valid]);
           }
           
           $product = Product::create($request->all());
           
           return json([
               'status' => 'success',
               'message' => 'Producto creado',
               'product' => $product
           ]);
       }
       
       public function show(Product $product)
       {
           return view('products.show', ['product' => $product]);
       }
       
       public function edit(Product $product)
       {
           return json($product);
       }
       
       public function update(Request $request, Product $product)
       {
           $valid = $this->validate($request->all(), [
               'name' => 'required|string|min:3|max:255',
               'description' => 'string|max:1000',
               'price' => 'required|numeric|min:0',
               'stock' => 'required|integer|min:0'
           ]);
           
           if ($valid !== true) {
               return json(['status' => 'error', 'message' => $valid]);
           }
           
           $product = Product::update($product->id, $request->all());
           
           return json([
               'status' => 'success',
               'message' => 'Producto actualizado',
               'product' => $product
           ]);
       }
       
       public function destroy(Product $product)
       {
           Product::delete($product->id);
           
           return json([
               'status' => 'success',
               'message' => 'Producto eliminado'
           ]);
       }
   }
   ```

7. **Crear vistas**:
   - `resources/views/products/index.php` - Listado
   - `resources/views/products/create.php` - Formulario de creación
   - `resources/views/products/show.php` - Detalle

8. **Registrar rutas** en `routes/web.php`:
   ```php
   use App\Controllers\ProductController;
   
   Route::group(['prefix' => '/products', 'middleware' => [AuthMiddleware::class]], function() {
       Route::get('/', [ProductController::class, 'index'])->name('products.index');
       Route::get('/create', [ProductController::class, 'create'])->name('products.create');
       Route::post('/', [ProductController::class, 'store'])->name('products.store');
       Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
       Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
       Route::put('/{product}', [ProductController::class, 'update'])->name('products.update');
       Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
   });
   ```

### Para agregar un middleware o filtro:

1. **Crear el middleware:**
   ```bash
   php cronos make:middleware RoleMiddleware
   ```

2. **Implementar el middleware** (`App/Middlewares/RoleMiddleware.php`):
   ```php
   <?php
   
   namespace App\Middlewares;
   
   use Closure;
   use Cronos\Http\Request;
   use Cronos\Http\Response;
   use Cronos\Http\Middleware;
   
   class RoleMiddleware implements Middleware
   {
       public function handle(Request $request, Closure $next): Response
       {
           // Verificar si el usuario está autenticado
           if (!session()->hasUser()) {
               return redirect()->route('login.index');
           }
           
           // Verificar si tiene el rol adecuado
           $user = session()->user();
           
           if ($user->role !== 'admin') {
               return json([
                   'status' => 'error',
                   'message' => 'Acceso no autorizado'
               ], 403);
           }
           
           return $next($request);
       }
   }
   ```

3. **Usar el middleware en una ruta:**
   ```php
   Route::get('/admin', [AdminController::class, 'index'])
       ->middleware(RoleMiddleware::class);
   ```

4. **Usar el middleware en un controlador:**
   ```php
   class AdminController extends Controller
   {
       public function __construct()
       {
           $this->middleware(RoleMiddleware::class);
       }
       
       // Métodos...
   }
   ```

5. **Usar el middleware en un grupo de rutas:**
   ```php
   Route::group(['prefix' => '/admin', 'middleware' => [RoleMiddleware::class]], function() {
       Route::get('/', [AdminController::class, 'index']);
       Route::get('/users', [AdminUserController::class, 'index']);
   });
   ```

## 14. Notas Importantes para IAs

### Advertencias sobre patrones específicos del proyecto

1. **Route model binding limitado:** El sistema de route model binding solo funciona si el parámetro se llama igual que el tipo del modelo. Ejemplo: `{blog}` requiere `function show(Blog $blog)`.

2. **Relaciones sin eager loading:** Las relaciones se cargan lazy. No hay `with()` para eager loading, así que ten cuidado con el problema N+1 queries.

3. **Solo un archivo de migración:** A diferencia de Laravel, este framework usa un solo archivo `App/Migrations/Database.php` para todas las migraciones. No generas archivos de migración individuales.

4. **No hay Service Providers avanzados:** Solo hay `boot` y `runtime`. No hay `register`, `booted`, etc. Los providers deben implementar el método `registerServices()`.

5. **Static properties en Model:** Las propiedades estáticas de Model (`$wheres`, `$values`, etc.) se resetean automáticamente después de cada `get()` o `first()`, pero ten cuidado si construyes consultas parciales.

6. **No hay Facades:** No usa facades estilo Laravel. En su lugar usa funciones helper (`view()`, `session()`, `route()`) o inyección de dependencias.

### Errores comunes a evitar

1. **No olvidar `$fillable`:** Si defines un modelo sin `$fillable`, el sistema lanzará un error. Debes listar todos los campos que se pueden asignar masivamente.

2. **No olvidar `$table` y `$primaryKey`:** Todos los modelos deben definir `$table` y `$primaryKey`, de lo contrario lanzará error.

3. **Rutas sin nombre:** Las rutas sin nombre no pueden ser referenciadas con `route()`. Siempre agrega `->name('ruta.nombre')` si necesitas generar URLs.

4. **Parámetros de ruta incorrectos:** Si una ruta espera `{id}` pero el controlador recibe un tipo diferente, fallará. Usa `{modelo:campo}` para especificar el campo.

5. **Views sin extensión:** No agregues `.php` al llamar a `view()`. Usa `view('home.index')`, no `view('home.index.php')`.

6. **Middleware en cadena incorrecta:** Si un middleware no llama a `$next($request)`, la cadena se rompe y no se ejecutarán los siguientes middlewares ni el controlador.

7. **Uso correcto del Pipeline:** El Pipeline usa el patrón "onion" (cebolla) donde los middlewares se ejecutan en orden pero las responses viajan en orden inverso. Cuando uses `$next($request)` en un middleware, estás pasando el control al siguiente middleware. Si no lo llamas, detienes la cadena. Nunca instancies Pipeline manualmente en tu código; el Router lo hace automáticamente.

8. **No limpiar propiedades estáticas después de queries fallidas:** Si construyes una query y hay un error antes de llamar a `get()` o `first()`, las propiedades estáticas no se resetean.

### Decisiones de diseño no obvias que hay que respetar

1. **Prefijo `/api` automático:** Las rutas en `routes/api.php` tienen prefijo `/api` automáticamente. No agregues `/api` manualmente.

2. **Session con múltiples claves internas:** La sesión usa claves internas como `_flash`, `_cronos_previous_path`, `_errors_inputs`. No las sobreescribas.

3. **Helpers globales cargados automáticamente:** Los archivos en `System/Helpers/` se cargan automáticamente. No agregues funciones globales fuera de esa carpeta.

4. **Cache de vistas:** Las vistas se cachean en `storage/cache/`. Si cambias una vista y no ves cambios, borra el cache manualmente.

5. **No hay namespaces de rutas:** A diferencia de Laravel, no puedes agrupar rutas por nombre. Usa prefijos en su lugar.

6. **Contenedor simple:** El contenedor de dependencias no soporta bindings complejos, aliases, o context bindings. Solo `singleton` y `resolve`.

7. **Validation retorna true o array:** El método `validate()` retorna `true` si pasa, o un array de errores si falla. No lanza excepciones.

8. **Modelos usan `__get` y `__set`:** Los atributos del modelo se acceden como propiedades mágicas. No hay getters/setters tradicionales.

9. **No hay eventos del modelo:** Los métodos `create()`, `update()`, `delete()` no disparan eventos. Si necesitas lógica adicional, agrégala antes/after de estos métodos.

10. **Timestamps automáticos solo si `$timestamps = true`:** Si no habilitas `$timestamps` en el modelo, no se crearán automáticamente.

### Cualquier "trampa" o comportamiento especial del framework

1. **Función `dd()` disponible globalmente:** `dd($data)` hace var_dump y die. Útil para debug.

2. **`back()` usa sesión:** La función `back()` guarda la URL anterior en sesión automáticamente en GET requests.

3. **Validation guarda errores en sesión:** Si falla la validación, los errores y old input se guardan automáticamente en sesión para el siguiente request.

4. **Route parameters con restricciones:** Puedes usar `{parametro:tipo}` donde tipo puede ser `slug`, `id`, etc. Esto se mapea al nombre del campo en el modelo.

5. **No hay CSRF tokens:** Los formularios POST no requieren tokens CSRF. El framework no implementa protección CSRF.

6. **Session start automático:** La sesión se inicia automáticamente en `Cronos\App::setSessionHandler()`. No necesitas llamar `session_start()`.

7. **Flash data age automático:** Los datos flash se "envejecen" automáticamente entre peticiones: new → old → eliminado.

8. **No hay route caching:** Las rutas se cargan en cada request. No hay sistema de cache de rutas.

9. **PDO con UTF-8:** La conexión PDO se configura automáticamente con charset utf8mb4.

10. **Middleware execution order:** Los middlewares de ruta se ejecutan ANTES de los middlewares del controlador.

11. **Route groups stack:** Los grupos de rutas usan un stack estático interno (`Route::$groupStack`). No lo manipules directamente.

12. **No hay View Composers:** Si necesitas datos comunes en múltiples vistas, pásalos desde el controlador o crea un helper.

13. **JSON response con headers automáticos:** La función `json()` automáticamente agrega el header `Content-Type: application/json`.

14. **No hay pagination helper:** Si necesitas paginación, implementa manualmente con `limit()` y `offset()`. No hay `paginate()`.

15. **CLI solo funciona con php-cli:** El archivo `cronos` verifica que se ejecute con PHP CLI, no con php-cgi.