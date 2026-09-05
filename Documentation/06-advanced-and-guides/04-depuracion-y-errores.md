# Depuración y Manejo Visual de Errores

Cronos Framework cuenta con un subsistema nativo de depuración y gestión de excepciones de alto nivel (inspirado en la ergonomía de herramientas como Laravel VarDumper e Ignition), implementado en memoria y sin dependencias externas pesadas.

---

## 1. Helpers Globales de Depuración

Para inspeccionar el estado de variables, objetos y colecciones en cualquier punto del ciclo de vida de la aplicación (controladores, modelos, vistas, middleware o scripts CLI), dispones de tres funciones globales:

### `dump(...$vars): void`
Inspecciona una o múltiples variables y **permite que la ejecución del código continúe**.

```php
// En un controlador o servicio:
$user = User::first();
$roles = $user->roles();

dump($user, $roles, 'Verificación intermedia');

// El script sigue ejecutándose normalmente...
return view('users.show', compact('user'));
```

### `d(...$vars): void`
Es un **alias corto y ergonómico** idéntico a `dump()`. Ideal para depuraciones rápidas durante el desarrollo diario.

```php
d($request->all(), $filters);
```

### `dd(...$vars): never`
Significa **"Dump and Die"**. Inspecciona una o múltiples variables y **detiene la ejecución inmediatamente** (`exit(1)`).
- En entorno web envía automáticamente una cabecera HTTP `500`.
- En CLI sale con código de error de proceso.

```php
public function store(UserRequest $request)
{
    $validated = $request->validated();
    
    // Detiene todo aquí para inspeccionar datos validados antes de persistir:
    dd($validated, $request->headers());

    User::create($validated);
}
```

---

## 2. Adaptación Inteligente al Entorno (Context-Aware)

El orquestador [`Cronos\Debug\Dumper`](../../System/Debug/Dumper.php) detecta automáticamente el contexto de la llamada y ajusta la salida:

### A. En Terminal y Consola CLI (`CliDumper`)
- Utiliza **secuencias de colores ANSI** nativas para distinguir tipos de datos a primera vista:
  - Strings en verde: `"texto"`
  - Números en azul brillante: `42`, `3.14`
  - Booleans y Null en gris / magenta: `true`, `false`, `null`
  - Arrays y Colecciones con estructura identada y conteo de elementos.
- **Trazabilidad automática**: Imprime la ubicación exacta de la llamada:
  ```text
  📍 App/Controllers/UserController.php:28
  ```

### B. En Navegador Web (`HtmlDumper`)
- Renderiza un panel interactivo con **Tema Oscuro** elegante.
- **Nodos expandibles y colapsables**: Arrays, objetos y modelos se agrupan en elementos `<details>` para evitar saturar la pantalla con objetos complejos.
- **Acciones globales**: Botones para `[+ Expandir todo]` y `[- Colapsar todo]`.
- **Inspección especializada de Modelos ORM**:
  - Reconoce instancias de [`Cronos\Database\Model`](../../System/Database/Model.php) y colecciones [`ModelCollection`](../../System/Database/ModelCollection.php).
  - Muestra ordenadamente `#attributes`, `#relations` y `#original`.
  - **Oculta de forma inteligente la conexión PDO** y buffers internos para prevenir fugas de memoria o volcados interminables.

### C. En Peticiones AJAX y APIs JSON
Si la petición entrante especifica `Accept: application/json` o la cabecera `X-Requested-With: XMLHttpRequest`, los helpers de depuración no inyectan HTML que corrompa clientes HTTP (como Axios, Fetch o Postman).
En su lugar, devuelven un JSON estructurado con la bandera `__cronos_debug`:

```json
{
  "__cronos_debug": true,
  "caller": "App/Controllers/ApiController.php:45",
  "dumps": [
    {
      "__class": "App\\Models\\User",
      "data": {
        "id": 1,
        "name": "Carlos",
        "email": "carlos@example.com"
      }
    }
  ]
}
```

---

## 3. Pantalla Visual Interactiva de Excepciones (`ErrorRenderer`)

Cuando ocurre cualquier excepción no capturada en la aplicación ([`Throwable`](https://www.php.net/manual/es/class.throwable.php)), el manejador [`Cronos\Errors\ExceptionHandler`](../../System/Errors/ExceptionHandler.php) interviene según la configuración del entorno.

### A. Modo Desarrollo (`CRONOS_APP_DEBUG=true`)

Si ocurre un error en el navegador web, Cronos despliega una interfaz completa de resolución de errores:

1. **Barra de Estado y Cabecera**:
   - Tipo de excepción (`RuntimeException`, `QueryException`, `ValidationException`, etc.).
   - Mensaje descriptivo del error.
   - Ruta relativa del archivo causante y número de línea exacta.
   - Versión activa de Cronos Framework y PHP.

2. **Editor con Snippet de Código Fuente**:
   - Lee el archivo en el punto exacto donde ocurrió el fallo.
   - Muestra las líneas de código previas y posteriores con numeración.
   - Resalta de forma luminosa (`active`) la línea exacta que disparó la excepción.

3. **Pila de Ejecución (Stack Trace)**:
   - Lista interactiva de todos los frames en la cadena de llamadas.
   - Destaca con la etiqueta verde `APP` aquellos pasos que pertenecen al código de tu proyecto, diferenciándolos del código interno del framework (`System/`) o librerías externas (`vendor/`).

4. **Pestañas de Contexto en Tiempo Real**:
   - **`Request`**: Método HTTP (`GET`, `POST`), URI solicitada, Query String e IP del cliente.
   - **`Headers`**: Todas las cabeceras HTTP recibidas en la petición.
   - **`Session`**: Estado actual de variables de sesión (`$_SESSION`), flash data y tokens.
   - **`Server / Env`**: Servidor web, ruta raíz (`document_root`), script en ejecución y runtime.

### B. Modo Producción (`CRONOS_APP_DEBUG=false`)

Por seguridad estricta:
- Se oculta cualquier detalle de base de datos, credenciales, stack trace o rutas del sistema de archivos.
- Si existe la vista `resources/views/errors/500.blade.php`, se renderiza automáticamente.
- En caso contrario, se devuelve una página limpia y minimalista de error de servidor HTTP 500.
- Si el cliente espera JSON, se devuelve:
  ```json
  {
    "message": "An internal server error occurred."
  }
  ```

---

## 4. Ejemplos Prácticos de Uso

### Ejemplo 1: Depurar una Consulta ORM antes de responder
```php
namespace App\Controllers;

use App\Models\Order;
use Cronos\Http\Request;

class OrderController
{
    public function index(Request $request)
    {
        $orders = Order::where('status', '=', 'pending')
            ->orderBy('created_at', 'DESC')
            ->get();

        // Inspeccionar los pedidos encontrados y el usuario en sesión:
        dump($orders, session()->get('user_id'));

        return view('orders.index', compact('orders'));
    }
}
```

### Ejemplo 2: Probar el comportamiento de excepciones
```php
// En routes/web.php (solo para pruebas locales)
Route::get('/test-error', function () {
    throw new \Exception("¡Prueba de la pantalla visual interactiva de Cronos!");
});
```
Al visitar `/test-error` en tu navegador con `CRONOS_APP_DEBUG=true`, verás la interfaz visual de depuración con el código fuente y el visor de contexto.
