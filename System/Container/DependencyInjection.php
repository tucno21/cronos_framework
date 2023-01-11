<?php

namespace Cronos\Container;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use Cronos\Container\Container;

class DependencyInjection
{
    public static function resolveParameters(Closure|array $callback, $routeParameters = [])
    {
        $methodOrFunction = is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1]) // array($controller, $method)
            : new ReflectionFunction($callback); // array($function) almacenada methodOrFunction

        $params = [];

        //recorrer los parametros de la funcion o metodo de la clase que se esta ejecutando
        foreach ($methodOrFunction->getParameters() as $param) {
            $resolved = null;

            //verificar si el parametro es de tipo primitivo
            if ($param->getType()->isBuiltin()) {
                //buscar el parametro en el array de parametros de la ruta
                $resolved = $routeParameters[$param->getName()] ?? null;
            } else {
                //instanciar la clase del parametro
                $resolved = Container::singleton($param->getType()->getName());
            }

            $params[] = $resolved;
        }

        return $params;
    }
}
