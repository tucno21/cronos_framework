# Configuracion y Variables de Entorno

## Archivo .env

Las variables de entorno se definen en el archivo `.env` en la raiz del proyecto. Se cargan automaticamente con `vlucas/phpdotenv`.

### Variables Disponibles

| Variable | Default | Descripcion |
|---|---|---|
| `APP_NAME` | Cronos | Nombre de la aplicacion |
| `APP_URL` | http://cronos_framework.test | URL base de la aplicacion |
| `APP_FORMAT` | web | Formato: 'web' o 'api' |
| `TIME_ZONE` | GMT | Zona horaria |
| `DB_CONNECTION` | mysql | Protocolo de conexion a BD |
| `DB_HOST` | localhost | Host de base de datos |
| `DB_PORT` | 3306 | Puerto de base de datos |
| `DB_DATABASE` | cronos | Nombre de la base de datos |
| `DB_USERNAME` | root | Usuario de base de datos |
| `DB_PASSWORD` | (vacio) | Contrasena de base de datos |
| `JWT_SECRET_KEY` | secret | Clave secreta para tokens JWT |
| `SESSION_STORAGE` | native | Driver de sesion |
| `PATH_FILE_STORAGE` | - | Carpeta para archivos subidos |

### Ejemplo de .env

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
PATH_FILE_STORAGE=imagenes
```

### Acceder a Variables de Entorno

```php
// Desde codigo PHP
$valor = env('NOMBRE_VARIABLE', 'valor_por_defecto');

// Desde archivos de configuracion
'nombre' => env('APP_NAME', 'Cronos')
```

## Archivos de Configuracion

Todos los archivos estan en `config/`.

### config/app.php

```php
return [
    'name' => env('APP_NAME', 'Cronos'),
    'url' => env('APP_URL', 'http://cronos_framework.test'),
    'app_format' => env('APP_FORMAT', 'web'),
    'global_middlewares' => [
        // Middlewares globales que se ejecutan en TODAS las rutas
    ],
];
```

### config/database.php

```php
return [
    'connection' => env('DB_CONNECTION', 'mysql'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'cronos'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
];
```

### config/session.php

```php
return [
    'storage' => env('SESSION_STORAGE', 'native'),
];
```

Solo soporta el driver `'native'` que usa sesiones nativas de PHP.

### config/view.php

```php
return [
    'engine' => 'cronos',
    'path' => resourcesDirectory() . '/views',
    'cache' => cacheDirectory(),
];
```

- `engine`: Solo soporta `'blade'` (motor BladeEngine)
- `path`: Directorio de vistas (`resources/views/`)
- `cache`: Directorio base de cache; las vistas compiladas van en `storage/cache/views/`

### config/hashing.php

```php
return [
    'hasher' => 'bcrypt'
];
```

Solo soporta `'bcrypt'`. Se usa para hashear contrasenas:

```php
$hasher = app(Cronos\Crypto\Hasher::class);
$hashed = $hasher->make('password123');
```

### config/cors.php

```php
return [
    'allowed_origins' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Authorization'],
    'supports_credentials' => false,
    'allowed_origins_patterns' => ['http://127.0.0.1:8090'],
    // 'max_age' => 0,
];
```

| Clave | Descripcion |
|---|---|
| `allowed_origins` | Dominios permitidos. `['*']` = todos |
| `allowed_methods` | Metodos HTTP permitidos. `['*']` = todos |
| `allowed_headers` | Headers permitidos del cliente |
| `exposed_headers` | Headers accesibles desde JavaScript |
| `supports_credentials` | Permitir cookies y credenciales |
| `allowed_origins_patterns` | Dominios para credenciales |
| `max_age` | Segundos de cache preflight |

### config/providers.php

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

- **boot**: Se ejecutan al inicio (BD, sesion, vistas, hashing)
- **runtime**: Se ejecutan despues (rutas)

---

> **Anterior**: [09 - Helpers](09-helpers.md)
> **Siguiente**: [11 - Guia Nuevo Modulo](11-guia-nuevo-modulo.md)
