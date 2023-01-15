<?php

namespace Cronos\Config;

class Config
{
    private static array $config = [];

    //metodo para cargar los archivos de configuracion
    public static function loadConfig(string $pathConfig)
    {
        //$pathConfig ruta de la carpeta config
        //recorrer todos los archivos de la carpeta config
        foreach (glob("$pathConfig/*.php") as $fileConfig) {
            //obtener el nombre del archivo sin la extension
            $nameFile = explode(".", basename($fileConfig))[0];
            //obtener el contenido del archivo
            $values = require_once $fileConfig;
            //almacenar el contenido del archivo en un array
            self::$config[$nameFile] = $values;
        }
    }

    //metodo para obtener el valor de una configuracion
    public static function get(string $key, $default = null)
    {
        //$key = app.name
        //$key = app.config.name.estado

        //separar key por puntos para obtener el valor de un array
        $keys = explode(".", $key);
        //obtenemos el ultimo valor del array
        $finalKey = array_pop($keys);

        //guardamos el array de configuracion en una variable
        $arrayConfig = self::$config;

        foreach ($keys as $key) {
            //'name' => env('APP_NAME', 'Cronos'),
            //busca la key 'name' en el array de configuracion

            //verificar si existe la llave en el array de configuracion
            if (!array_key_exists($key, $arrayConfig)) {
                //si no existe la llave retornamos el valor por defecto
                return $default;
            }
            //guardamos el valor de la llave en el array de configuracion
            $arrayConfig = $arrayConfig[$key];
        }

        //retornamos el valor de la llave final // env('APP_NAME', 'Cronos')
        return $arrayConfig[$finalKey] ?? $default;
    }
}
