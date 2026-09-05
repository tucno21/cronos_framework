<?php

declare(strict_types=1);

use Cronos\Debug\Dumper;

if (!function_exists('dump')) {
    /**
     * Vuelca las variables especificadas permitiendo continuar con la ejecución del script.
     * Soporta múltiples argumentos y adapta la salida automáticamente a CLI, Web o JSON.
     */
    function dump(mixed ...$vars): void
    {
        Dumper::dump(...$vars);
    }
}

if (!function_exists('d')) {
    /**
     * Alias de dump() para depurar continuando la ejecución.
     */
    function d(mixed ...$vars): void
    {
        Dumper::dump(...$vars);
    }
}

if (!function_exists('dd')) {
    /**
     * Vuelca las variables especificadas y termina la ejecución del script (Dump and Die).
     * Soporta múltiples argumentos y adapta la salida automáticamente a CLI, Web o JSON.
     */
    function dd(mixed ...$vars): never
    {
        Dumper::dd(...$vars);
    }
}

