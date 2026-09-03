# Instalacion y Estructura del Proyecto

## Requisitos

- PHP >= 8.2
- Composer

## Instalacion

1. Clonar el repositorio
2. Ejecutar el comando `composer install`
3. Crear un archivo `.env` en la raiz del proyecto
4. Configurar el archivo `.env` con los datos de la base de datos

## Estructura de Carpetas

```
📁 cronos_framework/
├── 📁 App/
│   ├── 📁 Controllers/       # Controladores de la aplicacion
│   ├── 📁 Middlewares/       # Middlewares personalizados
│   ├── 📁 Models/            # Modelos de la aplicacion
│   ├── 📁 Migrations/        # Migraciones timestamped (una por archivo)
│   ├── 📁 Seeders/           # Seeders de datos de prueba
│   ├── 📁 Providers/         # Service Providers
│   ├── 📁 Help/              # Clases auxiliares (imagenes, archivos, enlaces)
│   └── 📁 Library/           # Librerias personalizadas (JWT)
├── 📁 config/
│   ├── 📄 app.php            # Configuracion general de la app
│   ├── 📄 cors.php           # Configuracion CORS
│   ├── 📄 database.php       # Configuracion de base de datos
│   ├── 📄 hashing.php        # Configuracion de hashing
│   ├── 📄 providers.php      # Service Providers a cargar
│   ├── 📄 session.php        # Configuracion de sesiones
│   └── 📄 view.php           # Configuracion de vistas
├── 📁 public/
│   ├── 📄 index.php          # Punto de entrada de la aplicacion
│   ├── 📄 .htaccess          # Configuracion Apache
│   └── 📁 assets/            # Archivos estaticos (CSS, JS, imagenes)
├── 📁 resources/
│   ├── 📁 css/               # Archivos CSS fuente (TailwindCSS v4)
│   └── 📁 views/             # Vistas de la aplicacion
│       ├── 📁 components/    # Componentes reutilizables <x-nombre>
│       ├── 📁 home/          # Vistas publicas
│       ├── 📁 dashboard/     # Vistas del dashboard
│       └── 📁 error/         # Vistas de error
├── 📁 routes/
│   ├── 📄 web.php            # Rutas web (paginas)
│   └── 📄 api.php            # Rutas API (JSON)
├── 📁 System/                # Nucleo del framework
│   ├── 📄 App.php            # Clase principal
│   ├── 📁 Config/            # Sistema de configuracion
│   ├── 📁 ConsoleCLI/        # CLI (comandos artisan-like)
│   ├── 📁 Container/         # Contenedor de dependencias
│   ├── 📁 Crypto/            # Criptografia y hashing
│   ├── 📁 Database/          # Capa de base de datos (PDO)
│   ├── 📁 Errors/            # Manejo de errores
│   ├── 📁 Exceptions/        # Excepciones personalizadas
│   ├── 📁 Helpers/           # Funciones helper globales
│   ├── 📁 Http/              # Capa HTTP (Request, Response, Middleware, Pipeline)
│   ├── 📁 Model/             # ORM simplificado
│   ├── 📁 Provider/          # Service Providers del framework
│   ├── 📁 Routing/           # Sistema de enrutamiento
│   ├── 📁 Session/           # Sistema de sesiones
│   ├── 📁 Storage/           # Almacenamiento
│   ├── 📁 Validation/        # Sistema de validacion
│   └── 📁 View/              # Motor de plantillas (CronosEngine)
├── 📁 storage/
│   ├── 📁 cache/             # Cache de vistas compiladas
│   └── 📁 logs/              # Logs de aplicacion
├── 📁 tests/                 # Tests unitarios e integracion
├── 📄 .env                   # Variables de entorno (no versionado)
├── 📄 .gitignore
├── 📄 composer.json
├── 📄 cronos                 # Ejecutable CLI
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
8. **Vista**: CronosEngine compila directivas Blade-like a PHP, se cachea en `storage/cache/`
9. **Response**: se envian headers y contenido (JSON, HTML o redireccion)
10. **Finalizacion**: se cierra la sesion, se limpia flash data

---

> **Siguiente**: [01 - Rutas](01-rutas.md)
