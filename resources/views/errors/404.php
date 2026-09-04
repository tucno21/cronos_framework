<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Cronos Framework</title>

    <!-- TailwindCSS v4 compilado para paginas de error -->
    <link href="@asset('assets/css/error.css')" rel="stylesheet">
</head>
<body class="error-page">
    <div class="error-code">404</div>
    <div class="error-message">No se encontro la pagina que busca.</div>
    <a href="{{ route('home.index') }}" class="btn btn-primary">Retornar al Inicio</a>
</body>
</html>
