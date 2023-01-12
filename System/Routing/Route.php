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
        $this->regex = preg_replace('/\{([a-zA-Z]+)\}/', '([a-zA-Z0-9]+)', $uri); // /producto/([a-zA-Z0-9]+)
        //preg_match_all busca el regex ([a-zA-Z0-9]+) uri y lo almacena en el array $parameters
        $parameters = [];
        preg_match_all('/\{([a-zA-Z]+)\}/', $uri, $parameters); // [producto]
        //almacenamos los parametros de la uri
        $this->parameters = $parameters[1]; // [producto]
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
        //['test', 'user'] del framework
        //['3', '5'] de la web
        // devuelve la union

        //['test'=> 3, 'user'=>5]
        return array_combine($this->parameters, array_slice($arguments, 1));
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
}
