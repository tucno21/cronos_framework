<?php

namespace Cronos\Errors;

use Throwable;
use Cronos\Http\Response;
use Cronos\Http\Request;
use Cronos\Log\Logger;

/**
 * ExceptionHandler - Manejador centralizado de excepciones
 * 
 * Captura y maneja todos los tipos de errores: Exception, Error y errores fatales de PHP.
 * Detecta si la request es API y retorna JSON en ese caso.
 * Registra todos los errores en el sistema de Log.
 */
class ExceptionHandler
{
    /**
     * Instancia de la Request actual
     */
    protected ?Request $request = null;

    /**
     * Modo debug
     */
    protected bool $debug;

    /**
     * Constructor
     * 
     * @param Request|null $request Instancia de la Request
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request;
        $this->debug = (bool) env('APP_DEBUG', false);
    }

    /**
     * Maneja una excepción
     * 
     * @param Throwable $e La excepción a manejar
     * @return Response
     */
    public function handle(Throwable $e): Response
    {
        // Obtener contexto para el log
        $context = $this->getErrorContext($e);

        // Registrar el error en el log
        Logger::exception($e, $context);

        // Determinar código de estado HTTP
        $statusCode = $this->getStatusCode($e);

        // Determinar mensaje de error
        $message = $this->getErrorMessage($e);

        // Si es request de API, retornar JSON
        if ($this->isApiRequest()) {
            return $this->jsonResponse($e, $statusCode, $message);
        }

        // Si no es API, retornar vista de error
        return $this->viewResponse($e, $statusCode, $message);
    }

    /**
     * Obtiene el código de estado HTTP de una excepción
     * 
     * @param Throwable $e
     * @return int
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof HttpNotFoundException) {
            return 404;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof MethodNotAllowedException) {
            return 405;
        }

        // Para errores generales, usar 500
        return 500;
    }

    /**
     * Obtiene el mensaje de error apropiado
     * 
     * @param Throwable $e
     * @return string
     */
    protected function getErrorMessage(Throwable $e): string
    {
        // Si estamos en modo debug, mostrar el mensaje real
        if ($this->debug) {
            return $e->getMessage();
        }

        // Si no, mostrar mensajes genéricos según el código de estado
        $statusCode = $this->getStatusCode($e);

        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'An error occurred'
        };
    }

    /**
     * Obtiene el contexto del error para logging
     * 
     * @param Throwable $e
     * @return array
     */
    protected function getErrorContext(Throwable $e): array
    {
        $context = [];

        if ($this->request) {
            $context['url'] = $this->request->uri();
            $context['method'] = $this->request->method();
            $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        }

        return $context;
    }

    /**
     * Determina si la request actual es de tipo API
     * 
     * @return bool
     */
    protected function isApiRequest(): bool
    {
        // Verificar si tiene Accept: application/json
        if (
            isset($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        ) {
            return true;
        }

        // Verificar si la URI empieza con /api
        if ($this->request && strpos($this->request->uri(), '/api') === 0) {
            return true;
        }

        // Verificar si la ruta actual es api.php (configurada en RouteServiceProvider)
        if ($this->request && isset($_SERVER['REQUEST_URI'])) {
            $uri = $this->request->uri();
            // Asumir que cualquier ruta que empiece con /api es una API request
            if (strpos($uri, '/api') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera una respuesta JSON
     * 
     * @param Throwable $e
     * @param int $statusCode
     * @param string $message
     * @return Response
     */
    protected function jsonResponse(Throwable $e, int $statusCode, string $message): Response
    {
        $response = [
            'error' => true,
            'code' => $statusCode,
            'message' => $message
        ];

        // En modo debug, agregar más información
        if ($this->debug) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = $e->getTraceAsString();
        }

        // Para ValidationException, incluir los errores
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->getErrors();
        }

        // Para MethodNotAllowedException, incluir métodos permitidos
        if ($e instanceof MethodNotAllowedException) {
            $response['allowed_methods'] = $e->getAllowedMethods();
        }

        return json($response)->setStatusCode($statusCode);
    }

    /**
     * Genera una respuesta con vista
     * 
     * @param Throwable $e
     * @param int $statusCode
     * @param string $message
     * @return Response
     */
    protected function viewResponse(Throwable $e, int $statusCode, string $message): Response
    {
        // Intentar cargar vista específica del código de estado
        $viewName = "error/{$statusCode}";

        // Si la vista no existe, usar vista genérica
        if (!file_exists(resource_path("views/{$viewName}.php"))) {
            $viewName = 'error/error';
        }

        // En modo debug, agregar información detallada
        $viewData = [
            'statusCode' => $statusCode,
            'message' => $message,
        ];

        if ($this->debug) {
            $viewData['exception'] = get_class($e);
            $viewData['file'] = $e->getFile();
            $viewData['line'] = $e->getLine();
            $viewData['trace'] = $e->getTraceAsString();
        }

        return view($viewName, $viewData)->setStatusCode($statusCode);
    }

    /**
     * Maneja errores fatales de PHP
     * 
     * Este método se registra con register_shutdown_function()
     * 
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handle(
                new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                )
            );
        }
    }

    /**
     * Maneja errores de PHP (no fatales)
     * 
     * Este método se registra con set_error_handler()
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     * @throws \ErrorException
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // No reportar errores si están silenciados con @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Registra los handlers de errores globales
     * 
     * @param Request|null $request
     * @return void
     */
    public static function register(?Request $request = null): void
    {
        $handler = new self($request);

        // Registrar handler de excepciones
        set_exception_handler([$handler, 'handle']);

        // Registrar handler de errores
        set_error_handler([$handler, 'handleError']);

        // Registrar handler de shutdown (para errores fatales)
        register_shutdown_function([$handler, 'handleShutdown']);
    }
}
