<?php

namespace Cronos\Errors;

use Cronos\Exceptions\CronosException;

/**
 * HttpException - Excepción HTTP genérica con código de estado configurable
 * 
 * Esta clase permite lanzar excepciones con códigos de estado HTTP específicos.
 */
class HttpException extends CronosException
{
    /**
     * @var int Código de estado HTTP
     */
    protected int $statusCode;

    /**
     * Constructor
     * 
     * @param int $statusCode Código de estado HTTP (404, 500, 403, etc.)
     * @param string $message Mensaje de error
     * @param int $code Código de error interno
     * @param \Throwable|null $previous Excepción anterior
     */
    public function __construct(
        int $statusCode = 500,
        string $message = "Internal Server Error",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
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
     * Establece el código de estado HTTP
     * 
     * @param int $statusCode
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
}
