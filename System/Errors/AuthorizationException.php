<?php

namespace Cronos\Errors;

use Cronos\Exceptions\CronosException;

/**
 * AuthorizationException - Acceso denegado (HTTP 403)
 * 
 * Se lanza cuando un usuario intenta acceder a un recurso sin los permisos necesarios.
 */
class AuthorizationException extends CronosException
{
    /**
     * @var int Código de estado HTTP (siempre 403)
     */
    protected int $statusCode = 403;

    /**
     * Constructor
     * 
     * @param string $message Mensaje de error
     * @param int $code Código de error interno
     * @param \Throwable|null $previous Excepción anterior
     */
    public function __construct(
        string $message = "Access Denied",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
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
}
