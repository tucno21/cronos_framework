<?php

namespace Cronos\Container;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Cronos\Model\Model;
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

            //is_subclass_of verifica si la clase que viene del parametro del metodo Controller es una subclase de Model
            if (is_subclass_of($param->getType()->getName(), Model::class)) {
                //instanciar la clase del parametro
                $modelClass = new ReflectionClass($param->getType()->getName());

                //separamos la clase por \
                $arrayClass = explode('\\', $param->getType()->getName());
                //obtenemos el ultimo elemento del array
                $nameClass = end($arrayClass);
                //cambiamo todo a minusculas
                $keyParam = strtolower($nameClass);

                //buscamos el valor del parametro que viene de la ruta
                //ejecutamos el metodo find del modelo
                $resolved = $param->getType()->getName()::find($routeParameters[$keyParam] ?? 0);
            } else if ($param->getType()->isBuiltin()) { //verificar si el parametro es de tipo primitivo
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
