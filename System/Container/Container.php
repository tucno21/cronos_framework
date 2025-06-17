<?php

namespace Cronos\Container;

use Cronos\Container\DependencyInjection;

class Container
{
    //propiedad para almacenar instancias de una clase y sea unica
    private static array $instances = [];

    //metodo para crear una instancia de una clase
    //$class = nombre de la clase
    //$build = nombre de la clase o funcion anonima para crear la instancia
    public static function singleton(string $class, string|callable|null $build = null): object
    {
        //si no existe la clase en el array de instancias
        if (!array_key_exists($class, self::$instances)) {
            //match 
            match (true) {
                is_null($build) => self::$instances[$class] = DependencyInjection::resolveClass($class),
                is_string($build) => self::$instances[$class] = DependencyInjection::resolveClass($build),
                is_callable($build) => self::$instances[$class] = $build(),
            };
        }
        //retornamos la instancia de la clase
        return self::$instances[$class];
    }

    /**
     * Resuelve una instancia de una clase del contenedor.
     * Si no existe como singleton, intenta resolverla usando autowiring.
     *
     * @param string $class El nombre de la clase a resolver.
     * @return object La instancia resuelta de la clase.
     */
    public static function resolve(string $class): object
    {
        if (self::has($class)) {
            return self::$instances[$class];
        }

        // Si no es un singleton, intentar resolverla con autowiring
        return DependencyInjection::resolveClass($class);
    }

    /**
     * Verifica si una clase ya tiene una instancia singleton en el contenedor.
     *
     * @param string $class El nombre de la clase.
     * @return bool True si la instancia existe, false en caso contrario.
     */
    public static function has(string $class): bool
    {
        return array_key_exists($class, self::$instances);
    }
}
