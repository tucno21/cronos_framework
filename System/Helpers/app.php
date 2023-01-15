<?php

use Cronos\App;
use Cronos\Config\Config;
use Cronos\Container\Container;

function app(string $class = App::class)
{
    return Container::resolve($class);
}

function singleton(string $class, string|callable|null $build = null)
{
    Container::singleton($class, $build);
}

function configGet(string $configuration, $default = null)
{
    return Config::get($configuration, $default);
}

function resourcesDirectory()
{
    return App::$root . '/resources';
}

function env(string $variable, $default = null)
{
    //obtener el valor de una variable de entorno del archivo .env
    return $_ENV[$variable] ?? $default;
}

/**
 * obtener la ruta web de la aplicacion sin "/"
 */
if (!function_exists('last_char')) {
    function last_char($string)
    {
        //extraer el ultimo letra de un string
        $slash = substr($string, -1);
        if ($slash == '/') {
            //eliminar la ultima letra del string
            return substr($string, 0, -1);
        } else {
            return $string;
        }
    }
}

/**
 * formato de salida de validation
 */
define('RESULT_TYPE', 'object');

$baseURL = 'http://cronos_framework.test/';

/**
 * url de la web Principal
 */
define('base_url', last_char($baseURL));


if (!function_exists('base_url')) {
    /**
     * funcion url con parametros
     */
    function base_url($parameters = null)
    {
        return base_url . $parameters;
    }
}
