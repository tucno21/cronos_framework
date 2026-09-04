<?php

namespace Cronos\View\Exceptions;

class ViewCompileException extends \RuntimeException
{
    public static function forView(string $view, string $message): self
    {
        return new self("Error compilando la vista [{$view}]: {$message}");
    }
}
