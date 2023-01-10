<?php

if (!function_exists('dd')) {
    /**
     * debugear sin continuar con otros codigos de linea
     */
    function dd($variable)
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
    function d($variable)
    {
        echo "<pre>";
        var_dump($variable);
        echo "</pre>";
    }
}
