<?php

use Cronos\App;

/**
 * formato de salida de validation
 */
define('RESULT_TYPE', 'object');

//obtener la ruta de la aplicacion "\laragon\www\cronos_framework
define('ROOT', App::$root);

//obtener la ruta de la carpeta public "\laragon\www\cronos_framework\public
define('DIR_PUBLIC',  ROOT . '/public');

define('DIR_IMG', DIR_PUBLIC . '/' . env('FILE_STORAGE', 'public') . '/');

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


define('base_url', last_char(env('APP_URL', 'http://cronos_framework.test')));


if (!function_exists('base_url')) {
    /**
     * funcion url con parametros
     */
    function base_url($parameters = null)
    {
        return base_url . $parameters;
    }
}
