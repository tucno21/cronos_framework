# Vistas

Documentacion completa del motor de plantillas de Cronos (**BladeEngine**). Compila las vistas a PHP plano con cache automatico por dependencias y las ejecuta en un scope aislado. La sintaxis es la de Blade (Laravel 13), con diferencias puntuales documentadas en la seccion 24.

## Reglas de Oro (para desarrolladores e IAs)

Nunca asumas comportamientos de Laravel que no esten en esta guia. Las 10 reglas que evitan el 90% de los errores:

1. Las vistas viven en `resources/views/` y se referencian con **notacion de punto**: `view('home.index')` → `resources/views/home/index.php`. Tambien se acepta barra (`view('home/index')`), pero usa punto siempre.
2. `{{ $x }}` **escapa** HTML (XSS-safe). `{!! $x !!}` imprime crudo: solo con HTML de confianza. Nunca uses `{!! !!}` con input de usuario.
3. El layout del sitio es **uno solo**: `layouts/app.php`. La vista hija lo declara con `@extends('layouts.app')` en su primer linea. Nunca incluyas el layout con `@include`.
4. `@extends` es literal con comillas: `@extends($var)` **no funciona** (no hay extends dinamico).
5. En un layout intermedio (herencia multinivel) **no repitas el `@yield` del mismo nombre**: se imprimiria dos veces. Usa `@section ... @show` + `@yield` con otro nombre.
6. `@foreach` y `@forelse` exponen **`$loop`**. `@for` y `@while` **NO tienen `$loop`** (diferencia con Laravel).
7. El contenido de un **slot** se evalua con las variables del **padre** (donde se escribe el tag); el **archivo del componente** solo ve sus props/slots, jamas las variables del padre.
8. `@props([...])` va en la **primera linea del componente, en modo HTML**: nunca dentro de un bloque `<?php` abierto (rompe el compilado).
9. No existe Carbon: los timestamps son strings de la BD. No existe `Log::`, `Auth::` ni facades: usa los helpers (`session()`, `route()`, `asset()`).
10. Propiedades de componentes con guion (`data-id`) no pueden extraerse a variables PHP: quedan en `$attributes`.

## Indice

1. Reglas de Oro
2. Arquitectura: jerarquia de renderizado y pipeline
3. Estructura de carpetas y jerarquia de archivos
4. Renderizar vistas desde el controlador
5. Imprimir valores (escape)
6. Herencia: @extends / @section / @yield / @parent
7. Stacks: @push / @prepend / @stack / @pushOnce
8. Includes: @include / @includeIf / @includeWhen / @includeUnless / @each
9. Loops y la variable $loop
10. Condicionales y utilidades
11. Formularios y sesion
12. Componentes anonimos x-*
13. Scope: que variables ve cada contexto
14. URLs, rutas y assets
15. Atributos condicionales y @class / @style
16. Directivas personalizadas
17. Cache: mecanica e invalidacion
18. Seguridad (XSS, inclusion de archivos, verbatim)
19. Referencia completa de directivas
20. Lo que el motor NO soporta
21. Errores y excepciones
22. Guia de decision: layout, include o componente
23. Recetas completas
24. Diferencias con Blade de Laravel 13
25. Errores comunes y diagnostico
26. Cheat sheet para IAs
27. Mapa de tests

---

## 2. Arquitectura: jerarquia de renderizado y pipeline

### 2.1 Jerarquia de renderizado

El orden de ejecucion en un request con vista NO es el orden del texto: la vista hija se ejecuta COMPLETA primero y el layout al final (via footer compilado). Eso es lo que permite que las secciones ya esten capturadas cuando el layout las imprime.

```
Request
└─ Controller → view('home.index', $params)
   └─ BladeEngine::render('home.index', $params)
      └─ [1] EJECUTA resources/views/home/index.php (scope: $params)
      |     ├─ @section('content') ... captura el cuerpo en un buffer
      |     ├─ @push('scripts') ... acumula en el stack
      |     └─ (al terminar) FOOTER compilado →
      └─ [2] EJECUTA resources/views/layouts/app.php (mismo render, secciones ya cargadas)
            ├─ @include('partials.nav')  → sub-render inmediato
            ├─ @yield('content')         → imprime lo capturado en [1]
            ├─ @stack('scripts')         → imprime lo acumulado
            └─ salida final = HTML completo
```

Consecuencias practicas de esta jerarquia:

- Los `@push` de la hija quedan ANTES que los `@push` del propio layout (la hija corre primero).
- Una seccion definida en la hija puede usar `{{ $var }}` del controlador; el layout tambien ve los params del controlador.
- `@hasSection` en el layout SI ve lo definido por la hija (se evalua despues).

### 2.2 Pipeline de compilacion (una sola vez por version de vista)

```
@@ escape → verbatim → comentarios → extends(footer) → sections → yield → stacks
→ includes → once → props → atributos condicionales → csrf/method/error → asset
→ dump/dd → directivas personalizadas → componentes x-* → php → {!! !!}
→ break/continue → switch → forelse → foreach → for → while → condicionales
→ php inline → json → unset → {{ }} → restaurar verbatim + footer del layout
```

El resultado se guarda en `storage/cache/views/{md5}.php` con una cabecera de dependencias y se incluye como PHP normal en cada request (ver seccion 17).

---

## 3. Estructura de carpetas y jerarquia de archivos

```
resources/views/
├── layouts/                 # NIVEL 1: esqueletos HTML completos
│   └── app.php              #   el dueno de <html>, <head>, nav, @yield, stacks
├── partials/                # NIVEL 2: fragmentos sin vida propia
│   └── nav.php              #   incluidos SIEMPRE por un layout o una vista
├── components/              # NIVEL 2: piezas reutilizables auto-contenidas
│   ├── alert.php            #   se usan como <x-alert> desde cualquier nivel
│   ├── badge.php
│   ├── button.php
│   ├── card.php
│   ├── input.php
│   └── textarea.php
├── home/                    # NIVEL 3: paginas por modulo (una por ruta)
│   └── index.php
├── errors/                  # NIVEL 3: paginas de error por codigo HTTP
│   └── 404.php
└── spa/
    └── index.php            # shell de la SPA (sin layout, HTML completo propio)
```

Reglas de la jerarquia:

| Nivel | Quien lo usa | Contiene | NO debe contener |
|---|---|---|---|
| `layouts/` | las paginas (`@extends`) | esqueleto completo + `@yield` + `@stack` | contenido de negocio |
| `partials/` | layouts y paginas (`@include`) | fragmentos reusables ligados al proyecto | props con logica compleja (para eso: componente) |
| `components/` | cualquier vista (`<x-*>`) | piezas con contrato de props/slots | `@extends` (un componente jamas extiende un layout) |
| `{modulo}/` | el controlador (`view('modulo.pagina')`) | paginas completas con `@extends` | fragmentos reutilizables (subir a partials/components) |
| `errors/` | ExceptionHandler | paginas por codigo HTTP (404, 500...) | layout pesado |

Convenciones de sintaxis:

- Todo archivo de vista usa sintaxis Blade. **MAL:** mezclar `<?= $x ?>` o la constante `base_url`. **BIEN:** `{{ $x }}`, `{{ route('nombre') }}`, `@asset('ruta')`.
- Nombres de archivo en minuscula, `kebab-case` o minuscula simple; la vista principal de un modulo se llama `index.php`.
- Las vistas de error van en `errors/` (plural) nombradas por codigo exacto: `404.php`, `500.php`.

---

## 4. Renderizar vistas desde el controlador

```php
// BIEN: notacion de punto
return view('home.index');

// BIEN: con datos (quedan como variables en la vista)
return view('blog.show', ['blog' => $blog, 'pageTitle' => $blog->titulo]);

// MAL: extension o ruta absoluta
return view('home.index.php');   // buscara "home/index.php.php"
return view('/var/www/otro.php'); // nunca: paths absolutos

// DEPRECADO: el tercer argumento de layout se ignora
return view('home.index', [], 'layouts.admin');  // no hace nada
```

Comportamiento de retorno:

| Situacion | Resultado |
|---|---|
| Vista existe | `Response` HTML 200 con el contenido compilado+ejecutado |
| Vista no existe | `ViewNotFoundException` (la atrapa ExceptionHandler) |
| Error de sintaxis en directivas | `ViewCompileException` con el nombre de la vista |
| Error PHP en runtime de la vista | `ViewCompileException` "Error renderizando la vista [x]" con el error original como `previous` |

El layout NO se pasa desde el controlador: la vista hija declara `@extends`.

---

## 5. Imprimir valores (escape)

```blade
{{ $titulo }}                 {{-- escapado con e(): seguro contra XSS --}}
{{ $usuario->nombre }}        {{-- acceso a propiedad --}}
{{ $config['clave'] }}        {{-- acceso a array --}}
{{ $total ?? 0 }}             {{-- expresion PHP completa: operadores, funciones, ternarios --}}
{{ number_format($precio, 2) }}  {{-- llamadas a funciones OK --}}
{!! $htmlDelAdmin !!}         {{-- SIN escape: solo HTML generado por ti --}}
```

Semantica exacta de `e()` (lo que compila `{{ }}`):

| Valor de entrada | Salida |
|---|---|
| `null` | `''` (cadena vacia) |
| `true` / `false` | `'1'` / `''` |
| `int` / `float` / `string` | el valor con `htmlspecialchars(ENT_QUOTES)` |
| `array` | JSON escapado (`{&quot;a&quot;:1}`) |
| objeto con `toHtml()` | el HTML **crudo** (bolsas de atributos) |
| objeto con `__toString()` | el string **escapado** |
| objeto sin `__toString` | `''` |

**Que hacer y que NO hacer con el escape:**

```blade
{{-- BIEN: dato de usuario escapado --}}
<p>{{ $comentario->texto }}</p>

{{-- MAL: dato de usuario sin escape (XSS) --}}
<p>{!! $comentario->texto !!}</p>

{{-- BIEN: HTML propio y controlado sin escape --}}
{!! $attributes->merge(['class' => 'btn']) !!}

{{-- MAL: expecting that {{ }} keep format: escapa comillas --}}
<button class="{{ $attributes->merge(['class' => 'btn']) }}">   {{-- sale &quot; --}}

{{-- BIEN: para atributos dinamicos dentro de tags HTML, usar crudo o $attributes --}}
<button {!! $attributes->merge(['class' => 'btn']) !!}>
```

**No confundir:** `{{ $datos }}` con un array NO imprime `var_dump`: imprime JSON escapado. Para debug usar `@dump($datos)`.

---

## 6. Herencia: @extends / @section / @yield / @parent

### 6.1 El par basico

**Layout** (`layouts/app.php`) — el dueno del esqueleto:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
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

**Vista hija** (`home/index.php`) — la que renderiza el controlador:

```blade
@extends('layouts.app')

@section('title', 'Bienvenido')

@section('content')
    <h1>Hola {{ $usuario->nombre }}</h1>
@endsection

@push('scripts')
    <script src="@asset('assets/js/home.extra.js')"></script>
@endpush
```

### 6.2 Todas las formas de @section

| Forma | Semantica |
|---|---|
| `@section('n', 'texto')` | corta: el contenido es una EXPRESION PHP evaluada al renderizar. La salida NO se escapa |
| `@section('n', $variable)` | corta con variable |
| `@section('n') ... @endsection` | bloque: captura con buffer; el contenido SI se compila (directivas y `{{ }}` funcionan) |
| `@section('n') ... @stop` | `@stop` = alias exacto de `@endsection` |
| `@section('n') ... @show` | captura E imprime inmediatamente: uso tipico en layouts para definir defaults |
| `@section('n') ... @overwrite` | captura y REEMPLAZA lo previo ignorando `@parent` |
| `@parent` | dentro del bloque de la hija: el contenido del layout se conserva y el nuevo se inserta en el marcador |

### 6.3 @parent paso a paso

```blade
{{-- layouts/app.php --}}
@section('sidebar')
    <a href="/home">Home</a>
@show
<main>@yield('content')</main>

{{-- home/dashboard.php --}}
@extends('layouts.app')
@section('sidebar')
    @parent
    <a href="/reportes">Reportes</a>   {{-- se anexa AL FINAL del menu del layout --}}
@endsection
```

Resultado del sidebar: `<a href="/home">Home</a> <a href="/reportes">Reportes</a>`.

Mecanica: la hija captura ` @parent <a...>` primero (el layout corre despues); cuando el layout ejecuta su `@section('sidebar') ... @show`, su contenido (`<a href="/home">Home</a>`) reemplaza al marcador `@parent` de lo ya capturado.

### 6.4 @yield y condicionales de seccion

```blade
@yield('title', 'Cronos Framework')     {{-- default si nadie la define --}}
@yield('description', $metaDefault)     {{-- el default es una expresion PHP --}}

@hasSection('notificationes')
    @yield('notificationes')
@else
    <p>No hay notificaciones</p>
@endif

@sectionMissing('footer')
    <footer>Footer por defecto</footer>
@endif
```

**Importante:** `@hasSection` considera vacia una seccion cuyo contenido es solo espacios.

### 6.5 Herencia multinivel (jerarquia de layouts)

```
layouts/app.php           (esqueleto raiz: <html>, nav, footer)
    ↑ @extends
layouts/dashboard.php     (agrega sidebar y wrappers de admin)
    ↑ @extends
dashboard/index.php       (pagina concreta)
```

```blade
{{-- layouts/dashboard.php --}}
@extends('layouts.app')

@section('content')
    <div class="flex">
        <aside>@include('partials.sidebar-admin')</aside>
        <section>
            @yield('dashboard-content')   {{-- punto de extension para las paginas --}}
        </section>
    </div>
@show
```

```blade
{{-- dashboard/index.php --}}
@extends('layouts.dashboard')

@section('dashboard-content')
    <h1>Panel</h1>
@endsection
```

**Que hacer y que NO hacer en multinivel:**

```blade
{{-- MAL: el mismo @yield en dos niveles imprime DOS VECES el contenido --}}
{{-- layouts/dashboard.php --}}
@extends('layouts.app')
@yield('content')          {{-- app.php tambien tiene @yield('content') → duplicado --}}

{{-- BIEN: el nivel intermedio CONSUME la seccion del padre y expone otra nueva --}}
@extends('layouts.app')
@section('content')
    <div class="wrapper">
        @yield('dashboard-content')
    </div>
@show
```

```blade
{{-- MAL: @extends dinamico (no existe) --}}
@extends($layoutSegunRol)

{{-- BIEN: constante y con comillas --}}
@extends('layouts.app')
```

```blade
{{-- MAL: HTML antes de @extends quedara ANTES del layout en la salida --}}
<p>esto queda fuera</p>
@extends('layouts.app')

{{-- BIEN: @extends primero, todo el contenido dentro de secciones --}}
@extends('layouts.app')
@section('content') <p>esto va dentro</p> @endsection
```

Reglas finales de herencia:

- `@extends` debe aparecer UNA vez; si aparece varias, **gana la ultima**.
- Debe usar comillas literales: no hay extends dinamico.
- Las secciones pueden anidarse (`@section` dentro de `@section`) con pila LIFO.
- Las secciones definidas y NO consumidas por ningun `@yield` simplemente no se imprimen.

---

## 7. Stacks: @push / @prepend / @stack / @pushOnce

Los stacks resuelven "una vista necesita agregar scripts/estilos al layout, que se renderiza despues".

```blade
{{-- en la hija (o en un include, da igual) --}}
@push('scripts')
    <script src="@asset('assets/js/graficos.js')"></script>
@endpush

{{-- forma corta: el contenido es una expresion PHP --}}
@push('styles', '<link rel="stylesheet" href="tema.css">')

{{-- insertar AL FRENTE del stack (para dependencias: jQuery antes que plugins) --}}
@prepend('scripts')
    <script src="jquery.js"></script>
@endprepend

{{-- en el layout, en el punto exacto de salida --}}
@stack('scripts')
```

Orden y semantica:

| Regla | Detalle |
|---|---|
| Acumulacion | los `@push` se concatenan en ORDEN DE EJECUCION: hija primero, layout despues |
| `@prepend` | inserta al inicio de lo acumulado hasta ese momento |
| Runtime | el contenido se captura con buffers en runtime: puede usar `{{ }}`, variables y condicionales |
| Multiples `@stack` | cada `@stack('n')` imprime TODO lo acumulado en su punto; dos `@stack('n')` imprimen lo mismo dos veces |
| Cierre obligatorio | `@push` cierra con `@endpush`; `@prepend` con `@endprepend` (no son intercambiables) |

```blade
{{-- @pushOnce: agrega una sola vez aunque el parcial se incluya N veces --}}
{{-- partials/widget.php --}}
@pushOnce('scripts')
    <script src="@asset('assets/js/widget.js')"></script>
@endPushOnce
<div class="widget">...</div>

{{-- pagina.php: aunque se incluya 3 veces, widget.js sale UNA vez --}}
@include('partials.widget')
@include('partials.widget')
@include('partials.widget')
```

**Que hacer y que NO hacer:**

```blade
{{-- MAL: @stack dentro de la hija esperando que el layout lo use --}}
@stack('scripts')   {{-- imprime lo acumulado HASTA ese punto de ESE archivo --}}

{{-- BIEN: @stack en el layout (que corre al final y lo ve todo) --}}

{{-- MAL: mezclar closers --}}
@push('scripts') ... @endprepend   {{-- rompe el conteo LIFO --}}

{{-- BIEN: parejas correctas --}}
@push('scripts') ... @endpush
@prepend('scripts') ... @endprepend
```

---

## 8. Includes: @include / @includeIf / @includeWhen / @includeUnless / @each

```blade
@include('partials.tarjeta')

{{-- datos pasados: pisan a las variables del scope; el resto del scope sigue visible --}}
@include('partials.tarjeta', ['titulo' => 'Ofertas', 'compacta' => true])

{{-- nombre dinamico: permitido (a diferencia de @extends) --}}
@include($vistaSegunTipo)

{{-- condicionales --}}
@includeIf('partials.banner')                      {{-- solo si la vista existe --}}
@includeWhen($usuario->premium, 'partials.anuncio')
@includeUnless($usuario->premium, 'partials.upgrade')

{{-- @each: un parcial por elemento --}}
@each('partials.tarea', $tareas, 'tarea')
@each('partials.tarea', $tareas, 'tarea', 'partials.sin-tareas')
@each('partials.tarea', $tareas, 'tarea', 'partials.sin-tareas', ['modo' => 'lista'])
```

Semantica de datos en un include:

| Variable en el parcial | Valor |
|---|---|
| las pasadas en el array | el valor pasado (gana sobre el scope) |
| cualquier otra del scope actual | visible (heredada) |
| `$key` (solo con `@each`) | la clave del elemento actual |
| `$variable` (con `@each`) | el elemento iterado |

El parcial de `@each` con coleccion vacia: renderiza la vista de vacio (recibiendo solo los datos extra del 5to argumento); sin vista de vacio, no imprime nada.

**Que hacer y que NO hacer:**

```blade
{{-- MAL: dependecer de variables del scope sin documentarlas --}}
@include('partials.usuario')   {{-- funciona solo si $usuario existe arriba --}}

{{-- BIEN: pasar explicitamente lo que el parcial necesita --}}
@include('partials.usuario', ['usuario' => $autor])

{{-- MAL: logica de negocio dentro del parcial --}}
@include('partials.total', ['total' => Pedido::sum('total')])  {{-- consultas en vista: NO --}}

{{-- BIEN: el controlador calcula, la vista muestra --}}
return view('pedidos.resumen', ['total' => $total]);
```

Los includes pueden anidarse sin limite (`@include` dentro de `@include` dentro de secciones dentro de componentes).

---

## 9. Loops y la variable $loop

```blade
@foreach($publicaciones as $post)
    <article class="{{ $loop->first ? 'primero' : '' }} {{ $loop->last ? 'ultimo' : '' }}">
        #{{ $loop->iteration }} de {{ $loop->count }} — {{ $post->titulo }}
        (quedan {{ $loop->remaining }}, indice interno {{ $loop->index }})
    </article>
@endforeach
```

| Propiedad | Significado |
|---|---|
| `$loop->index` | indice actual, desde 0 |
| `$loop->iteration` | iteracion actual, desde 1 |
| `$loop->count` | total de elementos (0 si no es countable) |
| `$loop->remaining` | elementos que faltan (`count - iteration`) |
| `$loop->first` / `$loop->last` | bool: primera / ultima iteracion |
| `$loop->parent` | el `$loop` del nivel exterior (null en el primer nivel) |

Anidamiento (soportado sin limite; cada nivel restaura su `$loop` al salir):

```blade
@foreach($categorias as $categoria)
    <h2>{{ $loop->iteration }}. {{ $categoria->nombre }}</h2>
    @foreach($categoria->productos as $producto)
        {{-- parent apunta al loop de categorias --}}
        cat {{ $loop->parent->iteration }} - prod {{ $loop->iteration }}: {{ $producto->nombre }}
    @endforeach
@endforeach
```

Otros loops:

```blade
@for($i = 0; $i < 10; $i++) {{ $i }} @endfor
@while($registros = fetch()) ... @endwhile

@foreach($items as $item)
    @continue($item->oculto)          {{-- saltar este --}}
    @break($loop->iteration >= 10)    {{-- cortar a los 10 --}}
    {{ $item->nombre }}
@endforeach
```

```blade
@forelse($comentarios as $c)
    <p>{{ $c->texto }}</p>
@empty
    <p>Sin comentarios todavia</p>
@endforelse
```

**Que hacer y que NO hacer:**

```blade
{{-- MAL: usar $loop dentro de @for/@while (NO existe ahi; diferencia con Laravel) --}}
@for($i = 0; $i < 5; $i++) {{ $loop->index }} @endfor   {{-- ERROR de variable --}}

{{-- BIEN: usar la variable del propio for --}}
@for($i = 0; $i < 5; $i++) {{ $i }} @endfor

{{-- MAL: expresion con datos crudos sin validar --}}
@foreach($pedidos as $pedido)   {{-- si $pedidos es null → warning --}}
@endforeach

{{-- BIEN: garantiza default desde el controlador o coalesce --}}
@foreach(($pedidos ?? []) as $pedido) @endforeach
{{-- o directamente @forelse, que cubre el caso vacio --}}
```

> Las directivas deben ir separadas del texto siguiente por espacio o salto de linea cuando ese texto empieza con letra: `@endif aqui` compila; `OK@endif` NO compila (igual que Laravel).

---

## 10. Condicionales y utilidades

```blade
@if ($edad >= 18 && count($permisos) > 0)   {{-- expresiones PHP completas: parens anidados y comillas OK --}}
    ...
@elseif ($edad >= 16)
    ...
@else
    ...
@endif

@unless($usuario->activo) Inactivo @endunless   {{-- if (!($expr)) --}}
@isset($resultado) Existe: {{ $resultado }} @endisset
@empty($lista) Vacia @endempty

@auth     Bienvenido {{ session()->user()->nombre ?? '' }} @endauth
@guest    <a href="{{ route('login.index') }}">Ingresar</a> @endguest

@php $total = $precio * $cantidad; @endphp     {{-- bloque --}}
@php($iva = $total * 0.16)                     {{-- inline --}}

@json($config)                 {{-- json_encode con flags seguros para <script> --}}
@unset($temporal)

{{-- comentario Blade: NO llega al HTML (a diferencia de <!-- -->) --}}
{{-- multilinea
     tambien funciona --}}

@verbatim
    Este {{ $codigo }} y @if se imprimen LITERALES (ideal para ejemplos o JS con sintaxis parecida)
@endverbatim

@@if($x)      {{-- imprime literalmente @if($x) --}}

@dump($datos)   {{-- var_dump y sigue --}}
@dd($user)      {{-- var_dump y detiene --}}
```

`@auth` compila a `if (session()->hasUser())`: usa la sesion sin clave del framework (`session()->attempt($user)` para autenticar).

---

## 11. Formularios y sesion

Flujo completo de un formulario con validacion:

```blade
{{-- resources/views/contacto/index.php --}}
@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('contacto.store') }}">
    @csrf                                {{-- <input type="hidden" name="_token" value="..."> --}}

    <input name="email" type="email" value="{{ old('email') }}">
    @error('email')
        <p class="text-red-600">{{ $message }}</p>   {{-- $message la inyecta @error --}}
    @enderror

    <textarea name="mensaje">@error('mensaje'){{ old('mensaje') }}@enderror</textarea>

    <select name="tema">
        <option value="venta" @selected($temaElegido === 'venta')>Venta</option>
        <option value="soporte" @selected($temaElegido === 'soporte')>Soporte</option>
    </select>

    <input type="checkbox" name="newsletter" @checked(old('newsletter') === '1')>
    <button type="submit" @disabled($enviando)>Enviar</button>
</form>
@endsection
```

```php
// controlador
$valid = $this->validate($request->all(), [
    'email' => 'required|email',
    'mensaje' => 'required|min:3',
]);

if ($valid !== true) {
    return back()->withErrors($request->all(), $valid);   // deja old() y error() en sesion
}

// ... guardar; redirect con flash
```

| Directiva/Helper | Compila a / hace |
|---|---|
| `@csrf` | `<input type="hidden" name="_token" value="e(csrf_token())">` — token aleatorio persistido en sesion |
| `@method('PUT')` | `<input type="hidden" name="_method" value="PUT">` (solo PUT/PATCH/DELETE) |
| `@error('campo') ... @enderror` | `if (($message = session()->error('campo')) !== null):` — dentro del bloque, `$message` tiene el error |
| `old('campo')` | valor enviado previamente (sesion) |
| `error('campo')` / `ifError('campo')` | el mensaje / bool de existencia |
| `@checked($bool)` | imprime `checked` si verdadero |
| `@selected($bool)` | imprime `selected` |
| `@disabled($bool)` / `@readonly($bool)` / `@required($bool)` | imprime el atributo |

**Que hacer y que NO hacer:**

```blade
{{-- MAL: formulario POST sin @csrf --}}
<form method="POST"> ... </form>

{{-- BIEN: siempre @csrf en formularios POST --}}

{{-- MAL: repoblar sin escape con el input del usuario --}}
<input value="{!! old('email') !!}">

{{-- BIEN: old() con escape --}}
<input value="{{ old('email') }}">
```

---

## 12. Componentes anonimos x-*

Un componente es un archivo en `resources/views/components/` con un CONTRATO de props y slots. Se usa como etiqueta HTML sin registrar nada.

### 12.1 Anatomia de un componente

```blade
{{-- resources/views/components/tarjeta.php --}}
@props(['titulo' => null, 'destacada' => false])

<article {{ $attributes->merge(['class' => 'tarjeta'.($destacada ? ' tarjeta-destacada' : '')]) }}>
    @if ($titulo)
        <h3 class="tarjeta-titulo">{{ $titulo }}</h3>
    @endif
    <div class="tarjeta-cuerpo">
        {!! $slot !!}
    </div>
</article>
```

Uso:

```blade
<x-tarjeta titulo="Ofertas" destacada id="ofertas-hoy" class="mx-auto">
    <p>Contenido del cuerpo...</p>
</x-tarjeta>

{{-- salida aproximada: --}}
{{-- <article class="tarjeta tarjeta-destacada mx-auto" id="ofertas-hoy"> --}}
```

> **Regla de los slots:** el contenido de un slot llega al componente como HTML YA RENDERIZADO del padre (el padre decidio ahi si escapo sus datos con `{{ }}`). Para imprimirlo usa `{!! $slot !!}`. `{{ $slot }}` solo es correcto si el contenido es texto plano: con markup lo escapa y el usuario veria las etiquetas.

### 12.2 Contrato completo

| Concepto | Detalle |
|---|---|
| `@props([...])` | primera instruccion del archivo, EN MODO HTML (nunca dentro de `<?php`). Array de `nombre` o `nombre => default`. Lo declarado se extrae como variable (con default si falta); lo NO declarado va a `$attributes` |
| `{{ $slot }}` / `{!! $slot !!}` | contenido del tag (lo que no es un slot nombrado). Llega como HTML renderizado del padre: para markup usar `{!! !!}`; `{{ }}` solo si es texto plano |
| `<x-slot:nombre> ... </x-slot:nombre>` | slot nombrado: capturado en el scope del padre; tambien disponible como variable `$nombre` en el componente |
| `:prop="$expr"` | binding: la expresion se evalua EN EL SCOPE DEL PADRE y llega SIN escapar |
| `attr="texto"` | literal de texto; si el valor es EXACTAMENTE `{{ expr }}` se compila a `e(expr)` (llega escapado) |
| `attr` sin valor | booleano `true` |
| `{{ $attributes }}` / `{!! $attributes !!}` | renderiza los atributos NO declarados como `key="value"` (escape seguro via `toHtml()`) |
| `$attributes->merge(['class' => 'base'])` | combina defaults con lo recibido: `class`/`style` se CONCATENAN con espacio; el resto: gana el del uso |
| `$attributes->get('k')` / `->has('k')` | acceso puntual |
| `$attributes->only([...])` / `->except([...])` / `->all()` | filtros (retornan bolsa) |
| `$attributes->class(['a', 'b' => $cond])` | clases condicionales unidas a la clase actual |
| `<x-sub.carpeta ...>` | `resources/views/components/sub/carpeta.php` |
| `<x-dynamic-component :component="$nombre" .../>` | componente resuelto en runtime; nombre corto (`alerta`) o con ruta (`components/alerta`) |

### 12.3 Slots nombrados

```blade
{{-- componente: components/modal.php --}}
@props(['id' => 'modal'])
<div class="modal" id="{{ $id }}">
    <header>{!! $encabezado !!}</header>          {{-- slot nombrado como variable (HTML del padre: crudo) --}}
    <div class="cuerpo">{{ $slot }}</div>       {{-- contenido por defecto --}}
    <footer>{!! $__slots['pie'] !!}</footer> {{-- tambien via $__slots (compatibilidad) --}}
</div>

{{-- uso --}}
<x-modal id="confirmar">
    <x-slot:encabezado>
        <h2>Confirmar accion</h2>            {{-- se evalua con las variables de AQUI (el padre) --}}
    </x-slot:encabezado>

    Seguro que deseas eliminar <strong>{{ $registro->nombre }}</strong>?

    <x-slot:pie>
        <x-button variant="danger">Eliminar</x-button>
    </x-slot:pie>
</x-modal>
```

### 12.4 Bindings y tipos de atributos

```blade
:contador="$total"                          {{-- evaluado en el padre: llega el valor real --}}
:config="['claves' => $claves, 'memo' => true]"   {{-- arrays y expresiones completas --}}
titulo="Texto plano"                        {{-- string literal --}}
titulo="{{ $variable }}"                    {{-- igual que literal pero compilado a e($variable) --}}
disabled                                    {{-- true booleano --}}
```

**Seguridad de bindings:** `:html="$contenido"` llega SIN escapar (el padre confiesa que es HTML confiable). Con `html="$contenido"` llega el STRING crudo sin escapar tambien (es un literal). Con `html="{{ $contenido }}"` llega ESCAPADO. Elige conscientemente.

### 12.5 Que hacer y que NO hacer en componentes

```blade
{{-- MAL: @props dentro de un bloque PHP abierto (rompe el compilado) --}}
<?php
@props(['type' => 'info'])
$estilos = [...];
?>

{{-- BIEN: @props primero en modo HTML; PHP despues en su propio bloque --}}
@props(['type' => 'info'])
<?php $estilos = [...]; ?>

{{-- MAL: prop con guion (no puede extraerse a variable PHP) --}}
@props(['data-id'])
{{ $dataId }}              {{-- no existe --}}

{{-- BIEN: los guiones quedan en $attributes y se re-envian al HTML --}}
<div {{ $attributes }}>    {{-- data-id="5" sale en el HTML --}}

{{-- MAL: consultar variables del padre dentro del archivo del componente --}}
<span>{{ $usuarioLogueado }}</span>        {{-- no llega: el componente tiene scope propio --}}

{{-- BIEN: pasarla como prop, o usarla en el CONTENIDO del slot (ese si corre en el padre) --}}
<x-saludo :usuario="$usuarioLogueado">Hola {{ $usuarioLogueado->nombre }}</x-saludo>

{{-- MAL: imprimir un slot con markup usando {{ }} (lo escapa y el usuario ve las etiquetas) --}}
<div>{{ $slot }}</div>                {{-- con <p>hola</p> dentro: sale &lt;p&gt;hola&lt;/p&gt; --}}

{{-- BIEN: los slots llegan como HTML ya renderizado del padre (el padre escapo sus datos) --}}
<div>{!! $slot !!}</div>

{{-- MAL: componente con @extends --}}
@extends('layouts.app')    {{-- un componente es un fragmento, nunca una pagina --}}

{{-- MAL: cerrar mal los slots --}}
<x-slot:pie>...</x-slot:cabeza>

{{-- BIEN: nombres pareados --}}

{{-- MAL: anidar el mismo componente sin cierre balanceado --}}
<x-lista><x-lista>items</x-lista>    {{-- falta el cierre externo --}}
```

Componentes incluidos en el proyecto: `x-alert`, `x-badge`, `x-button`, `x-card`, `x-input`, `x-textarea` (cada archivo documenta sus props en el encabezado).

---

## 13. Scope: que variables ve cada contexto

La tabla mas importante para IAs. Define que puede referenciar cada archivo sin recibir `undefined variable`:

| Contexto | Variables visibles |
|---|---|
| Vista principal (pagina) | los `$params` del controlador |
| Layout (`layouts/app.php`) | los mismos `$params` del controlador (mismo render) + secciones |
| `@include` parcial | todo el scope actual + los datos pasados (pasados ganan) |
| Contenido de un `<x-componente>` (lo que escribes entre el abrir y cerrar) | el scope DONDE SE ESCRIBE el tag (el padre) |
| Slots nombrados `<x-slot:x>` | igual: scope del padre |
| Archivo del componente (components/x.php) | SOLO: props declaradas + slots (como variables) + `$slot` + `$__slots` + `$attributes`. JAMAS el scope del padre |
| Parcial de `@each` | `$variable` (elemento) + `$key` + datos extra del 5to argumento |
| Bloque `@once` | el scope donde se escribio |

```blade
{{-- pagina.php (controlador paso $pedidos y $titulo) --}}
@extends('layouts.app')

{{-- BIEN: el layout y esta vista ven $titulo --}}
@section('content')
    <h1>{{ $titulo }}</h1>

    {{-- BIEN: el slot corre en el scope de ESTA pagina: ve $pedidos --}}
    <x-lista-vacia :vacio="count($pedidos) === 0">
        Hay {{ count($pedidos) }} pedidos          {{-- OK: slot = scope del padre --}}
    </x-lista-vacia>

    {{-- MAL: esperar que components/lista-vacia.php vea $pedidos --}}
@endsection
```

---

## 14. URLs, rutas y assets

```blade
{{-- rutas nombradas (siempre escape normal) --}}
<a href="{{ route('home.index') }}">Inicio</a>
<a href="{{ route('blog.show', ['id' => $post->id]) }}">Ver</a>

{{-- assets con cache-busting automatico --}}
<link href="@asset('assets/css/home.css')" rel="stylesheet">
<script src="@asset('assets/js/app.js')"></script>
```

`@asset('ruta')` genera `http://host/ruta?v={filemtime}` si el archivo existe en `public/` (si no existe, sin version). El `?v=` cambia al modificar el archivo: cache del navegador invalidado automaticamente.

**MAL:** rutas absolutas escritas a mano (`/assets/css/x.css`) o la constante `base_url . '/...'`. **BIEN:** `@asset()` y `{{ route() }}`.

---

## 15. Atributos condicionales y @class / @style

```blade
{{-- clases condicionales: valor sin clave = siempre; con clave = solo si la condicion es verdadera --}}
<div class="@class(['tab', 'tab-activa' => $activa, 'tab-deshabilitada' => !$habilitada])"></div>

{{-- estilos condicionales --}}
<div style="@style(['display:flex', 'color:red' => $error])"></div>

{{-- dentro de componentes, combinado con la bolsa --}}
<div class="{{ $attributes->class(['tarjeta', 'oscura' => $temaOscuro]) }}"></div>

{{-- atributos booleanos de formulario --}}
<input type="checkbox" @checked($suscriptor)>
<option value="a" @selected($elegida === 'a')>
<button @disabled($sinStock)>Comprar</button>
<input @readonly($soloVista) @required>
```

---

## 16. Directivas personalizadas

```php
// registro (por ejemplo en un ServiceProvider)
use Cronos\View\Compiler\BladeCompiler;

BladeCompiler::directive('datetime', function ($expression) {
    // recibe la expresion cruda entre parentesis (string) o null si no hay parentesis
    // retorna el CODIGO PHP de reemplazo (o null para dejar el texto intacto)
    return "<?php echo date('d/m/Y H:i', strtotime({$expression})); ?>";
});

BladeCompiler::directive('anio', function () {
    return '<?php echo date("Y"); ?>';
});
```

```blade
<p>Publicado: @datetime($post->creado_en)</p>
<footer>&copy; @anio Cronos</footer>
```

Reglas: el nombre respeta fronteras de palabra (`@datetimeX` NO dispara `@datetime`); el registro es **estatico y global** (persiste entre requests y entre tests: en pruebas, limpiar con `BladeCompiler::getCustomDirectives()` manualmente si hace falta); si cambias una directiva, borra `storage/cache/views` (ver seccion 17).

---

## 17. Cache: mecanica e invalidacion

- Compilados en `storage/cache/views/{md5-ruta}.php`.
- Cada compilado lleva una cabecera `/*deps:[...]*/` con las rutas ABSOLUTAS de sus dependencias: layout (`@extends`), includes (`@include*`, `@each`) y componentes (`<x-*>`).
- En cada render se comparan los `filemtime`: si la vista **o cualquiera de sus dependencias** es mas nueva que el compilado, se recompila. Nombres dinamicos (`@include($var)`) no pueden rastrearse: sus cambios requieren tocar la vista que los usa.

```bash
# limpiar manualmente (solo necesario si tocas el compilador o directivas personalizadas)
Remove-Item storage\cache\views\*.php   # Windows
rm storage/cache/views/*.php            # Linux/Mac
```

**MAL:** asumir que hay que borrar cache tras editar una vista (la invalidacion es automatica). **BIEN:** borrar solo al modificar `BladeCompiler`, directivas personalizadas, o ante un cache corrupto.

---

## 18. Seguridad (XSS, inclusion de archivos, verbatim)

| Riesgo | Proteccion del motor | Tu responsabilidad |
|---|---|---|
| XSS por salida | `{{ }}` escapa con `e()`; arrays van como JSON escapado | Nunca `{!! !!}` con input de usuario; bindings `:attr` llegan crudos a proposito |
| Inclusion de archivos | El include compilado corre en closure con variables internas `__*` y los datos de la vista se filtran: `view('x', ['__viewPath' => '...'])` NO puede pisar el archivo a incluir | No construir nombres de vista con input sin whitelist: `view('paginas.' . $slug)` solo si `$slug` pasa por `in_array()` |
| CSRF | `@csrf` imprime token aleatorio de 40 hex persistido en sesion | Verificar el token en el middleware/accion que procesa el POST |
| Filtracion de codigo | `{{-- --}}` se elimina en compilacion; `@verbatim` protege bloques | Comentar datos sensibles con `{{-- --}}`, nunca con `<!-- -->` HTML |

---

## 19. Referencia completa de directivas

| Directiva | Cierra con | Notas |
|---|---|---|
| `{{ $expr }}` | — | escape con `e()`; arrays → JSON escapado; objetos con `toHtml()` → crudo |
| `{!! $expr !!}` | — | sin escape |
| `{{-- --}}` | — | comentario eliminado en compilacion |
| `@@directiva` | — | imprime la directiva literal |
| `@extends('v')` | — | una por vista; gana la ultima; SOLO literal con comillas; compila al FINAL de la vista |
| `@section('n', expr)` | — | corta, expresion cruda |
| `@section('n')` | `@endsection` / `@stop` | bloque compilado |
| `@section('n')` | `@show` | captura e imprime (layouts) |
| `@section('n')` | `@overwrite` | reemplaza ignorando @parent |
| `@parent` | — | solo dentro de un bloque de seccion |
| `@yield('n', 'default')` | — | default es expresion cruda |
| `@hasSection('n')` | `@endif` | seccion existe y no vacia |
| `@sectionMissing('n')` | `@endif` | inverso |
| `@push('s')` | `@endpush` | acumula al final |
| `@push('s', expr)` | — | forma corta |
| `@prepend('s')` | `@endprepend` | acumula al frente |
| `@stack('s')` | — | imprime acumulado (posicion del layout) |
| `@pushOnce('s')` | `@endPushOnce` | una vez por render (clave vista:stack) |
| `@include('v', [...])` | — | runtime, datos pasados pisan scope |
| `@includeIf('v')` | — | solo si la vista existe |
| `@includeWhen($cond, 'v')` | — | si $cond |
| `@includeUnless($cond, 'v')` | — | si !$cond |
| `@each('v', $items, 'var')` | — | + `'vista-vacia'` y `datos` opcionales; expone `$key` |
| `@foreach / @endforeach` | — | con `$loop` completo; anidable |
| `@forelse / @empty / @endforelse` | — | foreach con rama vacia |
| `@for / @endfor` | — | sin `$loop` |
| `@while / @endwhile` | — | sin `$loop` |
| `@break` / `@continue` | — | con condicion opcional `@break($cond)` |
| `@switch / @case / @default / @endswitch` | — | `@break` manual en cada case |
| `@if / @elseif / @else / @endif` | — | expresiones completas |
| `@unless / @endunless` | — | negado |
| `@isset / @endisset`, `@empty / @endempty` | — | `isset()` / `empty()` de PHP |
| `@auth / @endauth`, `@guest / @endguest` | — | `session()->hasUser()` |
| `@error('campo')` | `@enderror` | define `$message` con el error de sesion |
| `@php ... @endphp`, `@php(...)` | — | PHP crudo |
| `@json($v)` | — | json_encode con flags para `<script>` |
| `@unset($v)` | — | unset |
| `@verbatim / @endverbatim` | — | desactiva la compilacion dentro |
| `@csrf`, `@method('PUT')` | — | hidden inputs |
| `@asset('ruta')` | — | URL con `?v=filemtime` |
| `@dump($v)` / `@dd($v)` | — | debug; dd detiene |
| `@class([...])`, `@style([...])` | — | clases/estilos condicionales |
| `@checked/@selected/@disabled/@readonly/@required` | — | atributo solo si la condicion es verdadera |
| `@props([...])` | — | primera linea del componente |
| `BladeCompiler::directive()` | — | registra directivas propias |

---

## 20. Lo que el motor NO soporta

| Feature de Laravel 13 | Estado en Cronos | Alternativa |
|---|---|---|
| `@extends($variable)` dinamico | NO | `@include` condicionales, o vistas separadas por caso |
| Componentes con clase PHP, `make:component`, `@aware` | NO | componentes anonimos con `@props` |
| `@can`, `@cannot`, `@canany` (policies) | NO | `@auth` + condicionales con datos que decide el controlador |
| `@lang`, `@choice` (i18n) | NO | archivos PHP de textos propios |
| `@inject` | NO | pasar servicios desde el controlador |
| `$loop` en `@for` / `@while` | NO | contar con la variable del loop |
| `@includeFirst([...])` / `@each` con array de vistas | NO | `@includeIf` encadenados |
| `@yield` con seccion dinamicamente nombrada | NO | condicionales `@hasSection` |
| Modificadores de `@section` tipo `@sectionMissing` con default complejo | usa `@sectionMissing` + `@else` | — |
| Cache invalidable por includes dinamicos (`@include($var)`) | NO rastreable | tocar la vista contenedora para forzar recompilacion |
| `@parent` en stacks o components | NO | solo en secciones |
| Traits/scopes de Blade (`precompiling`, `renderComponent`) | NO | — |

---

## 21. Errores y excepciones

| Excepcion | Cuando ocurre | Ejemplo de mensaje |
|---|---|---|
| `ViewNotFoundException` | la vista PRINCIPAL (o sub-vista via makeView) no existe | `La vista [blog.show] no existe. Ruta buscada: .../resources/views/blog/show.php` |
| `ViewCompileException` | error de compilacion de directivas O error PHP en runtime de una vista; siempre incluye el nombre de la vista y envuelve el error original como `previous` | `Error compilando la vista [home.index]: La directiva @foreach requiere una expresion entre parentesis` |
| `LogicException` | interno: entorno de render sin motor (no deberia ocurrir en la app) | `makeView() debe ser implementado por el motor de vistas` |

Captura en un controlador:

```php
use Cronos\View\Exceptions\ViewNotFoundException;

try {
    return view('paginas.' . $slug);
} catch (ViewNotFoundException $e) {
    return view('errors.404')->setStatusCode(404);
}
```

---

## 22. Guia de decision: layout, include o componente

| Pregunta | Si la respuesta es SI |
|---|---|
| Es el esqueleto HTML de un tipo de pagina? | **layout** (`layouts/`) |
| Es un fragmento pegado al proyecto que solo usa UN layout? | **partial** (`@include`) |
| Necesita recibir datos con contrato, reutilizarse entre modulos y encapsular markup? | **componente** (`<x-*>`) |
| Es un item repetido de una coleccion? | parcial + **`@each`**, o componente dentro de `@foreach` |
| Necesita inyectar scripts al layout? | `@push`/`@stack` (no importa si es partial o componente) |
| Su contenido depende de variables del lugar de uso? | **componente** (slots corren en el scope del padre) vs **partial** (hereda TODO el scope, mas acoplado) |

Regla practica: **partial = acoplamiento al proyecto; componente = contrato reutilizable.** Si un partial empieza a documentar "requiere $x y $y", conviertelo en componente.

---

## 23. Recetas completas

### 23.1 Pagina de listado con componentes y estado vacio

```php
// controlador
$publicaciones = Publicacion::query()->where('usuario_id', $usuarioId)->orderBy('creado_en', 'desc')->get();

return view('blog.index', ['publicaciones' => $publicaciones]);
```

```blade
{{-- resources/views/blog/index.php --}}
@extends('layouts.app')

@section('title', 'Mis publicaciones')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Mis publicaciones</h1>

    @forelse($publicaciones as $post)
        <x-card class="mb-4">
            <x-slot:header>
                <h2 class="font-semibold">{{ $loop->iteration }}. {{ $post->titulo }}</h2>
            </x-slot:header>

            <p>{{ mb_substr($post->contenido, 0, 120) }}...</p>

            <x-button href="{{ route('blog.show', ['id' => $post->id]) }}" size="sm">Leer mas</x-button>
        </x-card>
    @empty
        <x-alert type="info">Todavia no tienes publicaciones.</x-alert>
    @endforelse
</div>
@endsection

@push('scripts')
    <script src="@asset('assets/js/blog.index.js')"></script>
@endpush
```

### 23.2 Formulario con validacion y re-poblado

```blade
@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('blog.store') }}" class="max-w-md mx-auto p-4 space-y-4">
    @csrf

    <x-input name="titulo" label="Titulo" required value="{{ old('titulo') }}"/>
    @error('titulo')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

    <x-textarea name="contenido" label="Contenido" rows="6" required>{{ old('contenido') }}</x-textarea>
    @error('contenido')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

    <x-button type="submit" :disabled="$huboError ?? false">Publicar</x-button>
</form>
@endsection
```

### 23.3 Componente compuesto (card + estados)

```blade
{{-- resources/views/components/estado.php --}}
@props(['tipo' => 'info', 'mensaje'])

@php($iconos = ['ok' => 'bi-check-circle', 'error' => 'bi-x-circle', 'info' => 'bi-info-circle'])

<div {{ $attributes->merge(['class' => 'estado estado-' . $tipo]) }}>
    <i class="bi {{ $iconos[$tipo] ?? $iconos['info'] }}"></i>
    <span>{{ $mensaje }}</span>
    {{ $slot }}   {{-- acciones opcionales --}}
</div>

{{-- uso --}}
<x-estado tipo="error" mensaje="No se pudo guardar" class="mb-2">
    <x-button variant="secondary" size="sm" href="{{ route('blog.edit', ['id' => $id]) }}">Reintentar</x-button>
</x-estado>
```

---

## 24. Diferencias con Blade de Laravel 13

| Laravel 13 | Cronos |
|---|---|
| Sintaxis `@directiva`, `{{ }}`, `<x-*>`, slots, `$attributes` | Igual |
| Componentes con clase PHP (`make:component`), `@aware` | Solo anonimos (archivo) |
| `$loop` tambien en `@for`/`@while` | `$loop` solo en `@foreach`/`@forelse` |
| `@can`/`@canany` (policies), `@lang`/`@choice` (i18n), `@inject` | No existen (ver seccion 20) |
| Cache invalida por mtime del propio archivo | Invalida por vista **y dependencias** (layout/includes/componentes) |
| `@each` acepta array de vistas candidatas | Una sola vista |
| `@extends` dinamico con view finder | Solo literal con comillas |
| Directivas pegadas a letras (`OK@endif`) | No compilan (igual); separar con espacio |
| `e()` con `HtmlString` | `e()` acepta cualquier objeto con `toHtml()` |
| `@section` corto escapa | Corto imprime CRUDO (es expresion); escape manual con `e()` si hace falta |
| Fallthrough automatico seguro en `@switch` | `@break` manual en cada `@case` |

---

## 25. Errores comunes y diagnostico

| Sintoma | Causa probable | Solucion |
|---|---|---|
| `Too few arguments to function e()` | un `{{ }}` vacio en alguna vista | buscar `{{  }}` (o `{{ }}`) en la vista indicada por el compilado |
| `syntax error, unexpected token "` en un compilado | `@props` dentro de un bloque `<?php` abierto | mover `@props` a la primera linea en modo HTML |
| Una directiva aparece LITERAL en el HTML | pegada a una letra (`OK@endif`), mal escrita, o dentro de `@verbatim` | separar con espacio; revisar el nombre contra la seccion 19 |
| `ParseError: unexpected end of file` en el compilado | bloque sin cerrar: `@if` sin `@endif`, `@foreach` sin `@endforeach`, `@push` sin `@endpush` | emparejar aperturas y cierres |
| El contenido se imprime DOS veces en herencia multinivel | el mismo `@yield('x')` en dos layouts de la cadena | el intermedio debe consumir con `@section ... @show` y exponer OTRO nombre |
| `Undefined variable` dentro de un componente | el archivo del componente no ve el scope del padre | pasar la variable como prop (`:x="$var"`) o usarla en el slot |
| `Undefined variable $loop` | `$loop` usado en `@for`/`@while` | usar la variable del loop o migrar a `@foreach` |
| `La vista [x] no existe` | nombre/carpeta incorrecta o sin `.php` en disco | verificar ruta real y notacion de punto |
| Cambios en la vista no se reflejan | cache con mtime igual (ediciones rapidas dentro del mismo segundo) | tocar el archivo o borrar `storage/cache/views` |
| Slot vacio | el contenido quedo fuera del slot o el slot no se cierra bien | verificar `<x-slot:nombre> ... </x-slot:nombre>` pareado |
| `500` al renderizar tras editar un componente | error de sintaxis del componente compilado | ver el mensaje de `ViewCompileException`: incluye la vista exacta |
| `Verbo HTTP no soportado por @method` | `@method('GET')` o minusculas raras | solo PUT/PATCH/DELETE (se normalizan mayusculas) |

---

## 26. Cheat sheet para IAs

Verificar SIEMPRE contra este bloque antes de generar una vista:

```blade
{{-- print --}}
{{ $x }}            escape XSS-safe (null→'' bool→'1'/'', array→JSON, toHtml→crudo)
{!! $html !!}       crudo, solo HTML propio

{{-- herencia --}}
@extends('layouts.app')                       primera linea, literal, UNA vez
@section('t', 'texto')                        corto (expresion cruda)
@section('c') ... @endsection | @stop         bloque compilado
@section('s') ... @show                       define default en layout
@section('s') ... @overwrite                  ignora @parent
@parent                                       anexa al contenido del layout
@yield('t', 'default')                        imprime seccion o default
@hasSection('t') ... @endif / @sectionMissing

{{-- stacks --}}
@push('scripts') ... @endpush     acumula
@push('s', 'html')                corto
@prepend('scripts') ... @endprepend    al frente
@stack('scripts')                 imprime (en el layout)
@pushOnce('scripts') ... @endPushOnce    una sola vez por render

{{-- includes --}}
@include('p.v', ['k' => 'v'])     pasados pisan; scope hereda; anidables
@include($dinamica)                OK
@includeIf / @includeWhen($c,) / @includeUnless($c,)
@each('p.item', $items, 'item', 'p.vacio', ['extra' => 1])    expone $item y $key

{{-- loops --}}
@foreach ($xs as $x) ... @endforeach      con $loop (index/iteration/count/remaining/first/last/parent)
@forelse ... @empty ... @endforelse
@for / @while: SIN $loop
@break($cond) / @continue($cond)

{{-- condicionales --}}
@if/@elseif/@else/@endif  @unless/@endunless  @isset/@endisset  @empty/@endempty
@auth/@endauth  @guest/@endguest

{{-- utilidades --}}
@php(...); @php ... @endphp; @json($v); @unset($v)
@verbatim ... @endverbatim; @@directiva; {{-- comentario --}}
@dump($v); @dd($v)

{{-- formularios --}}
@csrf; @method('PUT'|'PATCH'|'DELETE')
@error('campo') {{ $message }} @enderror
old()/error()/ifError() helpers de sesion
@checked/@selected/@disabled/@readonly/@required ($bool)
@class([...]) / @style([...]) condicionales

{{-- componentes --}}
<x-nombre prop="literal" :prop="$expr" bool />     components/{nombre}.php
<x-sub.carpeta />                                  components/sub/carpeta.php
<x-dynamic-component :component="$n" />
@props(['a', 'b' => 'default'])                    PRIMERA linea, modo HTML
{{ $slot }} / <x-slot:n>...</x-slot:n>             slot corre en scope del PADRE
{!! $slot !!} para markup (llega como HTML del padre); {{ $slot }} solo texto plano
{{ $attributes }} / $attributes->merge(['class' => 'base'])
                                                   archivo del componente: SOLO props+slots

{{-- urls y assets --}}
{{ route('nombre', ['id' => 1]) }}
@asset('assets/css/x.css')                          con ?v=filemtime

{{-- seguridad --}}
nunca {!! !!} con input de usuario
nombres de vista con input: whitelist obligatoria
```

---

## 27. Mapa de tests

Cada afirmacion de esta guia esta respaldada por la suite:

| Archivo de test | Cubre |
|---|---|
| `tests/Unit/BladeCompilerTest.php` | escape, raw echo, comentarios, verbatim, @@, @php, @json, @unset, condicionales (incl. parentesis/comillas), loops con `$loop`, anidamiento, forelse, for/while, break/continue, switch |
| `tests/Unit/BladeSectionsTest.php` | @extends, @yield con default, seccion corto/bloque, @parent, @show, @overwrite, @hasSection/@sectionMissing, multinivel, sin leak entre renders, secciones anidadas |
| `tests/Unit/BladeStacksTest.php` | push/prepend/stack, orden, push en loops, layout+hija, LIFO anidado, sin leak |
| `tests/Unit/BladeIncludesTest.php` | include basico/con datos/scope/dinamico/anidado, includeIf/includeWhen/includeUnless, @each (clave, vista vacia), include desde layout |
| `tests/Unit/BladeComponentsTest.php` | props, bindings, booleanos, slots default/nombrados, `__slots` compat, @props defaults, $attributes (merge/only/except), anidados, mismo nombre anidado, dynamic, notacion punto, @class/@checked..., tag sin cierre |
| `tests/Unit/BladeEngineTest.php` | render, cache hit e invalidacion (vista/layout/include), scope aislado (LFI), errores con contexto, @once, @pushOnce, @csrf/@method/@error con sesion |
| `tests/Unit/ViewHelpersTest.php` | helpers `e()`, `csrf_token()`, `asset()` |
| `tests/Integration/ViewsIntegrationTest.php` | estructura real de `resources/views` y convenciones del proyecto |

---

> **Anterior**: [05 - Validaciones](05-validaciones.md)
> **Siguiente**: [07 - Sesiones](07-sesiones.md)
