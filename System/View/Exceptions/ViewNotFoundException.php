<?php

namespace Cronos\View\Exceptions;

class ViewNotFoundException extends \RuntimeException
{
    public static function forView(string $view, string $path): self
    {
        return new self("La vista [{$view}] no existe. Ruta buscada: {$path}");
    }
}
