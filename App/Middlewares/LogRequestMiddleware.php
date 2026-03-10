<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;

/**
 * LogRequestMiddleware - Registra cada petición HTTP en el sistema de log
 * 
 * Este middleware registra información detallada de cada petición entrante,
 * útil para debugging, análisis de tráfico y detección de anomalías.
 * 
 * Se ejecuta en TODAS las peticiones si se configura como middleware global.
 * 
 * Información registrada:
 * - Timestamp de la petición
 * - Método HTTP (GET, POST, etc.)
 * - URI de la petición
 * - IP del cliente
 * - User Agent del navegador
 * - Referer (si existe)
 * - Tiempo de ejecución de la petición
 * - Código de estado de la respuesta
 * 
 * Ejemplo de configuración en config/app.php:
 * 'global_middlewares' => [
 *     \App\Middlewares\LogRequestMiddleware::class,
 * ],
 * 
 * Opciones de configuración (pueden pasarse en el constructor):
 * - log_level: 'full', 'basic', 'minimal' (default: 'basic')
 * - log_to: 'error_log', 'file', 'database' (default: 'error_log')
 * - log_file: Ruta al archivo de log si log_to es 'file' (default: 'storage/logs/request.log')
 * - include_request_body: Si debe incluir el body de la petición (default: false)
 * - include_response_time: Si debe incluir el tiempo de respuesta (default: true)
 */
class LogRequestMiddleware implements Middleware
{
    /**
     * Nivel de detalle del log
     * 'full' - Toda la información posible
     * 'basic' - Información básica de la petición
     * 'minimal' - Solo URI y método
     */
    protected string $logLevel;

    /**
     * Dónde guardar el log
     * 'error_log' - Usar error_log de PHP
     * 'file' - Guardar en archivo
     * 'database' - Guardar en base de datos (no implementado aún)
     */
    protected string $logTo;

    /**
     * Ruta del archivo de log
     */
    protected string $logFile;

    /**
     * Si debe incluir el body de la petición
     */
    protected bool $includeRequestBody;

    /**
     * Si debe incluir el tiempo de respuesta
     */
    protected bool $includeResponseTime;

    /**
     * Timestamp de inicio de la petición
     */
    protected float $startTime;

    /**
     * Constructor - Configura el middleware
     * 
     * @param string $logLevel Nivel de detalle ('full', 'basic', 'minimal')
     * @param string $logTo Dónde guardar el log ('error_log', 'file', 'database')
     * @param string $logFile Ruta al archivo de log
     * @param bool $includeRequestBody Si incluir el body
     * @param bool $includeResponseTime Si incluir tiempo de respuesta
     */
    public function __construct(
        string $logLevel = 'basic',
        string $logTo = 'error_log',
        string $logFile = 'storage/logs/request.log',
        bool $includeRequestBody = false,
        bool $includeResponseTime = true
    ) {
        $this->logLevel = $logLevel;
        $this->logTo = $logTo;
        $this->logFile = $logFile;
        $this->includeRequestBody = $includeRequestBody;
        $this->includeResponseTime = $includeResponseTime;
        $this->startTime = microtime(true);

        // Crear directorio de logs si no existe
        if ($logTo === 'file') {
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }

    /**
     * Maneja la petición y registra la información
     * 
     * @param Request $request La petición HTTP
     * @param Closure $next El siguiente middleware en la cadena
     * @return Response La respuesta procesada
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ejecutar la petición
        $response = $next($request);

        // Calcular tiempo de ejecución si está habilitado
        $executionTime = 0;
        if ($this->includeResponseTime) {
            $executionTime = microtime(true) - $this->startTime;
        }

        // Preparar datos del log según el nivel
        $logData = $this->prepareLogData($request, $response, $executionTime);

        // Escribir el log según el método configurado
        $this->writeLog($logData);

        return $response;
    }

    /**
     * Prepara los datos del log según el nivel configurado
     * 
     * @param Request $request La petición HTTP
     * @param Response $response La respuesta HTTP
     * @param float $executionTime Tiempo de ejecución en segundos
     * @return string Los datos del log formateados
     */
    protected function prepareLogData(Request $request, Response $response, float $executionTime): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $method = $request->method()->value;
        $uri = $request->uri();
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $referer = $_SERVER['HTTP_REFERER'] ?? '-';
        $statusCode = $response->statusCode();

        // Nivel minimal: Solo lo esencial
        if ($this->logLevel === 'minimal') {
            return sprintf(
                "[%s] %s %s",
                $timestamp,
                $method,
                $uri
            );
        }

        // Nivel básico: Información estándar
        if ($this->logLevel === 'basic') {
            return sprintf(
                "[%s] %s %s | IP: %s | Status: %d | Time: %.3fs",
                $timestamp,
                $method,
                $uri,
                $ip,
                $statusCode,
                $executionTime
            );
        }

        // Nivel full: Toda la información posible
        $logData = [
            'timestamp' => $timestamp,
            'method' => $method,
            'uri' => $uri,
            'ip' => $ip,
            'status_code' => $statusCode,
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'user_agent' => $userAgent,
            'referer' => $referer,
        ];

        // Agregar body de la petición si está habilitado
        if ($this->includeRequestBody && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = $request->all();
            // Ocultar datos sensibles
            if (isset($body['password'])) {
                $body['password'] = '***HIDDEN***';
            }
            $logData['request_body'] = $body;
        }

        return json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Escribe el log según el método configurado
     * 
     * @param string $logData Los datos del log
     * @return void
     */
    protected function writeLog(string $logData): void
    {
        switch ($this->logTo) {
            case 'file':
                $this->writeToFile($logData);
                break;
            case 'database':
                $this->writeToDatabase($logData);
                break;
            case 'error_log':
            default:
                error_log($logData);
                break;
        }
    }

    /**
     * Escribe el log a un archivo
     * 
     * @param string $logData Los datos del log
     * @return void
     */
    protected function writeToFile(string $logData): void
    {
        $logEntry = $logData . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Escribe el log a base de datos (no implementado)
     * 
     * @param string $logData Los datos del log
     * @return void
     */
    protected function writeToDatabase(string $logData): void
    {
        // TODO: Implementar guardado en base de datos
        // Podría crear una tabla 'request_logs' y guardar allí
        error_log('[LogRequestMiddleware] Database logging not implemented yet. Falling back to error_log.');
        error_log($logData);
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

                // Manejar múltiples IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
