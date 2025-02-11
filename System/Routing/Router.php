<?php

namespace Cronos\Routing;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Routing\Route;
use Cronos\Http\HttpMethod;
use Cronos\Container\Container;
use Cronos\Errors\RouteException;
use Cronos\Errors\HttpNotFoundException;
use Cronos\Container\DependencyInjection;


class Router
{
    public array $routes = [];

    public array $nameUrl = [];
    public array $nameRoute = [];

    protected string $prefix = '';

    public function __construct()
    {
        //agregar los metodos http a la propiedad routes
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function clearPrefix(): void
    {
        $this->prefix = '';
    }

    protected function applyPrefix(string $uri): string
    {
        return $this->prefix . $uri;
    }

    public function resolve(Request $request): mixed
    {
        //obtener la instancia de la clase Route dependiendo de la uri y el metodo http
        $route = $this->resolveRoute($request);

        //obtener la accion de la instancia de la clase Route
        $action = $route->action();

        $middlewares = $route->middlewares();

        //verificar si la accion es un array
        if (is_array($action)) {
            //instanciar el controlador
            $controller = new $action[0];
            $action[0] = $controller;
            //obtener los middlewares del controlador y unirlos con los middlewares de la ruta
            $middlewares = array_merge($middlewares, $controller->middlewares());
        }

        //obtener los parametros que se declara en la funcion o metodo de la ruta del framework
        $params = DependencyInjection::resolveParameters($action, $route->parseParameters($request->uri()));

        //primero se ejecutan los middlewares y despues la accion
        //primero se ejecutan los middlewares de la ruta y luego los del controlador
        //ejecutar la accion sea una funcion o una clase y sus parametros
        // return call_user_func($action, ...$params);
        return $this->runMiddlewares($request, $middlewares, fn() => call_user_func($action, ...$params));
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

    protected function runMiddlewares(Request $request, array $middlewares, $target): Response
    {
        if (count($middlewares) === 0) {
            //ejecutamos los controladores
            return $target();
        }

        //ejecutamos el primer middleware y le pasamos el request y la funcion que se ejecutara  que esta en el objeto Route declarado en la linea web.php
        return $middlewares[0]->handle($request, fn($request) => $this->runMiddlewares($request, array_slice($middlewares, 1), $target));
    }

    protected function registerRoute(HttpMethod $method, string $uri, Closure|array $action): Route
    {
        // Aplica el prefijo a la URI antes de registrar la ruta
        $prefixedUri = $this->applyPrefix($uri);

        // Instancia la ruta con la URI prefijada
        $route = new Route($prefixedUri, $action);

        // Almacena la instancia de la clase Route en la propiedad routes
        $this->routes[$method->value][] = $route;

        // Retorna la ruta
        return $route;
    }

    public function get(string $uri, Closure|array $action): Route
    {
        array_push($this->nameUrl, $uri);
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

    public function name(string $name)
    {
        foreach ($this->nameUrl as $key => $value) {
            $this->nameRoute[$name] = $value;
        }
    }

    public function route(string $nameRoute, string|array $params = null)
    {
        if (isset($this->nameRoute[$nameRoute])) {
            //comprobar si $nameRoute tiene llaves {} para agregarle los parametros
            if (!strpos($this->nameRoute[$nameRoute], '{')) {
                if ($params === null) {
                    return base_url . $this->nameRoute[$nameRoute];
                }
                return base_url . $this->nameRoute[$nameRoute] . '/' . $params;
            }

            //user/{user}/products/{id}
            $pre_url = preg_replace('/\{([a-zA-Z]+)(?::([a-zA-Z]+))?\}/', 'cronosXcronos', $this->nameRoute[$nameRoute]);
            //user/cronosXcronos/products/cronosXcronos

            if ($params === null) {
                throw new RouteException('La ruta ' . $nameRoute . ' requiere parametros');
            }


            if (is_string($params)) {
                $params = [$params];
            }

            //contar cuantos cronosXcronos hay en la $pre_url
            $count = substr_count($pre_url, 'cronosXcronos');
            //contar cuantos parametros hay en el array $params
            $countParams = count($params);

            //comprobar si los parametros son iguales a los cronosXcronos
            if ($count !== $countParams) {
                throw new RouteException('La ruta ' . $nameRoute . ' requiere ' . $count . ' parametros');
            }

            //separar $keys por / en un array
            $keys = explode('/', $pre_url);
            //buscar cronosXcronos en el array $keys
            foreach ($keys as $key => $value) {
                if ($value === 'cronosXcronos') {
                    //reemplazar cronosXcronos por el valor de $params
                    $keys[$key] = array_shift($params);
                }
            }

            //unir el array $keys por /
            $url = implode('/', $keys);
            return base_url . $url;
        }

        throw new RouteException('La ruta ' . $nameRoute . ' no existe');
    }
}
