<?php

namespace Cronos\Routing;

use Closure;
use Cronos\Http\HttpMethod;
use Cronos\Errors\HttpNotFoundException;


class Router
{
    protected array $routes = [];

    public function __construct()
    {
        //agregar los metodos http a la propiedad routes
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }

    public function resolve(string $uri, string $method)
    {
        $method = strtoupper($method);
        if (array_key_exists($uri, $this->routes[$method])) {
            $action = $this->routes[$method][$uri];
            if ($action instanceof Closure) {
                return $action();
            }
            if (is_array($action)) {
                $controller = new $action[0]();
                return $controller->{$action[1]}();
            }
        }

        throw new HttpNotFoundException();
    }

    public function get(string $uri, Closure|array $action)
    {
        //registrar la ruta del framework a la propiedad routes
        $this->routes[HttpMethod::GET->value][$uri] = $action;
    }

    public function post(string $uri, Closure|array $action)
    {
        //registrar la ruta del framework a la propiedad routes
        $this->routes[HttpMethod::POST->value][$uri] = $action;
    }

    public function put(string $uri, Closure|array $action)
    {
        //registrar la ruta del framework a la propiedad routes
        $this->routes[HttpMethod::PUT->value][$uri] = $action;
    }

    public function patch(string $uri, Closure|array $action)
    {
        //registrar la ruta del framework a la propiedad routes
        $this->routes[HttpMethod::PATCH->value][$uri] = $action;
    }

    public function delete(string $uri, Closure|array $action)
    {
        //registrar la ruta del framework a la propiedad routes
        $this->routes[HttpMethod::DELETE->value][$uri] = $action;
    }
}
