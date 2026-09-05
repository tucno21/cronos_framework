<?php

declare(strict_types=1);

namespace Cronos\Errors;

use Cronos\Exceptions\CronosException;
use Cronos\Http\Response;

class ValidationException extends CronosException
{
    protected array|object $errors;
    protected ?Response $response;

    public function __construct(array|object $errors, ?Response $response = null, string $message = "The given data was invalid.")
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
        $this->response = $response;
    }

    public function errors(): array|object
    {
        return $this->errors;
    }

    public function response(): ?Response
    {
        return $this->response;
    }
}
