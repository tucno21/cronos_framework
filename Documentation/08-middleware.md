# Middleware

El sistema de middleware permite filtrar peticiones HTTP antes y despues de que lleguen al controlador. Se ejecutan en cadena usando el patron "onion" (cebolla) mediante la clase `Pipeline`.

## Tipos de Middleware

### 1. Middleware Global

Se ejecutan en TODAS las rutas. Se configuran en `config/app.php`:

```php
'global_middlewares' => [
    \App\Middlewares\CorsMiddleware::class,
    \App\Middlewares\LogRequestMiddleware::class,
],
```

### 2. Middleware en Ruta

Se asigna directamente en la definicion de la ruta:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(AuthMiddleware::class);

// O multiples middlewares
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware([AuthMiddleware::class, RoleMiddleware::class]);
```

### 3. Middleware en Controlador

Se asigna en el constructor del controlador:

```php
public function __construct()
{
    $this->middleware(AuthMiddleware::class);
}
```

> **Importante**: No usar middleware en el controlador y en la ruta simultaneamente. Usar solo uno.

### 4. Middleware en Grupo de Rutas

```php
Route::group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('/users', [AdminUserController::class, 'index']);
});
```

## Orden de Ejecucion

Los middlewares se ejecutan en este orden: **globales → ruta → controlador**.

El patron "onion" significa:
- El Request viaja: primer middleware → ultimo middleware → controlador
- La Response viaja: controlador → ultimo middleware → primer middleware

Si un middleware NO llama a `$next($request)`, la cadena se rompe y se retorna la Response inmediatamente.

## Crear un Middleware

### Desde consola:

```bash
php cronos make:middleware NameMiddleware
```

### Estructura:

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
        if (!session()->hasUser()) {
            return redirect()->route('login.index');
        }

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

## Middlewares Incluidos en el Framework

### AuthMiddleware

Middleware de autenticacion web. Verifica si el usuario tiene sesion activa. Si no esta autenticado, redirecciona al login.

### AuthApiMiddleware

Middleware de autenticacion API usando JWT. Verifica el token Bearer en los headers de la peticion.

### CorsMiddleware

Manejo de headers CORS para APIs. Permite configurar origenes, metodos y headers permitidos (ver `config/cors.php`).

### ThrottleMiddleware

Limitacion de tasa de peticiones (Rate Limiting) para prevenir ataques de fuerza bruta.

**Parametros del constructor:**

| Parametro | Tipo | Default | Descripcion |
|---|---|---|---|
| `$maxRequests` | int | 60 | Maximo de peticiones en la ventana de tiempo |
| `$decayMinutes` | int | 1 | Ventana de tiempo en minutos |
| `$onLimit` | string | 'block' | Accion al exceder: 'block' o 'log_only' |

**Ejemplos de uso:**

```php
// Valores por defecto: 60 peticiones por minuto
Route::get('/api/data', [ApiController::class, 'index'])
    ->middleware(ThrottleMiddleware::class);

// Configuracion personalizada: 100 peticiones por 5 minutos
Route::get('/api/search', [ApiController::class, 'search'])
    ->middleware(new ThrottleMiddleware(100, 5));

// Solo logging sin bloquear
Route::get('/api/track', [ApiController::class, 'track'])
    ->middleware(new ThrottleMiddleware(1000, 60, 'log_only'));

// Login con limite estricto: 5 intentos por minuto
Route::get('/api/login', [ApiController::class, 'login'])
    ->middleware(new ThrottleMiddleware(5, 1));
```

**Headers de respuesta:**
- `X-RateLimit-Limit`: Maximo de peticiones permitidas
- `X-RateLimit-Remaining`: Peticiones restantes
- `X-RateLimit-Reset`: Timestamp de reseteo

Al exceder el limite (con `$onLimit === 'block'`): retorna HTTP 429 con header `Retry-After`.

### LogRequestMiddleware

Registro de todas las peticiones HTTP para debugging y analisis.

**Parametros del constructor:**

| Parametro | Tipo | Default | Descripcion |
|---|---|---|---|
| `$logLevel` | string | 'basic' | Nivel de detalle: 'full', 'basic', 'minimal' |
| `$logTo` | string | 'error_log' | Destino: 'error_log', 'file', 'database' |
| `$logFile` | string | 'storage/logs/request.log' | Ruta del archivo de log |
| `$includeRequestBody` | bool | false | Incluir body de la peticion |
| `$includeResponseTime` | bool | true | Incluir tiempo de ejecucion |

**Formatos de log:**

Nivel `minimal`:
```
[2026-03-13 14:30:45] GET /api/users
```

Nivel `basic`:
```
[2026-03-13 14:30:45] POST /api/login | IP: 192.168.1.100 | Status: 200 | Time: 0.045s
```

Nivel `full` (JSON formateado):
```json
{
    "timestamp": "2026-03-13 14:30:45",
    "method": "POST",
    "uri": "/api/login",
    "ip": "192.168.1.100",
    "status_code": 200,
    "execution_time": "45.23ms",
    "user_agent": "Mozilla/5.0 ...",
    "referer": "https://example.com/login"
}
```

**Los logs se escriben en** `storage/logs/request.log`. No hay rotacion automatica.

---

> **Anterior**: [07 - Sesiones](07-sesiones.md)
> **Siguiente**: [09 - Helpers](09-helpers.md)
