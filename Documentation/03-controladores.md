# Controladores

Todos los controladores se ubican en `App/Controllers/` y heredan de `Cronos\Http\Controller`.

## Convencion de Nombres

- Nombre en **PascalCase** con sufijo `Controller`
- Archivo: `App/Controllers/UserController.php`
- Clase: `class UserController extends Controller`
- Namespace: `namespace App\Controllers;`

## Estructura de un Controlador

```php
<?php

namespace App\Controllers;

use Cronos\Http\Controller;
use Cronos\Http\Request;
use App\Models\Blog;
use App\Middlewares\AuthMiddleware;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(AuthMiddleware::class);
    }

    public function index()
    {
        return view('dashboard.index', ['pageTitle' => 'Dashboard']);
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string'
        ]);

        if ($valid !== true) {
            return json(['status' => 'error', 'message' => $valid]);
        }

        $blog = Blog::create($request->all());
        return json(['status' => 'success', 'blog' => $blog]);
    }

    public function show(Blog $blog)
    {
        return view('dashboard.show', ['blog' => $blog]);
    }
}
```

## HTTP Request y Form Requests

Los metodos del controlador pueden recibir `Request` o clases especializadas `FormRequest` automaticamente mediante inyeccion de dependencias.

> Para validaciones desacopladas y autorizacion previa automatica, use **Form Requests** (consulte la guia detallada en [`Documentation/15-form-requests.md`](15-form-requests.md)).

```php
public function user(Request $request);

// O usando un FormRequest dedicado:
public function store(CreateUserRequest $request);

// Obtener todos los datos del request
$request->all();

// Acceder a un campo como propiedad
$request->name;

// Obtener un dato especifico
$request->input('name');

// Consultar si existe un dato
$request->has('name');

// Obtener todos los datos excepto los indicados
$request->except(['name', 'email']);

// Obtener solo los datos indicados
$request->only(['name', 'email']);

// Obtener archivo del request
$request->file('name');

// Consultar si existe un archivo
$request->hasFile('name');

// Consultar la IP
$request->ip();

// Obtener el metodo HTTP
$request->method();

// Obtener headers
$request->headers();
$request->headers('x-token');

// Obtener cookies
$request->cookies();
$request->cookies('aaa');

// Consultar si la conexion es segura
$request->isSecure();

// Obtener el User-Agent
$request->userAgent();

// Consultar si es AJAX
$request->ajax();

// Obtener el token Bearer
$request->bearerToken();

// Guardar archivo en el storage
// Parametros: archivo, nombre (opcional), carpeta (opcional)
$request->store($request->file('name'), string $nameFile = null, string $nameFolder = null)
```

## HTTP Response

```php
// Renderizar una vista
// Parametros: nombre de vista, datos (opcional), codigo de estado (opcional)
return view('name', ['data' => $data], $status = 200);

// Enviar JSON
return json($data, $status = 200);

// Redireccionar a una URL
return redirect('/login');

// Redireccionar usando nombre de ruta
return redirect()->route('login');

// Redireccionar con parametros
return redirect()->route('login', ['data' => $data]);

// Redireccionar con mensaje flash
return redirect()->route('login')->with('message', 'mensaje de session flash');

// Retornar a la ruta anterior
return back();

// Retornar atras con mensaje flash
return back()->with('message', 'mensaje de session flash');

// Retornar atras con errores de validacion
// Parametros: datos del request, errores del validator, codigo de estado (opcional)
return back()->withErrors($dataInput, $errors, $status = 200);
```

## Middleware en el Controlador

No usar el middleware en el controlador y en la ruta simultaneamente. Usar solo uno de los dos.

```php
public function __construct()
{
    $this->middleware(AuthMiddleware::class);
}
```

## Encriptar Password

```php
public function create(Request $request, Hasher $hasher)
{
    $data = $request->all();
    $data->password = $hasher->hash($data->password);
}

// Para verificar
$hasher->verify($inputPassword, $request->password);
```

## Metodos Auxiliares Disponibles

Desde `Cronos\Http\Controller`:
- `validate(array|object $inputs, array $rules)` — Valida datos (ver [05-validaciones.md](05-validaciones.md))

Desde helpers globales:
- `view()` — Renderiza vista
- `json()` — Retorna JSON
- `redirect()` — Redirecciona
- `back()` — Redirecciona atras
- `route()` — Genera URL desde nombre

---

> **Anterior**: [02 - Consola](02-consola.md)
> **Siguiente**: [04 - Modelos](04-modelos.md)
