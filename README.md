# CRONOS FRAMEWORK PHP 8.2

## Requirimientos

- PHP >= 8.2
- COMPOSER

### Directorio de carpetas:

```
📁framework
├───📁 App/
│   ├───📁 Controllers/
│   ├───📁 Middlewares/
│   ├───📁 Models/
│   └───📁 Providers/
├───📁 config/
│   └───📄 app.php
│   └───📄 database.php
│   └───📄 hashing.php
│   └───📄 providers.php
│   └───📄 session.php
│   └───📄 view.php
├───📁 public/
│   └───📄 index.php
│   └───📄 .htaccess.php
├───📁 resources/
│   └───📁 views/
│       └───📁 error/
│       └───└────📄 404.php
│       └───📁 layouts/
├───📁 routes/
│   └───📄 web.php
│   └───📄 api.php
├───📁 System/
│   └───📁 .../
│   └───📄 App.php
├───📄 .gitignore
├───📄 .htaccess
├───📄 composer.json
├───📄 cronos
└───📄 env
```

## **Índice**

- [Instalación](#instalacion)
- [Rutas web](#rutas-web)
- [Rutas con middlewares](#rutas-con-middelwares)
- [Crear controlador y modelo desde consola](#crear-controlador-y-modelo-desde-consola)
- [Constantes generales](#constantes-generales)
- [Helpers depuracion](#helpers-depuracion)
- [Uso en los controladores](#uso-en-los-controladores)
- [HTTP request](#http-request)
- [Middleware en el controlador](#middelwares-en-el-controlador)
- [Validacion de datos del formulario](#validacion-de-datos-del-formulario)
- [HTTP response](#http-response)
- [Eencriptar el password](#encriptar-el-password)
- [Uso en los modelos](#uso-en-los-modelos)
- [Guardar datos](#guardar-datos)
- [Actualizar datos](#actualizar-datos)
- [Eliminar datos](#eliminar-datos)
- [Consultas](#consultas)
- [Ejemplos de consultas](#ejemplos-de-consultas)
- [consultas personalizadas de la base de datos](#consultas-personalizadas-de-la-base-de-datos)
- [Directiva para las vistas](#directiva-para-las-vistas)
- [Directivas Personalizadas](#directivas-personalizadas)
- [helper para la vista](#helper-para-la-vista)
- [Obtener las rutas](#obtener-las-rutas)
- [Sessiones](#sessiones)
- [Tabla de validaiones](#tabla-de-validaciones)

## Intalacion

- Clonar el repositorio
- Ejecutar el comando `composer install`
- Crear un archivo `.env` en la raiz del proyecto
- Configurar el archivo `.env` con los datos de la base de datos

## RUTAS WEB

[☝️Inicio](#cronos-framework-php-82)

en la carpeta `routes` se encuentra el archivo `api.php` donde se definen las rutas de la aplicacion, no es necesario apregar la palabra "/api/" el sistema lo realiza automaticamente

en la carpeta `routes` se encuentra el archivo `web.php` donde se definen las rutas de la aplicacion

```php
//ruta del link, nombre del controlador, nombre del metodo
Route::get('/', [Controller::class, 'index'])->name('home');
//los nombres de la ruta solo en get - en post debe llevar a la misma ruta
Route::get('/login', [Controller::class, 'login'])->name('login');
Route::post('/login', [Controller::class, 'login']);

//ruta con parametros
Route::get('/user/{id}', [Controller::class, 'user'])->name('user');
//el metodo del controlador debe recibir el parametro
public function user(string $id);

// ruta con parametros modelos {user} debe ser el mismo nombre del modelo y se supone que es el id
Route::get('/user/{user}', [Controller::class, 'user'])->name('user');
public function user(User $user);

//ruta con parametros modelo y columna {user:colum} la columna valor del sglu
Route::get('/user/{user:colum}', [Controller::class, 'user'])->name('user');
public function user(User $user);

//el metodo tambien puede recivir el Request [get, post, put, delete]
Route::post('/login', [Controller::class, 'login']);
public function user(Request $request);

//tambien metodos put y delete
Route::put('/user/{user}', [Controller::class, 'user'])->name('user');
Route::delete('/user/{user}', [Controller::class, 'user'])->name('user');

//puede pasar parametro diversos
Route::get('/user/{user:colum}/{id}/producto/{product}', [Controller::class, 'user'])->name('user');
public function user(User $user, string $id, Product $product);
```

### RUTAS CON MIDDELWARES

[☝️Inicio](#cronos-framework-php-82)

```php
//ruta con parametros
Route::get('/user/{id}', [Controller::class, 'user'])->name('user')->middleware([LoginMiddleware::class]);
```

#### soporte de agrupacion de rutas
```php
// si esta en la carpeta api.php esta agrega la palabra /api/
Route::group(['prefix' => '/dashboard'], function () {
    Route::get('/users', [ApiController::class, 'grupos']);
    Route::post('/users', [ApiController::class, 'gruposStore']);
});

Route::group(['prefix' => '/panel-control', 'middleware' => [AuthApiMiddleware::class]], function () {
    Route::get('/users', [ApiController::class, 'grupos']);
    Route::post('/users', [ApiController::class, 'gruposStore']);
});
```

## CREAR CONTROLADOR Y MODELO DESDE CONSOLA

[☝️Inicio](#cronos-framework-php-82)

### Generar controlador

los controladores se encuentran en la carpeta `App/Controllers` y se pueden crear desde la consola

```bash
php cronos make:controller Name
php cronos make:controller Name FolderName

```

### Generar modelo

los modelos se encuentran en la carpeta `App/Models` y se pueden crear desde la consola

```
php cronos make:model Name
php cronos make:model Name FolderName
```

### Generar middlewares

los middlewares se encuentran en la carpeta `App/Middlewares` y se pueden crear desde la consola

```bash
php cronos make:middleware Name
```

### Generar migraciones

el Database se encuentran en la carpeta `App/Migrations` y se pueden crear desde la consola
OBSERVACION: primero configure los datos en le archivo .env
este crea el archivo que debe modificar con sus tablas
```bash
php make:migration database
```

para crear la base de datos y tablas ejecute
```bash
php cronos migrate
```

## CONSTANTES GENERALES

[☝️Inicio](#cronos-framework-php-82)

```php
ROOT //   path...

DIR_PUBLIC //   path.../public

DIR_IMG    //    path.../public/PATH_FILE_STORAGE.env
```

## HELPERS DEPURACION

[☝️Inicio](#cronos-framework-php-82)

```php
//detiene la ejecución del script y muestra el contenido de la variable
dd($variable);
//muestra el contenido de la variable sin detener la ejecución del script
d($variable);
```

# USO EN LOS CONTROLADORES

## HTTP REQUEST

[☝️Inicio](#cronos-framework-php-82)

```php
public function user(Request $request);

//obtener todos los datos del request
$request->all();

//obtener ek nombre mismo si metodo
$request->name;

//obtener un dato del request
$request->input('name');

//consultar si existe un dato en el request
$request->has('name');

//obtener todos los datos del request excepto los que se le pasan
$request->except(['name', 'email']);

//obtener todos los datos del request solo los que se le pasan
$request->only(['name', 'email']);

//obtener los datos del un archivo del request
$request->file('name');

//consultar si existe un archivo en el request
$request->hasFile('name');

//consultar la ip
$request->ip();

//obtener el metodo
$request->method();

//obtener el headers o header
$request->headers();
$request->headers('x-token');

//obtener la cokies
$request->cookies();
$request->cookies('aaa');

//obtener si la consulta es segura
$request->isSecure();

//obtener el User-Agent
$request->userAgent();

//consultar si es ajax
$request->ajax();

//obtener el token si es beaser
$request->bearerToken();

//guardar el archivo en el storage
//el primer parametro: es el archivo
//el segundo parametro: nombre del archivo que desea y si no se envia se genera uno aleatorio
//el tercer parametro: nombre de la carpeta si es null se usa PATH_FILE_STORAGE de .env
$request->store($request->file('name'), string $nameFile = null, string $nameFolder = null)
```

## MIDDELWARES EN EL CONTROLADOR

no usar el middleware en el controlador y esta en la ruta o viceversa

```php
// en el constructor del controlador
 public function __construct()
{
    $this->middleware(AuthMiddleware::class);
}
```

## VALIDACION DE DATOS DEL FORMULARIO

[☝️Inicio](#cronos-framework-php-82)

revise la tabla de validaciones al final de la documentación

```php
//en el controlador
public function register(Request $request)
{
    //validar los datos del request
    $valid = $this->validate($request->all(), [
        'name' => 'required|alpha',
        'username' => 'required|alpha_numeric',
        'email' => 'required|email|unique:HomeModel,email',
        'password' => 'required|min:3|max:12|matches:password_confirm',
        'password_confirm' => 'required',
        'photo' => 'requiredFile|maxSize:2|type:jpeg,png,zip,svg+xml',
    ]);

    if ($valid !== true) {
        return back()->withErrors($request->all(), $valid);
    }

    //si la validación es correcta
    //se puede acceder a los datos del request
    $request->input('email');
    $request->input('password');

    //guardar en la base de datos
    User::create($data);
    return redirect()->route('login');
}
```

## HTTP RESPONSE

[☝️Inicio](#cronos-framework-php-82)

```php
//renderizar una vista
//el primer parametro: nombre de la vista
//el segundo parametro: datos que se envian a la vista (OPCIONAL)
//el tercer parametro: codigo de estado (OPCIONAL)
return view('name', ['data' => $data] , $status = 200);

//enviar un json
//el primer parametro: datos que se envian
//el segundo parametro: codigo de estado (OPCIONAL)
return json($data, $status = 200);

//redireccionar a una ruta
return redirect('/login');
//tambien puede usar el nombre de la ruta
return redirect()->route('login');
//tambien puede enviar datos siempre y cuando la ruta tenga parametros
return redirect()->route('login', ['data' => $data]);
//enviar mensaje de session flash
return redirect()->route('login')->with('message', 'mensaje de session flash');

//retornar a la ruta anterior
return back();
//enviar mensaje de session flash
return back()->with('message', 'mensaje de session flash');
//tambien puede enviar datos siempre y cuando la ruta tenga parametros
//el primer parametro: datos que se envian del request
//el segundo parametro: errores que se envian del validator
//el tercer parametro: codigo de estado (OPCIONAL)
return back()->withErrors($dataInput, $errors, $status = 200);
```

## ENCRIPTAR EL PASSWORD

[☝️Inicio](#cronos-framework-php-82)

```php
public function create(Request $request, Hasher $hasher)
 {
    $data = $request->all();
    $data->password = $hasher->hash($data->password);
}

//para comparar el password
$hasher->verify($inputPassword, $request->password);
```

# USO EN LOS MODELOS

## GUARDAR DATOS

[☝️Inicio](#cronos-framework-php-82)

```php
//guardar datos con el metodo create
User::create([
    'name' => 'name',
    'email' => 'email',
    'password' => 'password'
]);

//ambos metodos retornan el objeto que se guardo con el id
```

## ACTUALIZAR DATOS

[☝️Inicio](#cronos-framework-php-82)

```php
$id = 1;
$data = [
    'name' => 'name',
    'email' => 'email',
    'password' => 'password'
];

$user = User::update($id, $data);

//retorna el objeto que se actualizo
//caso contrario retorna un booleano false o "0"
```

## ELIMINAR DATOS

[☝️Inicio](#cronos-framework-php-82)

```php
$user = User::delete($id);

// retorna un booleano
```

## CONSULTAS

[☝️Inicio](#cronos-framework-php-82)

```php
//obtener todos los datos de la tabla no se puede anidar
User::all();

//obtener un dato de la tabla no se puede anidar
User::find($id);

//->first() y ->get() solo funciona en anidaciones

//puede anidar consultas con los metodos, puede no usar uno o varios metodos
//pero debe respetar el orden y terminar con ->get() o ->first();
User::select()
    ->join()
    ->where()
    ->andWhere()
    ->orWhere()
    ->orderBy()
    ->limit()
    ->get();
```

el metodo `where()` puede se reemplazado por `whereBetween()` y `whereConcat()` pero no se pueden anidar entre si
el metodo `andWhere()` y `orWhere()` solo funciona con `where()`, `whereBetween()` y `whereConcat()`

```php
//select solo las columnas que se quieren obtener
//select() permite varios parametros
User::select('name', 'email')->get();

//join sirve para unir tablas
User::join('nombreOtraTabla', 'clientes.id', '=', 'ventas.cliente_id')->get();

//where sirve para buscar por igualdad
//los parametro de where(), si solo envia dos parametros se asume que busca por igualdad
User::where('column', 'valueColumnn')->get();
//si envia tres parametros se asume que busca por operador
User::where('column', 'operador', 'valueColumn')->get();

//andWhere sirve para buscar por una condicion adicional "AND" (y tambien)
//los parametro de andWhere(), si solo envia dos parametros se asume que busca por igualdad
User::where('column', 'valueColumn')->andWhere('colum', 'valueColumn')->get();
//si envia tres parametros se asume que busca por operador
User::where('column', 'operador', 'valueColumn')->andWhere('column', 'operador', 'valueColumn')->get();

//orWhere sirve para buscar por una condicion adicional "OR" (o tambien)
//los parametro de orWhere(), si solo envia dos parametros se asume que busca por igualdad
User::where('column', 'valueColumn')->orWhere('colum', 'valueColumn')->get();
//si envia tres parametros se asume que busca por operador
User::where('column', 'operador', 'valueColumn')->orWhere('columm', 'operador', 'valueColumn')->get();

//orderBy sirve para ordenar los datos de la consulta por una columna en especifico
//ascendente (asc) o descendente (desc)
User::orderBy('column', 'desc')->get();

//los parametro de limit() son los campos que se quieren limitar permite varios parametros
User::limit(10)->get();

//whereBetween sirve para buscar por un rango de valores
//los parametro de whereBetween() son la columna, el valor minimo y el valor maximo
User::whereBetween('column', 'valueMin', 'valueMax')->get();

//whereConcat sirve para buscar por una concatenacion de valores
//los parametro de whereConcat() son la columna, el valor minimo y el valor maximo
User::whereConcat('columnas', 'operadorOvalor', 'valor')->get();

//ejemplo whereConcat con dos parametros
User::whereConcat('column1 - column2', 'value')->get();
//ejemplo whereConcat con tres parametros
User::whereConcat('column1 - column2', 'operador', 'value')->get();
```

### EJEMPLOS DE CONSULTAS

[☝️Inicio](#cronos-framework-php-82)

```php
//obtener todos los datos de la tabla
User::all();

//obtener un dato de la tabla
User::find($id);

//obtener todos los datos de la tabla ordenados por id de forma descendente
User::orderBy('id', 'desc')->get();

//obtener todos los datos de la tabla ordenados por id de forma ascendente
User::orderBy('id', 'asc')->get();

//obtener todos los datos de la tabla ordenados por id de forma ascendente y limitar a 10
User::orderBy('id', 'asc')->limit(10)->get();

//obtener todos los datos de la tabla ordenados por id de forma ascendente
User::orderBy('id', 'asc')->limit(10)->get();

//obtener todos los datos de la tabla ordenados por id de forma ascendente  y obtener solo los datos de la columna name
User::select('name')->orderBy('id', 'asc')->limit(10)->get();

//obtener todos los datos de la tabla ordenados por id de forma ascendente  y obtener solo los datos de la columna name y email
User::select('name', 'email')->orderBy('id', 'asc')->limit(10)->get();

//obtener todos los datos de la tabla ordenados por id de forma ascendente  y obtener solo los datos de la columna name y email y unir la tabla roles
User::select('name', 'email')->join('roles', 'users.id', '=', 'roles.user_id')->orderBy('id', 'asc')->limit(10)->get();


//obtener todos los datos de la tabla ordenados por id de forma ascendente  y obtener solo los datos de la columna name y email y unir la tabla roles y obtener solo los datos de la columna name de la tabla roles y obtener solo los datos de la tabla roles donde el id sea igual a 1 y el id de la tabla users sea igual a 1
User::select('name', 'email', 'roles.name')->join('roles', 'users.id', '=', 'roles.user_id')->where('roles.id', 1)->andWhere('users.id', 1)->orderBy('id', 'asc')->limit(10)->get();

// Obtener todos los blogs de un usuario con user.id específico
$blogs = Blog::select('blogs.*', 'users.name as author')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->where('users.id', '1')
            ->orderBy('blogs.created_at', 'DESC')
            ->get();

// Obtener un blog específico con su autor
 $blog = Blog::select('blogs.title', 'blogs.content', 'users.name as author')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->where('blogs.slug', 'hola-peru')
            ->dd();
```

### depuracio de la sentencia que se forma sql y los datos
```php
//no realiza la consulta, pero se visualiza la sentencia sql
$posts = Blog::select('blogs.title', 'users.email')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->where('blogs.content', 'LIKE', '%esta%')
            ->limit(5)
            ->dd();
```

### visualizar datos de create, update, y consultas
```php
$data = User::all();

//ver por propiedades
$data->name;

//ver en forma de array
$data->toArray();

//ver en forma de objeto
$data->toObject();


//para generar json
return json($data);
```

### consultas personalizadas de la base de datos

```php
//debe realizar desde el modelo
//ejemplo de consulta personalizada
public static function getVentasEstado($estado)
{
    $sql = "SELECT * FROM ventas WHERE estado = ?";
    return self::statement($sql, [$estado]);
}
```

# HELPERS DE APOYO
[☝️Inicio](#cronos-framework-php-82)

## OBTENER LA URL DE ARCHIVOS O IMAGEN
```php
$blog->imagen = LInkFile::setName($blog->imagen);

//si guardo e una carpera dentr0 de la carpeta de PATH_FILE_STORAGE

$blog->documento = LInkFile::setName($blog->docuemnto, 'archivos');
```

## ALMECENAR IMAGEN
Almacenar imagen en el nombre de la carpeta de PATH_FILE_STORAGE .env

para esto se usa la libreria "intervention/image"
```php
$nameFoto = MoveFileImagen::setImage($request->file('image'))
            ->size(400, 100) //width, height
            ->delete('nombreimagen.png') //eliminar la imagen del store
            ->format(ImageFormat::WEBP) //por defecto ImageFormat::WEBP, se tiene tambien ImageFormat::JPG, ImageFormat::PNG
            ->quality(90) //por defecto 90
            ->maintainAspectRatio(true) //por defecto false si se enviar tañamos no proporcionales a la imagen, este corta la imagen
            ->save();

// no es necesario todos los metodos los basico
$nameFoto = MoveFileImagen::setImage($request->file('image'))
            ->size(400)
            ->save();

// te retorna el nombre de la imagen almacenda
```

## ALMECENAR ARCHIVOS
los archivos se almacener en una carperta "archivos" que esta dentro de la carpeta de PATH_FILE_STORAGE .env, pero puede gardar con otro nombre tambien.
```php
//guardar un solo archivo
//retorna el nombre del archivo
$nameArchivo = MoveFile::storeSingle($request->file('archivo'))->originalName()->save();
//amacenar varios archivos que estan dentro de un array
//retorna los nombres de los archivos en un  array
$nameArchivos = MoveFile::storeMultiple($request->file('archivos'))->originalName()->save();


//nobres para los archivos
MoveFile::storeSingle($file)->save(); //genera nombre aleatorio texto numerico // 37e2cb859bbc06c421a695014043d23d.docx
MoveFile::storeSingle($file)->originalName()->save(); //usa lo nombres origibales
MoveFile::storeSingle($file)->dateName()->save(); //nonbre de la fecha y hora //12-02-25-193938.docx
```




# DIRECTIVA PARA LAS VISTAS

[☝️Inicio](#cronos-framework-php-82)

las vistas debe ser creadas en la carpeta `resources/views` se puede llamar desde el controlador con la función `view()` el cual acepta dos parametros:

- primero el archivo de la vista que se quiere llamar (sin la extensión `.php`) ejemplo `home` y si esta en una carpeta se debe especificar la carpeta y el archivo `carpeta/archivo`
- segundo los datos array que se quieren pasar a la vista

### directivas que se puede usar en las vistas

se tiene la siguiente carpeta `resources/views/home`:
dentro de la carpeta `home` se tiene el archivo `index.php`, el archivo `layout.php` y navegacion.php

la directiva `@extends` sirve para extender una vista

```php
@extends('home.layout')
```

la directiva `@section` y `@yield` sirve para crear una sección en la vista

```php
//en el archivo home/layout.php
@yield('content')

//en el archivo home/index.php
@section('content')
    <h1>hola mundo</h1>
@endsection
```

la directiva `@include` sirve para incluir una porción de código en la vista

```php
//en el archivo home/layout.php
@include('home.navegacion')
```

### Directiva `@component` (Componentes con Slots)

[☝️Inicio](#cronos-framework-php-82)

Esta directiva te permite crear componentes reutilizables y pasarles datos (props) y bloques de contenido (slots), similar a cómo funcionan los componentes en frameworks como React.

**Uso en tu vista principal:**

```php
@component('components.card', ['title' => 'Mi Tarjeta'])
    @slot('header')
        <h2>Encabezado Personalizado</h2>
    @endslot

    <p>Este es el contenido principal de la tarjeta (slot por defecto).</p>

    @slot('footer')
        <button>Ver más</button>
    @endslot
@endcomponent
```

**Uso dentro del archivo del componente (ej. `resources/views/components/card.php`):**

```php
<!-- resources/views/components/card.php -->
<div class="card">
    <?php if (isset($title)): ?>
        <h3>{{ $title }}</h3>
    <?php endif; ?>

    <?php if (isset($__component_slots['header'])): ?>
        <div class="card-header">
            <?= $__component_slots['header'] ?>
        </div>
    <?php endif; ?>

    <div class="card-body">
        <?= $__component_slots['default'] ?? '' ?>
    </div>

    <?php if (isset($__component_slots['footer'])): ?>
        <div class="card-footer">
            <?= $__component_slots['footer'] ?>
        </div>
    <?php endif; ?>
</div>
```

*   Los datos pasados en el segundo argumento de `@component` (ej. `['title' => 'Mi Tarjeta']`) se acceden directamente como variables dentro del componente (ej. `$title`).
*   El contenido entre `@slot('nombre')` y `@endslot` se accede a través de la variable `$_slots['nombre']`.
*   Cualquier contenido dentro del bloque `@component ... @endcomponent` que no esté dentro de un `@slot` nombrado se considera el slot por defecto y se accede a través de `$_slots['default']`.

. Ejemplo de referencia

```php
//en el archivo home/layout.php
<body>
    @include('home.header')
    <main>
        <section>
            @yield('content1')
        </section>

        <section>
            @yield('content2')
        </section>
    </main>
    <footer>
        <p>Derechos reservados &copy; 2023</p>
    </footer>
</body>

//en el archivo home/index.php
@extends('home.layout')

@section('content1')
<h1>Título de la sección</h1>
<p>Contenido de la sección</p>
@endsection

@section('content2')
<h1>Título de otra sección</h1>
<p>Contenido de la otra sección</p>
@endsection

//en el archivo home/header.php
<header>
    <nav>
        <ul>
            <li><a href="#">Inicio</a></li>
            <li><a href="#">Acerca de</a></li>
            <li><a href="#">Contacto</a></li>
        </ul>
    </nav>
</header>
```

### Directivas Personalizadas

[☝️Inicio](#cronos-framework-php-82)

El motor de vistas ahora soporta la creación de directivas personalizadas, lo que te permite extender sus funcionalidades sin modificar el código fuente de `CronosEngine.php`.

**Cómo Definir y Usar una Directiva Personalizada:**

1.  **Registro de Directivas:**
    Utiliza el método estático `CronosEngine::directive(string $name, callable $handler)` para registrar una nueva directiva.
    *   `$name`: Es el nombre de la directiva (ej. `'datetime'`, `'uppercase'`).
    *   `$handler`: Es una función de callback (o closure) que se ejecutará cuando el motor encuentre tu directiva. Esta función recibirá los argumentos que se pasen a la directiva como una cadena.

    **Ejemplo de Registro (puedes colocar esto en un Service Provider, como `App/Providers/ViewServiceProvider.php`, o en `System/App.php` después de que el motor de vistas esté disponible):**

    ```php
    use Cronos\View\CronosEngine;

    // Directiva sin argumentos: @currentYear
    CronosEngine::directive('currentYear', function () {
        return '<?php echo date("Y"); ?>';
    });

    // Directiva con argumentos: @datetime($timestamp)
    CronosEngine::directive('datetime', function ($expression) {
        // $expression contendrá la cadena de los argumentos, ej. '$post->created_at'
        return "<?php echo date('Y-m-d H:i:s', {$expression}); ?>";
    });

    // Directiva para convertir a mayúsculas: @uppercase($text)
    CronosEngine::directive('uppercase', function ($expression) {
        return "<?php echo strtoupper({$expression}); ?>";
    });
    ```

2.  **Uso de la Directiva en tus Vistas PHP:**
    Una vez registrada, puedes usar tu directiva personalizada en cualquier archivo de vista.

    **Ejemplo de Uso en una Vista:**
    ```php
    <!-- En tu vista (ej. resources/views/home/index.php) -->
    <footer>
        &copy; @currentYear Mi Empresa.
    </footer>

    <p>Publicado el: @datetime($post->created_at)</p>

    <p>Título en mayúsculas: @uppercase($post->title)</p>
    ```

Esta funcionalidad te da un control mucho mayor sobre cómo se procesan tus plantillas y te permite adaptar el motor de vistas a tus necesidades específicas de manera muy flexible.

## helper para la vista

[☝️Inicio](#cronos-framework-php-82)

si en el controlador se uso `return back()->withErrors($dataInput, $errors, $status = 200)`

```php
//Para los $errors

//para saber si exite error
(ifError('name'))
//imprimir el error
 <?= error('name') ?>

//para que los datos no se borren al recargar la pagina
<?= old('name') ?>
```

### ejemplo

```html
<div class="mb-3">
	<label class="form-label">Correo</label>
	<input
		name="email"
		type="text"
		class="form-control <?= ifError('email') ? 'is-invalid' : '' ?>"
		value="<?= old('email') ?>"
	/>

	<?php if (ifError('email')) : ?>
	<div class="invalid-feedback">
		<?= error('email') ?>
	</div>
	<?php endif; ?>
</div>
```

### obtener las rutas

```html
//usar la funcion route() como parametro el nombre de la ruta declarada en el archivo routes.php
<a class="nav-link" aria-current="page" href="<?= route('home.login') ?>">Login</a>

//si declaro la ruta con parametros
<a class="nav-link" aria-current="page" href="<?= route('home.login', ['id' => 1]) ?>">Login</a>
```

# SESSIONES

[☝️Inicio](#cronos-framework-php-82)

la session se puede crear desde el controlador con la helper `session()` y anidar los siguientes casos:

```php
//crear una session
session()->set('nombre', 'valor');
session()->put('nombre', 'valor');

//agrear un valor a una sesion que ya existe, debe ser en una sesion ya creada anteriormente
session()->push('nombre', 'valor');

//session que se elimina al recargar la pagina
session()->flash('nombre', 'valor');

//si existe la session devuelve true o false
session()->has('nombre');

//obtener el valor de una session
session()->get('nombre');

//obtener el valor de una session y eliminarla
session()->pull('nombre');

//eliminar una session
session()->forget('nombre');
session()->remove('nombre');

//eliminar todas las sessiones
session()->flush();
```

crear session sin clave

```php
session()->attempt($data);

//consultar si existe una sesion
session()->hasUser();

//obtener el valor de una sesion
session()->user();

//eliminar una sesion
session()->logout();
```

## TABLA DE VALIDACIONES

[☝️Inicio](#cronos-framework-php-82)

| Validación                   | Descripción                                                        | Ejemplo                      |
| ---------------------------- | ------------------------------------------------------------------ | ---------------------------- |
| alpha                        | Solo letras                                                        | `alpha`                      |
| alpha_space                  | Solo letras y espacios                                             | `alpha_space`                |
| alpha_dash                   | Solo letras, espacios y guiones                                    | `alpha_dash`                 |
| alpha_numeric                | Solo letras y números                                              | `alpha_numeric`              |
| decimal                      | Solo números decimales                                             | `decimal`                    |
| integer                      | Solo números enteros                                               | `integer`                    |
| is_natural                   | Solo números naturales                                             | `is_natural`                 |
| is_natural_no_zero           | Solo números naturales sin cero                                    | `is_natural_no_zero`         |
| numeric                      | Solo números                                                       | `numeric`                    |
| required                     | Requerido, no vacio, es obligatorio                                | `required`                   |
| email                        | Correo electronico                                                 | `email`                      |
| url                          | texto tipo URL                                                     | `url`                        |
| min:number                   | Minimo de caracteres                                               | `min:5`                      |
| max:number                   | Maximo de caracteres                                               | `max:5`                      |
| string                       | Solo texto                                                         | `string`                     |
| confirm                      | comparar dos imputs iguales, agregar la 2da entrada "\_confirm"    | `confirm`                    |
| slug                         | texto tipo slug **aa-bb-cc**                                       | `slug`                       |
| text                         | solo texto                                                         | `text`                       |
| choice:param                 | la valor debe ser igual al **param**                               | `choice:table`               |
| between:min,max              | entra minima y maxima de caracteres                                | `between:1,5`                |
| datetime                     | fecha y hora **Y-m-d H:i:s**                                       | `datetime`                   |
| time                         | hora **H:i:s**                                                     | `time`                       |
| date                         | fecha **Y-m-d**                                                    | `date`                       |
| matches:2inputs              | comparar dos imputs                                                | `matches:otro_input`         |
| unique:model,column          | unico en la tabla **model** y columna **column**                   | `unique:User,email`          |
| not_unique:model,column      | no unico en la tabla **model** y columna **column**                | `not_unique:User,email`      |
| password_verify:model,column | verificar la contraseña en la tabla **model** y columna **column** | `password_verify:User,email` |

### consideraciones para el uso

si va ha usar la validacion `unique:model,column`, `not_unique:model,column` la palabra `model` debe ser exactamente igual al nombre del modelo: `User` al igual que la columna `email` debe ser exactamente igual al nombre de la columna en la base de datos.

si va ha usar la validacion `password_verify:model,column` por ejemplo:

```php
'password' => 'required|password_verify:User,email',
```

la palabra `model` debe ser exactamente igual al nombre del modelo: `User` y la palabra `column` esta relacionado a la entrada(input) `email`, y este busca el valor del input `column` en la base de datos y de los datos compara con el valor del input `password`.

#### los modelos que se describe en las consideraciones deben estar en la carpeta `app/Models` y no una subcarpeta.

### Validaciones para archivos

| Validación     | Descripción                         | Ejemplo        |
| -------------- | ----------------------------------- | -------------- |
| requiredFile   | Requerido, no vacio, es obligatorio | `requiredFile` |
| maxSize:number | tamaño maximo del archivo en bytes  | `maxSize:1000` |
| type:param     | tipo de archivo                     | `type:jpg,png` |

## Creditos 📌

[☝️Inicio](#cronos-framework-php-82)

_Modelo de framework php_

- [Juan de la Torre](https://www.udemy.com/course/desarrollo-web-completo-con-html5-css3-js-php-y-mysql/) - Curso PHP y base framework.
- [The Codeholic](https://www.youtube.com/playlist?list=PLLQuc_7jk__Uk_QnJMPndbdKECcTEwTA1) - Framework php.
- [Antonio Sarosi](https://antoniosarosi.com/) - Framework php.

_Modificacion para la validacion del formulario de_

- [mkakpabla](https://github.com/mkakpabla/form-validation-php#readme) - Validacion Adaptado.
- [booomerang](https://github.com/booomerang/Validatr/tree/master/src) - Validacion php.

_inspirado en:_

- [codeigniter](https://codeigniter.com/user_guide/libraries/validation.html) - formato y uso de validaciones.
- [laravel](https://laravel.com/docs/8.x/validation) - estilo de las validaciones y funciones.

_Y A TODO LOS DEV DE YOUTUBE_
