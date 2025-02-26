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

    protected static array $groupStack = [];

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

    public static function group(array $attributes, callable $callback): void
    {
        // Guardamos los atributos del grupo actual
        self::$groupStack[] = $attributes;

        // Ejecutamos las rutas dentro del grupo
        $callback();

        // Removemos los atributos del grupo actual
        array_pop(self::$groupStack);
    }

    protected static function getGroupAttributes(): array
    {
        // Si no hay grupos, retornamos un array vacío
        if (empty(self::$groupStack)) {
            return [
                'prefix' => '',
                'middleware' => []
            ];
        }

        // Combinamos todos los atributos de los grupos
        $result = [
            'prefix' => '',
            'middleware' => []
        ];

        foreach (self::$groupStack as $group) {
            // Concatenamos los prefijos
            if (isset($group['prefix'])) {
                $result['prefix'] .= '/' . trim($group['prefix'], '/');
            }

            // Agregamos los middlewares
            if (isset($group['middleware'])) {
                $middlewares = is_array($group['middleware'])
                    ? $group['middleware']
                    : [$group['middleware']];
                $result['middleware'] = array_merge($result['middleware'], $middlewares);
            }
        }

        return $result;
    }

    public static function get(string $uri, Closure|array $action): Route
    {
        $groupAttributes = self::getGroupAttributes();
        $prefixedUri = $groupAttributes['prefix'] . '/' . trim($uri, '/');
        $route = Container::resolve(App::class)->router->get($prefixedUri, $action);

        // Aplicar middlewares del grupo si existen
        if (!empty($groupAttributes['middleware'])) {
            $route->middleware($groupAttributes['middleware']);
        }

        return $route;
    }

    public static function post(string $uri, Closure|array $action): Route
    {
        $groupAttributes = self::getGroupAttributes();
        $prefixedUri = $groupAttributes['prefix'] . '/' . trim($uri, '/');
        $route = Container::resolve(App::class)->router->post($prefixedUri, $action);

        if (!empty($groupAttributes['middleware'])) {
            $route->middleware($groupAttributes['middleware']);
        }

        return $route;
    }

    public static function put(string $uri, Closure|array $action): Route
    {
        $groupAttributes = self::getGroupAttributes();
        $prefixedUri = $groupAttributes['prefix'] . '/' . trim($uri, '/');
        $route = Container::resolve(App::class)->router->put($prefixedUri, $action);

        if (!empty($groupAttributes['middleware'])) {
            $route->middleware($groupAttributes['middleware']);
        }

        return $route;
    }

    public static function patch(string $uri, Closure|array $action): Route
    {
        $groupAttributes = self::getGroupAttributes();
        $prefixedUri = $groupAttributes['prefix'] . '/' . trim($uri, '/');
        $route = Container::resolve(App::class)->router->patch($prefixedUri, $action);

        if (!empty($groupAttributes['middleware'])) {
            $route->middleware($groupAttributes['middleware']);
        }

        return $route;
    }

    public static function delete(string $uri, Closure|array $action): Route
    {
        $groupAttributes = self::getGroupAttributes();
        $prefixedUri = $groupAttributes['prefix'] . '/' . trim($uri, '/');
        $route = Container::resolve(App::class)->router->delete($prefixedUri, $action);

        if (!empty($groupAttributes['middleware'])) {
            $route->middleware($groupAttributes['middleware']);
        }

        return $route;
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
        $this->middlewares = array_map(fn($middleware) => new $middleware, $middlewares);

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
        // Recorre todos los archivos PHP en la carpeta $routesDirectory
        foreach (glob("$routesDirectory/*.php") as $routesFile) {
            // Verifica si el archivo es api.php
            $isApiFile = basename($routesFile) === 'api.php';

            // Si es api.php, establece un prefijo global temporal
            if ($isApiFile) {
                Container::resolve(App::class)->router->setPrefix('/api');
            }

            // Carga el archivo de rutas
            require_once $routesFile;

            // Limpia el prefijo después de cargar el archivo api.php
            if ($isApiFile) {
                Container::resolve(App::class)->router->clearPrefix();
            }
        }
    }
}
