# Guia: Crear un Nuevo Modulo

Guia paso a paso para crear un modulo completo (ejemplo: "Products").

## 1. Crear el Modelo

```bash
php cronos make:model Product
```

Configurar `App/Models/Product.php`:

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
    protected string $created = 'created_at';
    protected string $updated = 'updated_at';
}
```

> **Obligatorio**: definir `$table`, `$primaryKey` y `$fillable`.

## 2. Crear la Tabla en la Base de Datos

Generar la migración y editar el stub con el Schema Builder:

```bash
php cronos make:migration create_products_table
```

En el archivo generado (`App/Migrations/..._create_products_table.php`):

```php
public function up(): void
{
    Schema::create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->decimal('price', 10, 2);
        $table->integer('stock')->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('products');
}
```

Ejecutar la migracion:

```bash
php cronos migrate
```

> Ver la referencia completa del Schema Builder y comandos en: **[14 - Migraciones y Seeders](14-migraciones-y-seeders.md)**.

## 3. Crear el Controlador

```bash
php cronos make:controller ProductController
```

Implementar los metodos CRUD en `App/Controllers/ProductController.php`:

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
            'price' => 'required|numeric',
            'stock' => 'required|integer'
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
            'price' => 'required|numeric',
            'stock' => 'required|integer'
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

## 4. Crear las Vistas

Crear los archivos en `resources/views/products/`:

**`resources/views/products/index.php`** — Listado
```php
@extends('dashboard.layouts.app')

@section('content')
<h1>Productos</h1>
@foreach($products as $product)
    <div>
        <h3>{{ $product->name }}</h3>
        <p>{{ $product->price }}</p>
        <a href="<?= route('products.show', $product->slug) ?>">Ver</a>
    </div>
@endforeach
@endsection
```

**`resources/views/products/create.php`** — Formulario de creacion
```php
@extends('dashboard.layouts.app')

@section('content')
<h1>Nuevo Producto</h1>
<form action="<?= route('products.store') ?>" method="POST">
    @csrf
    <x-input name="name" label="Nombre" required />
    <x-textarea name="description" label="Descripcion" />
    <x-input name="price" type="text" label="Precio" required />
    <x-input name="stock" type="number" label="Stock" required />
    <x-button type="submit" variant="primary">Guardar</x-button>
</form>
@endsection
```

**`resources/views/products/show.php`** — Detalle
```php
@extends('dashboard.layouts.app')

@section('content')
<h1>{{ $product->name }}</h1>
<p>{{ $product->description }}</p>
<p>Precio: {{ $product->price }}</p>
<p>Stock: {{ $product->stock }}</p>
@endsection
```

## 5. Registrar las Rutas

En `routes/web.php`:

```php
use App\Controllers\ProductController;

Route::group(['prefix' => '/products', 'middleware' => [AuthMiddleware::class]], function () {
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::get('/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/', [ProductController::class, 'store'])->name('products.store');
    Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});
```

## 6. Probar

Visitar `http://cronos_framework.test/products` para verificar.

## Convenciones del Proyecto

| Elemento | Convencion | Ejemplo |
|---|---|---|
| Controladores | PascalCase + sufijo `Controller` | `ProductController.php` |
| Modelos | PascalCase, singular | `Product.php` |
| Middlewares | PascalCase + sufijo `Middleware` | `RoleMiddleware.php` |
| Vistas | lowercase con puntos | `view('products.index')` |
| Rutas (URL) | lowercase con guiones | `/product-profile` |
| Tablas BD | lowercase, plural con guiones bajos | `user_profiles` |
| Metodos | camelCase | `getUserProfile()` |

## Errores Comunes a Evitar

1. **Olvidar `$fillable`**: Sin el, el sistema lanzara error al usar `create()` o `update()`.
2. **Olvidar `$table` y `$primaryKey`**: Obligatorios en todos los modelos.
3. **Rutas sin nombre**: Sin `->name()` no se puede usar `route()`.
4. **Vistas con extension**: No usar `.php` en `view()`. Correcto: `view('products.index')`.
5. **Prefijo `/api` en api.php**: No agregar `/api` manualmente, ya se agrega automaticamente.
6. **Middleware en controlador Y ruta**: Usar solo uno de los dos.
7. **Cache de vistas**: Si modificas el motor y no ves cambios, borra `storage/cache/*.php`.

---

> **Anterior**: [10 - Configuracion](10-configuracion.md)
