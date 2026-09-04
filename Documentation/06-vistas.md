# Vistas

El motor de plantillas de Cronos es **BladeEngine**: compila las vistas a PHP plano con cache automatico por dependencias y las ejecuta en un scope aislado. La sintaxis es la de Blade (Laravel).

## Reglas de Oro (para desarrolladores e IAs)

1. Las vistas viven en `resources/views/` y se referencian con **notacion de punto**: `view('home.index')` → `resources/views/home/index.php`.
2. `{{ $x }}` **escapa** HTML (seguro contra XSS); `{!! $x !!}` imprime crudo (solo HTML de confianza).
3. El layout se define **una sola vez** en `layouts/app.php`; las vistas hijas lo usan con `@extends` y `@section`.
4. Los componentes se crean como archivos en `resources/views/components/` y se usan como etiquetas `<x-nombre>` sin registrar nada.
5. `@foreach` expone la variable **`$loop`** (index, iteration, count, first, last, remaining, parent). No existe Carbon: los timestamps son strings.

## Estructura

```
resources/views/
├── layouts/
│   └── app.php              # esqueleto unico: <head>, nav, @yield('content'), stacks
├── partials/
│   └── nav.php              # fragmentos incluidos por el layout
├── components/              # componentes anonimos <x-*>
│   ├── alert.php
│   ├── badge.php
│   ├── button.php
│   ├── card.php
│   ├── input.php
│   └── textarea.php
├── home/
│   └── index.php            # paginas por modulo
├── errors/
│   ├── 404.php              # paginas de error por codigo HTTP
│   └── 500.php
└── spa/
    └── index.php            # shell de la SPA (reactapp)
```

Convenciones:
- El **layout** es el dueno del esqueleto HTML; la vista **hija** es la que se renderiza.
- Las vistas de error van en `errors/` (plural) nombradas por codigo.
- Usar sintaxis Blade en las vistas: no mezclar `<?= ?>` ni la constante `base_url`; para URLs usar `{{ route('nombre') }}` y `@asset('ruta')`.

## Renderizar una Vista

```php
// desde un controlador
return view('home.index');
return view('dashboard.show', ['blog' => $blog, 'pageTitle' => $blog->title]);
```

La firma con tercer argumento de layout (`view('x', [], 'layout')`) esta **deprecada**: los layouts se resuelven con `@extends` dentro de la vista.

## Imprimir Valores

```blade
{{ $variable }}              // escapado con e() (XSS-safe); null → '', bool → '1'/''
{{ $array['clave'] }}        // acceso a array
{{ $objeto->propiedad }}     // acceso a objeto
{{ $x ?? 'default' }}        // expresiones PHP completas
{!! $htmlConfiable !!}       // SIN escape
{{ $datos }}                 // arrays: se imprimen como JSON escapado
```

## Herencia: @extends / @section / @yield

**Layout** (`layouts/app.php`):

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <title>@yield('title', 'Cronos Framework')</title>
    <link href="@asset('assets/css/home.css')" rel="stylesheet">
    @stack('styles')
</head>
<body class="font-sans">
    @include('partials.nav')
    <main>@yield('content')</main>
    <script src="@asset('assets/js/home.js')"></script>
    @stack('scripts')
</body>
</html>
```

**Vista hija** (`home/index.php`):

```blade
@extends('layouts.app')

@section('title', 'Mi Pagina')

@section('content')
    <h1>Hola {{ $usuario->nombre }}</h1>
    <x-button href="https://ejemplo.com">Ir</x-button>
@endsection

@push('scripts')
    <script src="@asset('assets/js/blog.js')"></script>
@endpush
```

Detalles de secciones:

| Directiva | Uso |
|---|---|
| `@section('n', 'contenido')` | forma corta; el contenido es una expresion PHP evaluada al renderizar |
| `@section('n') ... @endsection` | bloque; el contenido se captura con las variables de la vista hija |
| `@section('n') ... @stop` | `@stop` es alias de `@endsection` |
| `@section('n') ... @show` | captura e imprime inmediatamente (tipico en layouts) |
| `@section('n') ... @overwrite` | reemplaza lo definido por el layout sin respetar @parent |
| `@parent` | dentro de una seccion hija: anexa al contenido definido en el layout |
| `@yield('n', 'default')` | imprime la seccion o el default si no fue definida |
| `@hasSection('n') ... @endif` | condicional: la seccion existe y no esta vacia |
| `@sectionMissing('n') ... @endif` | condicional inverso |

La herencia es **multinivel**: `nieto @extends(media)`, `media @extends(app)` funcionan en cadena. En un layout intermedio que quiera exponer un punto de extension, definir la seccion con `@show` y un `@yield` interno con OTRO nombre:

```blade
{{-- layouts/media.php --}}
@extends('layouts.app')
@section('content')
    <div class="wrapper">
        @yield('media-content')
    </div>
@show
```

## Stacks: @push / @prepend / @stack

```blade
{{-- en la vista hija --}}
@push('scripts')
    <script src="pagina.js"></script>
@endpush

{{-- forma corta --}}
@push('styles', '<link rel="stylesheet" href="x.css">')

{{-- al frente del stack --}}
@prepend('scripts')<script>primero</script>@endprepend

{{-- en el layout, donde se deben renderizar --}}
@stack('scripts')
```

- El contenido se acumula en orden; `@prepend` inserta al frente.
- Funcionan igual desde la vista hija, el layout o un include (se evaluan en runtime; la hija renderiza antes que el layout).
- `@pushOnce('stack') ... @endPushOnce`: agrega una sola vez aunque el parcial se incluya varias veces.

## Includes

```blade
@include('partials.tarjeta')
@include('partials.tarjeta', ['titulo' => 'Especial'])   {{-- los datos pasados pisan al scope --}}
@include($parcial)                                       {{-- nombres dinamicos --}}
@includeIf('partials.banner')                            {{-- solo si la vista existe --}}
@includeWhen($usuario->activo, 'partials.aviso')
@includeUnless($usuario->activo, 'partials.suspendido')
@each('partials.item', $tareas, 'tarea')                 {{-- un parcial por elemento --}}
@each('partials.item', $tareas, 'tarea', 'partials.vacio')
```

- El parcial recibe los datos pasados **mas** todas las variables del scope actual (los pasados ganan).
- Los includes pueden anidarse sin limite.

## Loops y $loop

```blade
@foreach($publicaciones as $post)
    @if ($loop->first) <primer> @endif
    {{ $loop->iteration }}/{{ $loop->count }}: {{ $post->titulo }}
    @if ($loop->last) <ultimo> @endif
@endforeach

@forelse($comentarios as $c)
    {{ $c->texto }}
@empty
    <p>Sin comentarios</p>
@endforelse
```

Propiedades de `$loop`: `index` (desde 0), `iteration` (desde 1), `count`, `remaining`, `first`, `last`, `parent` (el `$loop` del nivel exterior, o null).

Tambien disponibles: `@for`, `@while`, `@break`, `@break($cond)`, `@continue`, `@continue($cond)`, `@switch/@case/@default/@break/@endswitch`.

> Nota: las directivas deben ir separadas del texto siguiente por espacio o salto de linea cuando ese texto empieza con letra (`@endif aqui` OK; `@endifaqui` se interpreta como otra palabra).

## Condicionales y Utilidades

```blade
@if / @elseif / @else / @endif          {{-- expresiones PHP completas: parens y comillas OK --}}
@unless($activo) ... @endunless
@isset($var) ... @endisset
@empty($var) ... @endempty
@auth ... @endauth                       {{-- session()->hasUser() --}}
@guest ... @endguest
@php $doble = $n * 2; @endphp            {{-- bloque --}}
@php($doble = $n * 2)                    {{-- inline --}}
@json($datos)                            {{-- json_encode seguro para <script> --}}
@unset($var)
{{-- comentario que NO llega al HTML --}}
@verbatim {{ $x }} y @if quedan literales @endverbatim
@@if(true)                               {{-- @@ escapa la directiva --}}
@dump($var) / @dd($var)
```

## Formularios

```blade
<form method="POST" action="{{ route('blog.store') }}">
    @csrf
    @method('PUT')   {{-- PUT | PATCH | DELETE --}}

    <input name="email" value="{{ old('email') }}" @if(session()->ifError('email')) autofocus @endif>
    @error('email')
        <p class="text-red-600">{{ $message }}</p>
    @enderror

    <input type="checkbox" @checked($recordar)>
    <option @selected($actual == $valor)>
    <button @disabled($bloqueado)>Enviar</button>
    <input @readonly($soloLectura) @required>
</form>
```

Helpers de sesion en vistas: `old('campo')`, `error('campo')`, `ifError('campo')`.

Clases y estilos condicionales:

```blade
<div class="@class(['p-4', 'bg-green' => $ok, 'bg-red' => !$ok])"></div>
<div style="@style(['display:block', 'color:red' => $error])"></div>
```

## Componentes Anonimos <x-*>

Archivo `resources/views/components/alerta.php`:

```blade
@props(['type' => 'info', 'title' => null])

<div class="alert alert-{{ $type }}" {{ $attributes->merge(['role' => 'alert']) }}>
    @if ($title)
        <strong>{{ $title }}</strong>
    @endif
    {{ $slot }}
</div>
```

Uso desde cualquier vista:

```blade
<x-alerta type="error" title="UPS" id="alerta-1">
    Algo salio mal con {{ $detalle }}
</x-alerta>

<x-alerta type="ok"/>          {{-- self-closing --}}

<x-dynamic-component :component="$nombreComponente" type="info"/>
```

Reglas:

| Concepto | Detalle |
|---|---|
| `@props([...])` | primera linea del componente; define props con defaults; lo NO declarado queda en `$attributes` |
| `{{ $slot }}` | contenido por defecto del tag |
| `<x-slot:nombre> ... </x-slot:nombre>` | slot nombrado; tambien disponible como variable `$nombre` |
| `:prop="$expresion"` | binding: la expresion se evalua en el scope del padre |
| `attr="texto"` | literal; si el valor es exactamente `{{ expr }}` se compila a `e(expr)` |
| `attr` (sin valor) | booleano `true` |
| `{{ $attributes }}` | renderiza los atributos extra como HTML (`id="x" class="y"`) |
| `$attributes->merge(['class' => 'base'])` | combina: `class`/`style` se concatenan, el resto gana el del uso |
| `$attributes->get/has/only/except/all` | acceso programatico a la bolsa |
| `@class([...])`, `@style([...])`, `@checked/@selected/@disabled/@readonly/@required` | directivas condicionales |
| `<x-sub.carpeta>` | `resources/views/components/sub/carpeta.php` |
| Nombre de componente | solo valida PHP: `data-id` como prop NO puede extraerse a variable (queda en `$attributes`) |

Componentes incluidos: `x-alert`, `x-badge`, `x-button`, `x-card`, `x-input`, `x-textarea` (props documentadas en el propio archivo).

## URLs y Assets

```blade
<a href="{{ route('blog.show', ['id' => 1]) }}">Ver</a>
<link href="@asset('assets/css/home.css')" rel="stylesheet">
```

`@asset('ruta')` genera la URL absoluta con cache-busting: `http://host/assets/css/home.css?v=1733...` (v = `filemtime` del archivo; si no existe, sin version).

## Directivas Personalizadas

```php
use Cronos\View\Compiler\BladeCompiler;

BladeCompiler::directive('datetime', function ($expression) {
    return "<?php echo date('Y-m-d H:i:s', strtotime({$expression})); ?>";
});
```

```blade
<p>Publicado: @datetime($post->created_at)</p>
```

## Cache

- Compilados en `storage/cache/views/` (un `.php` por vista, con cabecera de dependencias).
- Se recompila **solo** si la vista o alguna dependencia (layout, includes, componentes) es mas nueva que el compilado.
- Limpiar manualmente:

```bash
Remove-Item storage\cache\views\*.php   # Windows
rm storage/cache/views/*.php            # Linux/Mac
```

## Diferencias con Blade de Laravel

| Laravel 13 | Cronos |
|---|---|
| Compilador idéntico en sintaxis `@directiva`, `{{ }}`, `<x-*>` | Misma sintaxis |
| Componentes con clase PHP (`make:component`) | Solo componentes anonimos (archivo) |
| `@can`, `@canany` (policies) | No hay policies; usar `@auth` + condiciones |
| `@lang`, `@choice` (i18n) | No implementado |
| `$attributes->class([...])` | Existe; ademas `@class([...])` global |
| Cache invalida por mtime del propio archivo | Cache invalida por vista **y dependencias** |
| `@each` | Igual |
| Directivas pegadas a letras (`@endifX`) | No compilan (igual que Laravel); separar con espacio |
| `{{ }}` escapa con `e()` | Igual; `e()` tambien acepta objetos con `toHtml()` (bolsas de atributos) |

## Errores Comunes

| Sintoma | Causa | Solucion |
|---|---|---|
| `Too few arguments to function e()` | `{{ }}` vacio en la vista | Revisar `{{  }}` sin contenido |
| `La directiva @foreach requiere una expresion` | `@foreach` sin parentesis | `@foreach($x as $y)` |
| La directiva aparece literal en el HTML | Pegada a una letra (`OK@endif`) o mal escrita | Separar con espacio; revisar nombre |
| `La vista [x] no existe` | Ruta/nombre incorrecto | Verificar carpeta y notacion de punto |
| Cambios en la vista no se ven | Cache con mtime futuro (edicion automatica) | Borrar `storage/cache/views` |
| Slot no recibe `{{ $var }}` del padre | El contenido no pertenece al slot | Verificar `<x-slot:...>` balanceado |

---

> **Anterior**: [05 - Validaciones](05-validaciones.md)
> **Siguiente**: [07 - Sesiones](07-sesiones.md)
