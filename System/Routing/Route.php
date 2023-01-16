<?php

namespace Cronos\Routing;

use Closure;
use Cronos\App;
use Cronos\Container\Container;

class Route
{
    //almacenamos la uri y la accion que viene del framework
    protected string $uri;

    //almacenamos la accion que viene del framework
    protected Closure|array $action;

    //almacenamos la expresion regular de la uri
    protected string $regex;

    //almacenamos los parametros de la uri en un array
    protected array $parameters;

    //almacenamos los middlewares
    protected array $middlewares = [];

    public function __construct(string $uri, Closure|array $action)
    {
        //alamacenar la uri que viene del framework
        $this->uri = $uri; // /producto/{producto}
        //almacenar la accion que viene del framework
        $this->action = $action;
        //preg_replace remplaza los parametros de la uri por expresiones regulares
        $this->regex = preg_replace('/\{([a-zA-Z]+)(?::([a-zA-Z]+))?\}/', '([a-zA-Z0-9-]+)', $uri); // /producto/([a-zA-Z0-9]+)


        preg_match_all('/\{([a-zA-Z]+)(?::([a-zA-Z]+))?\}/', $uri, $parameters);
        array_shift($parameters); //eliminamos el primer elemento del array



        $new_array = [];
        //cambiar este arreglo original [[user,contac,blog],[name,'',sglu]] por este [[user,name],[contac,''],[blog,sglu]]
        // foreach ($parameters as $key => $value) {
        //     foreach ($value as $key2 => $value2) {
        //         $new_array[$key2][$key] = $value2;
        //     }
        // }

        //comprobamos que el segundo elemento del array no este vacio [[user,name],[contac,''],[blog,sglu]]
        // foreach ($new_array as $key => $value) {
        //     if ($value[1] == '') {
        //         $new_array[$key] = $value[0];
        //     }
        // }

        //cambiar este arreglo original [[user,contac,blog],[name,'',sglu]] por este [[user,name],contac,[blog,sglu]]
        foreach ($parameters[0] as $key => $value) {
            if ($parameters[1][$key] === '') {
                $new_array[] = $value;
            } else {
                $new_array[] = [$value, $parameters[1][$key]];
            }
        }

        $this->parameters = $new_array;
    }

    public function uri()
    {
        return $this->uri;
    }

    public function action()
    {
        return $this->action;
    }

    public function matches(string $uri): bool
    {
        //sirve comparar si la ruta web que le pasamos es igual a la ruta que tenemos en el framework
        return preg_match("#^$this->regex/?$#", $uri);
    }

    public function hasParameters(): bool
    {
        //true si hay parametros que se obtuviron de /tests/{test}
        return count($this->parameters) > 0;
    }

    public function parseParameters(string $uri): array
    {
        preg_match("#^$this->regex$#", $uri, $arguments);

        if (count($this->parameters) === 0) {
            return array_combine($this->parameters, array_slice($arguments, 1));
        }

        array_shift($arguments); //eliminamos el primer elemento del array
        //$arguments = [admin,2]
        //$this->parameters = [[user,name],contac]
        //recorremos y convertimos de esta forma [user=>[name=>admin],contac=>2]

        $new_array = [];
        foreach ($this->parameters as $key => $value) {
            if (is_array($value)) {
                $new_array[$value[0]] = [$value[1] => $arguments[$key]];
            } else {
                $new_array[$value] = $arguments[$key];
            }
        }

        return $new_array;
    }

    public static function get(string $uri, Closure|array $action): Route
    {
        //enviamos la uri y la accion al router mediante la instancia de la clase App
        return Container::resolve(App::class)->router->get($uri, $action);
    }

    public static function post(string $uri, Closure|array $action): Route
    {
        //enviamos la uri y la accion al router mediante la instancia de la clase App
        return Container::resolve(App::class)->router->post($uri, $action);
    }

    public static function put(string $uri, Closure|array $action): Route
    {
        //enviamos la uri y la accion al router mediante la instancia de la clase App
        return Container::resolve(App::class)->router->put($uri, $action);
    }

    public static function patch(string $uri, Closure|array $action): Route
    {
        //enviamos la uri y la accion al router mediante la instancia de la clase App
        return Container::resolve(App::class)->router->patch($uri, $action);
    }

    public static function delete(string $uri, Closure|array $action): Route
    {
        //enviamos la uri y la accion al router mediante la instancia de la clase App
        return Container::resolve(App::class)->router->delete($uri, $action);
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function middleware(string|array $middlewares): self
    {
        if (is_string($middlewares)) {
            //si es un string lo convertimos en array
            $middlewares = [$middlewares];
        }

        //array_map crea un nuevo array con los resultados de la funcion
        //almacenamos los middlewares en el array $middlewares
        $this->middlewares = array_map(fn ($middleware) => new $middleware, $middlewares);

        return $this;
    }

    public function name(string $name): self
    {
        Container::resolve(App::class)->router->name($name);
        //almacenamos el nombre de la ruta
        return $this;
    }

    public static function load(string $routesDirectory)
    {
        //glob sirve para buscar archivos con una extension especifica
        //recorre todos los archivos php que esten en la carpeta $routesDirectory
        foreach (glob("$routesDirectory/*.php") as $routes) {
            //requiere los archivos php
            require_once $routes;
        }
    }
}
