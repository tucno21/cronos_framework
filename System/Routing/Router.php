<?php

namespace Cronos\Routing;

use Closure;
use Cronos\Routing\Route;
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
        //obtener la instancia de la clase Route dependiendo de la uri y el metodo http
        $route = $this->resolveRoute($uri, $method);

        //obtener la accion de la instancia de la clase Route
        $action = $route->action();

        //verificar si la accion es un array
        if (is_array($action)) {
            //instanciar el controlador
            $controller = new $action[0];
            $action[0] = $controller;
        }

        //ejecutar la accion sea una funcion o una clase
        return call_user_func($action);
    }

    public function resolveRoute(string $uri, string $method)
    {
        //recorrer el array de rutas dependiendo del metodo http
        foreach ($this->routes[$method] as $route) {
            //verificar si la ruta coincide con la uri que viene de la peticion
            //$route es una instancia de la clase Route
            if ($route->matches($uri)) {
                //retornar la Route especifico
                return $route;
            }
        }

        //si no existe la ruta lanzar una excepcion
        throw new HttpNotFoundException();
    }

    protected function registerRoute(HttpMethod $method, string $uri, Closure|array $action): Route
    {
        //instanciar route y se lo asignamos a la propiedad routes
        $route = new Route($uri, $action);
        //almacenamos la instancia de la clase Route en la propiedad routes
        $this->routes[$method->value][] = $route;
        //retornar la route
        return $route;
    }

    public function get(string $uri, Closure|array $action): Route
    {
        //registrar la ruta del framework a la propiedad routes
        return $this->registerRoute(HttpMethod::GET, $uri, $action);
    }

    public function post(string $uri, Closure|array $action): Route
    {
        //registrar la ruta del framework a la propiedad routes
        return $this->registerRoute(HttpMethod::POST, $uri, $action);
    }

    public function put(string $uri, Closure|array $action): Route
    {
        //registrar la ruta del framework a la propiedad routes
        return $this->registerRoute(HttpMethod::PUT, $uri, $action);
    }

    public function patch(string $uri, Closure|array $action): Route
    {
        //registrar la ruta del framework a la propiedad routes
        return $this->registerRoute(HttpMethod::PATCH, $uri, $action);
    }

    public function delete(string $uri, Closure|array $action): Route
    {
        //registrar la ruta del framework a la propiedad routes
        return $this->registerRoute(HttpMethod::DELETE, $uri, $action);
    }
}
