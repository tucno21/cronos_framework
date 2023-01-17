# CRONOS FRAMEWORK PHP 8.1

## Requirimientos

- PHP >= 8.1
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

[☝️Inicio](#cronos-framework-php-81)

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

[☝️Inicio](#cronos-framework-php-81)

```php
//ruta con parametros
Route::get('/user/{id}', [Controller::class, 'user'])->name('user')->middleware([LoginMiddleware::class]);
```

## CREAR CONTROLADOR Y MODELO DESDE CONSOLA

[☝️Inicio](#cronos-framework-php-81)

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

## CONSTANTES GENERALES

[☝️Inicio](#cronos-framework-php-81)

```php
ROOT //   path...

DIR_PUBLIC //   path.../public

DIR_IMG    //    path.../public/PATH_FILE_STORAGE.env
```

## HELPERS DEPURACION

[☝️Inicio](#cronos-framework-php-81)

```php
//detiene la ejecución del script y muestra el contenido de la variable
dd($variable);
//muestra el contenido de la variable sin detener la ejecución del script
d($variable);
```

# USO EN LOS CONTROLADORES

## HTTP REQUEST

[☝️Inicio](#cronos-framework-php-81)

```php
public function user(Request $request);

//obtener todos los datos del request
$request->all();

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

[☝️Inicio](#cronos-framework-php-81)

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

[☝️Inicio](#cronos-framework-php-81)

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

//retarnar a la ruta anterior
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

[☝️Inicio](#cronos-framework-php-81)

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

[☝️Inicio](#cronos-framework-php-81)

```php
$user = new User();
$user->name = 'name';
$user->email = 'email';
$user->password = 'password';
$user->save();


//guardar datos con el metodo create
User::create([
    'name' => 'name',
    'email' => 'email',
    'password' => 'password'
]);

//ambos metodos retornan el objeto que se guardo con el id
```

## ACTUALIZAR DATOS

[☝️Inicio](#cronos-framework-php-81)

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

[☝️Inicio](#cronos-framework-php-81)

```php
$user = User::delete($id);

// retorna un booleano
```

## CONSULTAS

[☝️Inicio](#cronos-framework-php-81)

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

[☝️Inicio](#cronos-framework-php-81)

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

# DIRECTIVA PARA LAS VISTAS

[☝️Inicio](#cronos-framework-php-81)

la vista creada en la carpeta `resources/views` se puede llamar desde el controlador con la directiva `view()` y se le pasa como parametro el nombre de la vista y un array con los datos que se quieren pasar a la vista

por ejemplo `home.php` es la vista que se quiere llamar desde el controlador

```php
@include('layouts.head')
<h1>hola mundo</h1>
@include('layouts.footer')
```

la directiva `@include` sirve para incluir un archivo de la carpeta `resources/views` en la vista que se esta llamando

```php
la directiva `@include` recibe un parametro el nombre del archivo que se quiere incluir

//ejemplo
@include('layouts.head')

//donde estaria buscando el archivo resources/views/layouts/head.php
```

## helper para la vista

[☝️Inicio](#cronos-framework-php-81)

si en el controlado se uso return `back()->withErrors($dataInput, $errors, $status = 200)`

```php
//los $errors puede usar el siguiente helper

//para saber si exite de uno de los imputs
(ifError('name'))
//impriir el error
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
//usar la funcion route() como parametro el nombre de la ruta
<a class="nav-link" aria-current="page" href="<?= route('home.login') ?>">Login</a>

//si declaro la ruta con parametros
<a class="nav-link" aria-current="page" href="<?= route('home.login', ['id' => 1]) ?>">Login</a>
```

# SESSIONES

[☝️Inicio](#cronos-framework-php-81)

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

[☝️Inicio](#cronos-framework-php-81)

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
| confirm                      | compararar dos imputs iguales, agregar la 2da entrada "\_confirm"  | `confirm`                    |
| slug                         | texto tipo slug **aa-bb-cc**                                       | `slug`                       |
| text                         | solo texto                                                         | `text`                       |
| choice:param                 | la valor de ser igual al **param**                                 | `choice:table`               |
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

[☝️Inicio](#cronos-framework-php-81)

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
