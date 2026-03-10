<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;
use Cronos\Session\Session;

/**
 * ThrottleMiddleware - Limita la tasa de peticiones por IP
 * 
 * Este middleware previene ataques de fuerza bruta y abuso de API
 * limitando cuántas peticiones puede hacer una IP en un período de tiempo.
 * 
 * Configuración:
 * - max_requests: Máximo de peticiones permitidas (default: 60)
 * - decay_minutes: Tiempo en minutos antes de resetear el contador (default: 1)
 * - on_limit: Qué hacer cuando se excede el límite ('block' o 'log_only')
 * 
 * Ejemplo de uso en rutas:
 * Route::get('/api', [ApiController::class, 'index'])
 *     ->middleware(ThrottleMiddleware::class);
 */
class ThrottleMiddleware implements Middleware
{
    /**
     * Máximo de peticiones permitidas por ventana de tiempo
     */
    protected int $maxRequests;

    /**
     * Ventana de tiempo en minutos
     */
    protected int $decayMinutes;

    /**
     * Acción a tomar cuando se excede el límite
     * 'block' - Retorna 429 Too Many Requests
     * 'log_only' - Solo registra, permite la petición
     */
    protected string $onLimit;

    /**
     * Clave de sesión para almacenar el contador
     */
    protected string $sessionKey;

    /**
     * Constructor - Configura el middleware
     * 
     * @param int $maxRequests Máximo de peticiones (default: 60)
     * @param int $decayMinutes Ventana de tiempo en minutos (default: 1)
     * @param string $onLimit Acción al exceder límite (default: 'block')
     */
    public function __construct(
        int $maxRequests = 60,
        int $decayMinutes = 1,
        string $onLimit = 'block'
    ) {
        $this->maxRequests = $maxRequests;
        $this->decayMinutes = $decayMinutes;
        $this->onLimit = $onLimit;
        $this->sessionKey = 'throttle_' . md5($this->getClientIp());
    }

    /**
     * Maneja la petición y aplica el límite de tasa
     * 
     * @param Request $request La petición HTTP
     * @param Closure $next El siguiente middleware en la cadena
     * @return Response La respuesta procesada o 429 si excede límite
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = session();

        // Obtener datos del throttle actual
        $throttleData = $this->getThrottleData($session);

        // Verificar si el contador expiró
        if ($this->isExpired($throttleData)) {
            // Resetear el contador
            $throttleData = $this->resetCounter();
        }

        // Incrementar el contador
        $throttleData['count']++;
        $throttleData['last_attempt'] = time();

        // Verificar si se excedió el límite
        $isOverLimit = $throttleData['count'] > $this->maxRequests;

        // Guardar los datos actualizados
        $session->set($this->sessionKey, $throttleData);

        // Si se excedió el límite
        if ($isOverLimit) {
            $this->logOverLimit($request, $throttleData);

            if ($this->onLimit === 'block') {
                return $this->getTooManyRequestsResponse($throttleData);
            }
            // Si es 'log_only', continuar con la petición
        }

        // Agregar headers informativos sobre el rate limit
        return $this->addRateLimitHeaders(
            $next($request),
            $throttleData
        );
    }

    /**
     * Obtiene los datos de throttling desde la sesión
     * 
     * @param Session $session Instancia de sesión
     * @return array Datos de throttling
     */
    protected function getThrottleData(Session $session): array
    {
        return $session->get($this->sessionKey, [
            'count' => 0,
            'created_at' => time(),
            'last_attempt' => time(),
        ]);
    }

    /**
     * Verifica si el contador de throttling expiró
     * 
     * @param array $throttleData Datos de throttling
     * @return bool True si expiró, false en caso contrario
     */
    protected function isExpired(array $throttleData): bool
    {
        $expireTime = $throttleData['created_at'] + ($this->decayMinutes * 60);
        return time() > $expireTime;
    }

    /**
     * Resetea el contador de throttling
     * 
     * @return array Nuevos datos de throttling reseteados
     */
    protected function resetCounter(): array
    {
        return [
            'count' => 0,
            'created_at' => time(),
            'last_attempt' => time(),
        ];
    }

    /**
     * Obtiene la dirección IP del cliente
     * 
     * @return string La dirección IP del cliente
     */
    protected function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // Manejar múltiples IPs (pueden estar separadas por coma)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Fallback
    }

    /**
     * Genera una respuesta 429 Too Many Requests
     * 
     * @param array $throttleData Datos de throttling
     * @return Response Respuesta con código 429
     */
    protected function getTooManyRequestsResponse(array $throttleData): Response
    {
        $response = new Response();

        $retryAfter = ($throttleData['created_at'] + ($this->decayMinutes * 60)) - time();

        return $response
            ->setStatusCode(429)
            ->setHeader('Retry-After', (string)$retryAfter)
            ->setHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->setHeader('X-RateLimit-Remaining', '0')
            ->setHeader('X-RateLimit-Reset', (string)($throttleData['created_at'] + ($this->decayMinutes * 60)))
            ->setContent(json_encode([
                'status' => 'error',
                'message' => 'Too many requests',
                'retry_after' => $retryAfter,
            ]))
            ->setContentType('application/json');
    }

    /**
     * Agrega headers informativos de rate limit a la respuesta
     * 
     * @param Response $response La respuesta a modificar
     * @param array $throttleData Datos de throttling
     * @return Response La respuesta con headers agregados
     */
    protected function addRateLimitHeaders(Response $response, array $throttleData): Response
    {
        $remaining = max(0, $this->maxRequests - $throttleData['count']);
        $reset = $throttleData['created_at'] + ($this->decayMinutes * 60);

        return $response
            ->setHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->setHeader('X-RateLimit-Remaining', (string)$remaining)
            ->setHeader('X-RateLimit-Reset', (string)$reset);
    }

    /**
     * Registra cuando se excede el límite de peticiones
     * 
     * @param Request $request La petición que excedió el límite
     * @param array $throttleData Datos de throttling
     * @return void
     */
    protected function logOverLimit(Request $request, array $throttleData): void
    {
        $logMessage = sprintf(
            '[THROTTLE] IP: %s | URI: %s | Count: %d/%d | Time: %s',
            $this->getClientIp(),
            $request->uri(),
            $throttleData['count'],
            $this->maxRequests,
            date('Y-m-d H:i:s')
        );

        // En un sistema real, esto iría a un archivo de log
        // Por ahora, solo usamos error_log de PHP
        error_log($logMessage);
    }
}
