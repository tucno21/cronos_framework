<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cronos Framework')</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- TailwindCSS v4 compilado para el grupo home/login/register --}}
    <link href="@asset('assets/css/home.css')" rel="stylesheet">

    {{-- Estilos adicionales inyectados por las vistas hijas --}}
    @stack('styles')
</head>
<body class="font-sans">
    @include('partials.nav')

    <main>
        @yield('content')
    </main>

    <script src="@asset('assets/js/home.js')"></script>

    {{-- Scripts adicionales inyectados por las vistas hijas --}}
    @stack('scripts')
</body>
</html>
