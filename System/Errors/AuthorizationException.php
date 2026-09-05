<?php

declare(strict_types=1);

namespace Cronos\Errors;

use Cronos\Exceptions\CronosException;

class AuthorizationException extends CronosException
{
    public function __construct(string $message = "This action is unauthorized.", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
