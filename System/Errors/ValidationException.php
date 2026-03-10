<?php

namespace Cronos\Errors;

use Cronos\Exceptions\CronosException;

/**
 * ValidationException - Excepción para errores de validación
 * 
 * Se lanza automáticamente cuando falla la validación de datos.
 */
class ValidationException extends CronosException
{
    /**
     * @var array Errores de validación
     */
    protected array $errors;

    /**
     * Constructor
     * 
     * @param array $errors Array de errores de validación
     * @param string $message Mensaje de error general
     * @param int $code Código de error interno
     * @param \Throwable|null $previous Excepción anterior
     */
    public function __construct(
        array $errors,
        string $message = "Validation failed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Obtiene los errores de validación
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtiene el primer mensaje de error
     * 
     * @return string
     */
    public function getFirstError(): string
    {
        return !empty($this->errors) ? reset($this->errors)[0] ?? 'Unknown error' : 'No errors';
    }
}
