<?php

namespace Cronos\Routing;

use Closure;
use Cronos\Http\HttpMethod;

class Router
{
    protected array $routes = [];

    public function __construct()
    {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }

    public function resolve(string $uri, string $method)
    {
        dd($this->routes);
        $method = strtoupper($method);
        if (array_key_exists($uri, $this->routes[$method])) {
            return $this->routes[$method][$uri];
        }
        return null;
    }

    public function get(string $uri, Closure|array $action)
    {
        $this->routes[HttpMethod::GET->value][$uri] = $action;
    }

    public function post(string $uri, Closure|array $action)
    {
        $this->routes[HttpMethod::POST->value][$uri] = $action;
    }

    public function put(string $uri, Closure|array $action)
    {
        $this->routes[HttpMethod::PUT->value][$uri] = $action;
    }

    public function patch(string $uri, Closure|array $action)
    {
        $this->routes[HttpMethod::PATCH->value][$uri] = $action;
    }

    public function delete(string $uri, Closure|array $action)
    {
        $this->routes[HttpMethod::DELETE->value][$uri] = $action;
    }
}
