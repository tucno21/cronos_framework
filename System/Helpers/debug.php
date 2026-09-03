<?php

if (!function_exists('dd')) {
    /**
     * debugear sin continuar con otros codigos de linea
     */
    function dd(mixed $variable): never
    {
        echo "<pre>";
        var_dump($variable);
        echo "</pre>";
        exit;
    }
}


if (!function_exists('d')) {
    /**
     * debugear continuando las lineas de codigo
     */
    function d(mixed $variable): void
    {
        echo "<pre>";
        var_dump($variable);
        echo "</pre>";
    }
}
