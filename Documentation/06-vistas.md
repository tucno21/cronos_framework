# Vistas

Las vistas se ubican en `resources/views/` y se renderizan con la funcion `view()`.

## Convencion de Nombres

- Referencia con punto para separar carpetas: `view('dashboard.index')`
- Corresponde al archivo: `resources/views/dashboard/index.php`
- No agregar la extension `.php` al llamar `view()`

## Renderizar una Vista

```php
// Vista simple
return view('home.index');

// Con datos
return view('dashboard.show', ['blog' => $blog, 'pageTitle' => $blog->title]);

// Con layout especifico
return view('dashboard.index', ['data' => $data], 'layouts.admin');
```

## Imprimir Variables

```php
{{ $variable }}          // Con escape HTML (seguro contra XSS)
{!! $variable !!}        // Sin escape (solo HTML de confianza)
{{ base_url }}           // URL base del proyecto
{{ $array['key'] }}      // Acceso a array
{{ $object->property }}  // Acceso a objeto
```

## Layouts y Herencia

**Layout base** (`resources/views/home/layouts/head.php`):
```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle ?? 'Cronos Framework' }}</title>
    <link href="@asset('assets/css/home.css')" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @yield('content')

    <script src="@asset('assets/js/home.js')"></script>
    @stack('scripts')
</body>
</html>
```

**Vista que extiende el layout** (`resources/views/home/index.php`):
```php
@extends('home.layouts.head')

@section('content')
<div class="container">
    <h1>{{ $pageTitle }}</h1>
</div>
@endsection

@push('scripts')
    <script src="@asset('assets/js/blog.js')"></script>
@endpush
```

## Directivas del Motor CronosEngine

### Directivas Basicas

| Directiva | Descripcion |
|---|---|
| `@extends('vista')` | Extiende un layout |
| `@include('vista')` | Incluye otra vista |
| `@section('nombre')` ... `@endsection` | Define una seccion |
| `@yield('nombre')` | Muestra el contenido de una seccion |
| `@push('nombre')` ... `@endpush` | Acumula contenido en un stack |
| `@stack('nombre')` | Renderiza contenido acumulado |

### Control de Flujo

| Directiva | Descripcion |
|---|---|
| `@if($cond)` ... `@elseif($cond)` ... `@else` ... `@endif` | Condicionales |
| `@foreach($array as $item)` ... `@endforeach` | Bucle iterador |
| `@for($i=0; $i<n; $i++)` ... `@endfor` | Bucle for |
| `@while($cond)` ... `@endwhile` | Bucle while |
| `@switch($var)` / `@case` / `@default` / `@break` ... `@endswitch` | Switch |
| `@isset($var)` ... `@endisset` | Renderiza si la variable existe |
| `@empty($var)` ... `@endempty` | Renderiza si la variable esta vacia |
| `@unless($cond)` ... `@endunless` | Condicional inverso (si es falso) |
| `@forelse($arr as $item)` ... `@empty` ... `@endforelse` | Foreach con bloque alternativo si esta vacio |

### Directivas de Formularios y Seguridad

| Directiva | Descripcion |
|---|---|
| `@csrf` | Genera `<input type="hidden" name="_token">` para proteccion CSRF |
| `@method('PUT')` | Genera `<input type="hidden" name="_method">` para otros metodos HTTP |

### Directivas de Autenticacion

| Directiva | Descripcion |
|---|---|
| `@auth` ... `@endauth` | Renderiza si hay usuario autenticado |
| `@guest` ... `@endguest` | Renderiza si NO hay usuario autenticado |

### Directivas de Errores y Debug

| Directiva | Descripcion |
|---|---|
| `@error('campo')` ... `@enderror` | Renderiza si existe error de validacion. `$message` contiene el error |
| `@dump($var)` | Ejecuta `var_dump` sin detener ejecucion |
| `@dd($var)` | Ejecuta `var_dump` y detiene ejecucion |

### Otras Directivas

| Directiva | Descripcion |
|---|---|
| `{{-- comentario --}}` | Comentario que NO aparece en el HTML |
| `@asset('ruta/al/archivo')` | Genera URL con cache-busting automatico (`?v=timestamp`) |

## Componentes x-

Los componentes se ubican en `resources/views/components/` y se registran automaticamente.

### Sintaxis

```php
// Self-closing
<x-badge color="blue" />

// Con contenido
<x-card>Contenido aqui</x-card>

// Con slots nombrados
<x-card>
    <x-slot:header>Titulo</x-slot:header>
    Cuerpo del card
</x-card>
```

### Componentes Disponibles

**`<x-alert>`** — Alerta con variantes
- Props: `type` (success/error/warning/info), `title` (opcional)
```php
<x-alert type="success" title="Exito!">Operacion realizada.</x-alert>
```

**`<x-card>`** — Tarjeta con slots header/footer
- Props: `class`, `shadow` (bool), `padding` (sm/md/lg)
```php
<x-card>
    <x-slot:header><h2>Titulo</h2></x-slot:header>
    Contenido
</x-card>
```

**`<x-button>`** — Boton o enlace
- Props: `type`, `variant` (primary/secondary/danger/ghost), `size` (sm/md/lg), `href`, `disabled`, `class`
```php
<x-button type="submit" variant="primary" size="lg">Iniciar Sesion</x-button>
<x-button href="{{ route('dashboard.index') }}" variant="secondary">Volver</x-button>
```

**`<x-input>`** — Campo de formulario con validacion
- Props: `name` (requerido), `label`, `type`, `placeholder`, `required`, `class`, `variant` (standard/floating)
- Recupera `old()` y muestra errores automaticamente
```php
<x-input name="title" label="Titulo" required />
<x-input name="email" type="email" variant="floating" required />
```

**`<x-textarea>`** — Textarea con validacion
- Props: mismos que `<x-input>` mas `rows` (default 4)
```php
<x-textarea name="content" label="Contenido" rows="6" required />
```

**`<x-badge>`** — Badge/pill de colores
- Props: `color` (blue/green/red/yellow/gray)
```php
<x-badge color="green">Publicado</x-badge>
```

> **Regla critica**: Los atributos de `<x-componente>` solo aceptan valores literales. NO se pueden pasar expresiones PHP como valores.

## Directivas Personalizadas

Se pueden registrar directivas personalizadas con `CronosEngine::directive()`:

```php
use Cronos\View\CronosEngine;

// Sin argumentos
CronosEngine::directive('currentYear', function () {
    return '<?php echo date("Y"); ?>';
});

// Con argumentos
CronosEngine::directive('datetime', function ($expression) {
    return "<?php echo date('Y-m-d H:i:s', {$expression}); ?>";
});

CronosEngine::directive('uppercase', function ($expression) {
    return "<?php echo strtoupper({$expression}); ?>";
});
```

Uso en vistas:
```php
<footer>&copy; @currentYear Mi Empresa.</footer>
<p>Publicado el: @datetime($post->created_at)</p>
```

## Cache de Vistas

Las vistas se cachean en `storage/cache/`. Si no ves cambios despues de modificar una vista, borra el cache:

```bash
# Windows
Remove-Item storage\cache\*.php

# Linux/Mac
rm storage/cache/*.php
```

**Debe borrar el cache manualmente cuando:**
- Modificas `System/View/CronosEngine.php`
- Agregas o modificas directivas personalizadas
- El cache esta corrupto

## Obtener Rutas en Vistas

```php
<a href="<?= route('home.login') ?>">Login</a>
<a href="<?= route('home.login', ['id' => 1]) ?>">Login</a>
```

---

> **Anterior**: [05 - Validaciones](05-validaciones.md)
> **Siguiente**: [07 - Sesiones](07-sesiones.md)
