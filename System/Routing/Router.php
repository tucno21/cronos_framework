<?php

namespace Cronos\Routing;

use Closure;
use Cronos\Routing\Route;
use Cronos\Http\HttpMethod;
use Cronos\Http\Request;
use Cronos\Errors\HttpNotFoundException;
use Cronos\Container\DependencyInjection;


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

    public function resolve(Request $request)
    {
        //obtener la instancia de la clase Route dependiendo de la uri y el metodo http
        $route = $this->resolveRoute($request);

        //obtener la accion de la instancia de la clase Route
        $action = $route->action();

        //verificar si la accion es un array
        if (is_array($action)) {
            //instanciar el controlador
            $controller = new $action[0];
            $action[0] = $controller;
        }

        //obtener los parametros que se declara en la funcion o metodo de la ruta del framework
        $params = DependencyInjection::resolveParameters($action, $route->parseParameters($request->uri()));

        //ejecutar la accion sea una funcion o una clase y sus parametros
        return call_user_func($action, ...$params);
    }

    public function resolveRoute(Request $request)
    {
        //obtener el metodo http de la peticion de la clase Request
        //y recorrer el array de rutas dependiendo del metodo http
        foreach ($this->routes[$request->method()->value] as $route) {
            //verificar si la ruta del framework coincide con la uri que viene de la peticion
            if ($route->matches($request->uri())) {
                //retornamos el objeto class Route
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
