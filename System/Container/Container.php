<?php

namespace Cronos\Container;

class Container
{
    //propiedad para almacenar instancias de una clase y sea unica
    private static array $instances = [];

    //metodo para crear una instancia de una clase
    //$class = nombre de la clase
    //$build = nombre de la clase o funcion anonima para crear la instancia
    public static function singleton(string $class, string|callable|null $build = null)
    {
        //si no existe la clase en el array de instancias
        if (!array_key_exists($class, self::$instances)) {
            //match 
            match (true) {
                is_null($build) => self::$instances[$class] = new $class(),
                is_string($build) => self::$instances[$class] = new $build(),
                is_callable($build) => self::$instances[$class] = $build(),
            };
        }
        //retornamos la instancia de la clase
        return self::$instances[$class];
    }

    public static function resolve(string $class)
    {
        return self::$instances[$class] ?? null;
    }
}
