<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Error 404 - Cronos Framework</title>
    <!-- TailwindCSS v4 compilado para páginas de error -->
    <link href="<?= base_url . '/assets/css/error.css' ?>" rel="stylesheet">
</head>

<body class="error-page">
    <div class="error-code">404</div>
    <div class="error-message">No se encontró la página que busca.</div>
    <a href="<?= base_url('/'); ?>" class="btn btn-primary">Retornar al Inicio</a>
</body>

</html>