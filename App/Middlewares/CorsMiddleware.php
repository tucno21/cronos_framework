<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;
use Cronos\Config\Config;

/**
 * CorsMiddleware - Maneja los encabezados CORS (Cross-Origin Resource Sharing)
 * 
 * Este middleware permite o deniega el acceso a recursos desde diferentes dominios.
 * Lee la configuración desde config/cors.php para determinar qué dominios
 * y métodos están permitidos.
 * 
 * Configuración en config/cors.php:
 * 'allowed_origins' => ['*'] o ['http://localhost:3000', 'https://example.com']
 * 'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
 * 'allowed_headers' => ['Content-Type', 'Authorization']
 * 'allow_credentials' => true/false
 * 'max_age' => 86400 (24 horas en segundos)
 */
class CorsMiddleware implements Middleware
{
    /**
     * Encabezados CORS por defecto
     */
    protected array $defaultHeaders = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age' => '86400',
    ];

    /**
     * Maneja la petición y agrega encabezados CORS
     * 
     * @param Request $request La petición HTTP
     * @param Closure $next El siguiente middleware en la cadena
     * @return Response La respuesta con encabezados CORS
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cargar configuración de CORS
        $config = Config::get('cors', []);

        // Pre-flight request (OPTIONS)
        if ($request->method()->value === 'OPTIONS') {
            return $this->handlePreflightRequest();
        }

        // Procesar la petición normal con encabezados CORS
        $response = $next($request);

        // Agregar encabezados CORS a la respuesta
        return $this->addCorsHeaders($response);
    }

    /**
     * Maneja una petición pre-flight (OPTIONS)
     * 
     * Las peticiones OPTIONS se usan para verificar si el servidor permite
     * la petición real que el navegador quiere hacer.
     * 
     * @return Response Respuesta con status 200 OK y encabezados CORS
     */
    protected function handlePreflightRequest(): Response
    {
        $response = new Response();
        $response->setStatusCode(200);

        // Agregar todos los encabezados CORS
        $this->addCorsHeaders($response);

        // Pre-flight requests no necesitan cuerpo
        return $response;
    }

    /**
     * Agrega los encabezados CORS a una respuesta
     * 
     * @param Response $response La respuesta a modificar
     * @return Response La respuesta con encabezados CORS
     */
    protected function addCorsHeaders(Response $response): Response
    {
        $config = Config::get('cors', []);

        // Orígenes permitidos
        $allowedOrigins = $config['allowed_origins'] ?? ['*'];
        $origin = $this->getOrigin();

        if ($allowedOrigins === ['*']) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        } elseif (in_array($origin, $allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        }

        // Métodos permitidos
        $allowedMethods = $config['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));

        // Encabezados permitidos
        $allowedHeaders = $config['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With'];
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));

        // Credenciales
        $allowCredentials = $config['allow_credentials'] ?? true;
        $response->setHeader('Access-Control-Allow-Credentials', $allowCredentials ? 'true' : 'false');

        // Tiempo máximo de cache (para pre-flight requests)
        $maxAge = $config['max_age'] ?? 86400;
        $response->setHeader('Access-Control-Max-Age', (string)$maxAge);

        // Encabezado Vary para que el navegador guarde respuestas por origen
        if ($allowedOrigins !== ['*']) {
            $response->setHeader('Vary', 'Origin');
        }

        return $response;
    }

    /**
     * Obtiene el origen de la petición desde los headers
     * 
     * @return string|null El origen o null si no existe
     */
    protected function getOrigin(): ?string
    {
        // Obtener el header Origin o HTTP_ORIGIN (dependiendo del servidor)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['ORIGIN'] ?? null;
        return $origin;
    }

    /**
     * Verifica si el origen está permitido
     * 
     * @param string $origin El origen a verificar
     * @return bool True si está permitido, false en caso contrario
     */
    protected function isOriginAllowed(string $origin): bool
    {
        $config = Config::get('cors', []);
        $allowedOrigins = $config['allowed_origins'] ?? ['*'];

        if ($allowedOrigins === ['*']) {
            return true;
        }

        return in_array($origin, $allowedOrigins);
    }
}
