# Rutas

Las rutas se definen en `routes/web.php` (paginas web) y `routes/api.php` (API endpoints).

> **Nota**: Las rutas en `api.php` tienen prefijo `/api` automaticamente. No es necesario agregar `/api` manualmente.

## Sintaxis Basica

```php
use Cronos\Routing\Route;

// Ruta con controlador
Route::get('/', [Controller::class, 'index'])->name('home');

// Ruta GET con nombre (los nombres solo en GET)
Route::get('/login', [Controller::class, 'login'])->name('login');
Route::post('/login', [Controller::class, 'login']);

// Tambien se puede usar closure
Route::get('/', function() {
    return view('home.index');
});
```

## Metodos HTTP Soportados

```php
Route::get($uri, $action);
Route::post($uri, $action);
Route::put($uri, $action);
Route::patch($uri, $action);
Route::delete($uri, $action);
```

## Parametros Dinamicos

```php
// Parametro simple
Route::get('/user/{id}', [Controller::class, 'user'])->name('user');
// El metodo del controlador recibe el parametro
public function user(string $id);

// Route Model Binding: {user} debe ser el mismo nombre del modelo (busca por id)
Route::get('/user/{user}', [Controller::class, 'user'])->name('user');
public function user(User $user);

// Route Model Binding con columna especifica: {user:slug}
Route::get('/user/{user:colum}', [Controller::class, 'user'])->name('user');
public function user(User $user);

// Multiples parametros
Route::get('/user/{user:colum}/{id}/producto/{product}', [Controller::class, 'user'])->name('user');
public function user(User $user, string $id, Product $product);
```

## Rutas con Middleware

```php
Route::get('/user/{id}', [Controller::class, 'user'])
    ->name('user')
    ->middleware([LoginMiddleware::class]);
```

## Grupos de Rutas

```php
// Grupo con prefijo
Route::group(['prefix' => '/dashboard'], function () {
    Route::get('/users', [ApiController::class, 'grupos']);
    Route::post('/users', [ApiController::class, 'gruposStore']);
});

// Grupo con prefijo y middleware
Route::group(['prefix' => '/panel-control', 'middleware' => [AuthApiMiddleware::class]], function () {
    Route::get('/users', [ApiController::class, 'grupos']);
    Route::post('/users', [ApiController::class, 'gruposStore']);
});
```

## Generar URLs desde el Nombre de Ruta

```php
// En controlador o vista
$url = route('dashboard.index');
// Genera: http://cronos_framework.test/dashboard

// Con parametros
$url = route('dashboard.show', 'mi-blog');
// Genera: http://cronos_framework.test/dashboard/mi-blog

// Con parametros como array
$url = route('dashboard.show', ['id' => 1]);
```

---

> **Anterior**: [00 - Instalacion](00-instalacion.md)
> **Siguiente**: [02 - Consola](02-consola.md)
