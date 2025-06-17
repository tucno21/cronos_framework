<?php

namespace Cronos\Container;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Cronos\Model\Model;
use ReflectionFunction;
use Cronos\Container\Container;
use Cronos\Errors\HttpNotFoundException;
use ReflectionUnionType;
use ReflectionNamedType;

class DependencyInjection
{
    /**
     * Resuelve una clase y sus dependencias de forma recursiva.
     *
     * @param string $class El nombre de la clase a resolver.
     * @return object La instancia resuelta de la clase.
     * @throws \ReflectionException Si la clase no existe o no se puede instanciar.
     */
    public static function resolveClass(string $class): object
    {
        // Si la clase ya es un singleton en el contenedor, la retornamos.
        if (Container::has($class)) {
            return Container::resolve($class);
        }

        $reflector = new ReflectionClass($class);

        // Si la clase no es instanciable (ej. interfaz, clase abstracta), lanzamos una excepción.
        if (!$reflector->isInstantiable()) {
            throw new \ReflectionException("Class {$class} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // Si no hay constructor, simplemente instanciamos la clase.
        if (is_null($constructor)) {
            return new $class();
        }

        // Resolvemos las dependencias del constructor.
        $dependencies = self::resolveDependencies($constructor->getParameters());

        // Instanciamos la clase con sus dependencias.
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resuelve las dependencias para un conjunto de parámetros.
     *
     * @param array $parameters Array de ReflectionParameter.
     * @return array Array de dependencias resueltas.
     * @throws \ReflectionException Si una dependencia no puede ser resuelta.
     */
    protected static function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Si el parámetro no tiene tipo o es un tipo primitivo, intentamos resolverlo.
            if (is_null($type) || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // Podríamos lanzar una excepción o intentar resolver desde algún otro lugar (ej. Request).
                    // Por ahora, si es primitivo y no tiene valor por defecto, lo dejamos como null o lanzamos error.
                    // Para autowiring básico, asumimos que las dependencias de objetos son clases.
                    throw new \ReflectionException("Unresolvable dependency: {$parameter->getName()}");
                }
            } elseif ($type instanceof ReflectionNamedType) {
                $className = $type->getName();
                if ($type->isBuiltin()) {
                    // Esto ya debería estar cubierto por el if anterior, pero por seguridad.
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new \ReflectionException("Unresolvable builtin dependency: {$parameter->getName()}");
                    }
                } else {
                    // Recursivamente resolvemos la dependencia de la clase.
                    $dependencies[] = self::resolveClass($className);
                }
            } elseif ($type instanceof ReflectionUnionType) {
                // Manejar tipos de unión (ej. ClassA|ClassB). Por simplicidad, intentaremos resolver el primer tipo que sea una clase.
                $resolved = false;
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType && !$unionType->isBuiltin()) {
                        try {
                            $dependencies[] = self::resolveClass($unionType->getName());
                            $resolved = true;
                            break;
                        } catch (\ReflectionException $e) {
                            // Intentar con el siguiente tipo en la unión
                        }
                    }
                }
                if (!$resolved) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new \ReflectionException("Unresolvable union type dependency: {$parameter->getName()}");
                    }
                }
            }
        }

        return $dependencies;
    }

    public static function resolveParameters(Closure|array $callback, $routeParameters = [])
    {
        $methodOrFunction = is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1]) // array($controller, $method)
            : new ReflectionFunction($callback); // array($function) almacenada methodOrFunction

        $params = [];

        //recorrer los parametros de la funcion o metodo de la clase que se esta ejecutando
        foreach ($methodOrFunction->getParameters() as $param) {
            $resolved = null;
            $type = $param->getType();

            if (is_null($type)) {
                // Si no hay tipo, intentar resolver desde los parámetros de ruta o valor por defecto
                if (array_key_exists($param->getName(), $routeParameters)) {
                    $resolved = $routeParameters[$param->getName()];
                } elseif ($param->isDefaultValueAvailable()) {
                    $resolved = $param->getDefaultValue();
                } else {
                    // No se pudo resolver, lanzar excepción o manejar de otra forma
                    throw new \ReflectionException("Unresolvable dependency: {$param->getName()}");
                }
            } elseif ($type->isBuiltin()) { // verificar si el parametro es de tipo primitivo
                // buscar el parametro en el array de parametros de la ruta
                $resolved = $routeParameters[$param->getName()] ?? null;
                if (is_null($resolved) && $param->isDefaultValueAvailable()) {
                    $resolved = $param->getDefaultValue();
                }
            } elseif ($type instanceof ReflectionNamedType) {
                $className = $type->getName();
                //is_subclass_of verifica si la clase que viene del parametro del metodo Controller es una subclase de Model
                if (is_subclass_of($className, Model::class)) {
                    //separamos la clase por \
                    $arrayClass = explode('\\', $className);
                    //obtenemos el ultimo elemento del array
                    $nameClass = end($arrayClass);
                    //cambiamo todo a minusculas EN NOMBRE DE LA CLASE QUE VIENE DEL PARAMETRO DEL CONTROLADOR
                    $keyParam = strtolower($nameClass);

                    if (isset($routeParameters[$keyParam])) {
                        if (is_string($routeParameters[$keyParam])) {
                            $id = $routeParameters[$keyParam];
                            //buscamos el valor del parametro que viene de la ruta
                            //ejecutamos el metodo find del modelo
                            $resolved = $className::find($id ?? 0);
                            if (is_null($resolved)) {
                                throw new HttpNotFoundException();
                            }
                        } else {
                            //obtener la clave de $routeParameters[$keyParam]
                            $column = array_key_first($routeParameters[$keyParam]);
                            //obtener el valor de $routeParameters[$keyParam]
                            $value = $routeParameters[$keyParam][$column];
                            //ejecutamos el metodo where del modelo
                            $resolved = $className::where($column, $value)->first();
                            if (is_null($resolved)) {
                                throw new HttpNotFoundException();
                            }
                        }
                    } else {
                        // Si no hay parámetro de ruta para el modelo, intentar resolverlo como una clase normal
                        $resolved = self::resolveClass($className);
                    }
                } else {
                    //instanciar la clase del parametro usando el nuevo método de resolución
                    $resolved = self::resolveClass($className);
                }
            } elseif ($type instanceof ReflectionUnionType) {
                // Manejar tipos de unión en parámetros de ruta, similar a resolveDependencies
                $resolved = false;
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType && !$unionType->isBuiltin()) {
                        try {
                            $resolved = self::resolveClass($unionType->getName());
                            break;
                        } catch (\ReflectionException $e) {
                            // Intentar con el siguiente tipo en la unión
                        }
                    }
                }
                if (!$resolved) {
                    if ($param->isDefaultValueAvailable()) {
                        $resolved = $param->getDefaultValue();
                    } else {
                        throw new \ReflectionException("Unresolvable union type dependency for parameter: {$param->getName()}");
                    }
                }
            }

            $params[] = $resolved;
        }

        return $params;
    }
}
