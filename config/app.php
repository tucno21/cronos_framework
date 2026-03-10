<?php

return [
    'name' => env('APP_NAME', 'Cronos'),
    'url' => env('APP_URL', 'http://cronos_framework.test'),
    'app_format' => env('APP_FORMAT', 'web'),

    /**
     * Middlewares Globales
     * 
     * Estos middlewares se ejecutan en TODAS las peticiones HTTP,
     * independientemente de la ruta específica.
     * 
     * El orden de ejecución es el orden del array (de arriba a abajo).
     * 
     * Middlewares recomendados:
     * - \App\Middlewares\LogRequestMiddleware::class - Registrar todas las peticiones
     * - \App\Middlewares\CorsMiddleware::class - Manejar CORS para APIs
     * - \App\Middlewares\ThrottleMiddleware::class - Limitar tasa de peticiones
     */
    'global_middlewares' => [
        // Ejemplos de uso (descomentar según necesidad):

        // \App\Middlewares\LogRequestMiddleware::class,

        // Para APIs, descomentar CORS:
        // \App\Middlewares\CorsMiddleware::class,

        // Para limitar peticiones a toda la aplicación:
        // new \App\Middlewares\ThrottleMiddleware(60, 1, 'block'),
    ],
];
