<?php

namespace Cronos\Errors;

use Cronos\Exceptions\CronosException;

/**
 * MethodNotAllowedException - Método HTTP no permitido (HTTP 405)
 * 
 * Se lanza cuando se intenta usar un método HTTP que no está permitido para una ruta.
 */
class MethodNotAllowedException extends CronosException
{
    /**
     * @var int Código de estado HTTP (siempre 405)
     */
    protected int $statusCode = 405;

    /**
     * @var array Métodos HTTP permitidos
     */
    protected array $allowedMethods;

    /**
     * Constructor
     * 
     * @param array $allowedMethods Métodos HTTP permitidos
     * @param string $message Mensaje de error
     * @param int $code Código de error interno
     * @param \Throwable|null $previous Excepción anterior
     */
    public function __construct(
        array $allowedMethods = [],
        string $message = "Method Not Allowed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->allowedMethods = $allowedMethods;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Obtiene el código de estado HTTP
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Obtiene los métodos HTTP permitidos
     * 
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
