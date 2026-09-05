<?php

declare(strict_types=1);

namespace Cronos\Testing;

use Closure;
use Throwable;
use Cronos\App;
use Cronos\Container\Container;
use Cronos\Errors\ExceptionHandler;
use Cronos\Http\MiddlewareGroup;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Routing\Route;
use Cronos\Routing\Router;

/**
 * Trait para ejecutar peticiones HTTP sintéticas y funcionales en memoria
 * a través de Router::resolve($request), ejecutando middlewares, DI,
 * validaciones y controladores sin necesidad de servidor web externo.
 */
trait MakesHttpRequests
{
    /**
     * Cabeceras por defecto para cada petición del test.
     */
    protected array $defaultHeaders = [];

    /**
     * Define una cabecera para las siguientes peticiones.
     */
    public function withHeader(string $name, string $value): static
    {
        $this->defaultHeaders[$name] = $value;
        return $this;
    }

    /**
     * Define múltiples cabeceras para las siguientes peticiones.
     */
    public function withHeaders(array $headers): static
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Agrega el token de autorización Bearer a las siguientes peticiones.
     */
    public function withToken(string $token, string $type = 'Bearer'): static
    {
        return $this->withHeader('Authorization', trim($type . ' ' . $token));
    }

    /**
     * Ejecuta una petición GET.
     */
    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], [], [], $this->prepareServerVars($headers));
    }

    /**
     * Ejecuta una petición POST.
     */
    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $data, [], [], $this->prepareServerVars($headers));
    }

    /**
     * Ejecuta una petición PUT.
     */
    public function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $data, [], [], $this->prepareServerVars($headers));
    }

    /**
     * Ejecuta una petición PATCH.
     */
    public function patch(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PATCH', $uri, $data, [], [], $this->prepareServerVars($headers));
    }

    /**
     * Ejecuta una petición DELETE.
     */
    public function delete(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, $data, [], [], $this->prepareServerVars($headers));
    }

    /**
     * Ejecuta una petición GET esperando JSON.
     */
    public function getJson(string $uri, array $headers = []): TestResponse
    {
        return $this->jsonRequest('GET', $uri, [], $headers);
    }

    /**
     * Ejecuta una petición POST enviando y esperando JSON.
     */
    public function postJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonRequest('POST', $uri, $data, $headers);
    }

    /**
     * Ejecuta una petición PUT enviando y esperando JSON.
     */
    public function putJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonRequest('PUT', $uri, $data, $headers);
    }

    /**
     * Ejecuta una petición PATCH enviando y esperando JSON.
     */
    public function patchJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonRequest('PATCH', $uri, $data, $headers);
    }

    /**
     * Ejecuta una petición DELETE enviando y esperando JSON.
     */
    public function deleteJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonRequest('DELETE', $uri, $data, $headers);
    }

    /**
     * Helper para peticiones JSON con cabeceras Accept y Content-Type fijadas.
     */
    public function jsonRequest(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $content = !empty($data) ? json_encode($data) : null;

        return $this->call(
            $method,
            $uri,
            $data,
            [],
            [],
            $this->prepareServerVars($headers),
            $content
        );
    }

    /**
     * Despacha una petición sintética a través del Router y Pipeline del framework.
     */
    public function call(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ): TestResponse {
        $this->ensureHttpTestEnvironmentReady();

        $serverVars = array_merge($this->defaultHeaders, $server);
        $this->defaultHeaders = [];

        $request = Request::create(
            $uri,
            $method,
            $parameters,
            $cookies,
            $files,
            $serverVars,
            $content
        );

        // Registrar request actual en el contenedor para DI y FormRequests
        Container::instance(Request::class, $request);

        $router = Container::resolve(Router::class);

        try {
            $result = $router->resolve($request);

            if ($result instanceof Response) {
                $response = $result;
            } elseif (is_array($result) || is_object($result)) {
                $response = Response::json($result);
            } else {
                $response = Response::text((string) $result);
            }
        } catch (Throwable $e) {
            $handler = new ExceptionHandler();
            $response = $handler->handle($e);
        }

        return new TestResponse($response);
    }

    /**
     * Garantiza que el Router tenga las rutas cargadas y los middlewares inicializados.
     */
    protected function ensureHttpTestEnvironmentReady(): void
    {
        $expectedRoot = dirname(__DIR__, 2);
        if (!isset(App::$root) || empty(App::$root) || !is_dir(App::$root . '/routes')) {
            App::$root = $expectedRoot;
        }

        // Asegurar que exista una instancia de Router en el contenedor
        if (!Container::has(Router::class)) {
            $router = Container::singleton(Router::class, fn() => new Router());
        } else {
            $router = Container::resolve(Router::class);
        }

        // Asegurar que exista una instancia de App en el contenedor con router inicializado
        if (!Container::has(App::class)) {
            $app = Container::singleton(App::class, fn() => new App());
            $app->router = $router;
        } else {
            $app = Container::resolve(App::class);
            if ($app instanceof App && !isset($app->router)) {
                $app->router = $router;
            }
        }

        // Cargar middlewares globales si aún no están cargados
        if (empty(MiddlewareGroup::getGlobalMiddlewares())) {
            MiddlewareGroup::loadFromConfig();
        }

        // Para evitar contaminación de estado con tests unitarios previos que instancien routers vacíos
        // o con rutas dummy, aseguramos que las rutas de la aplicación estén cargadas.
        $needsRoutes = empty($router->routes['GET']) && empty($router->routes['POST']);
        // O si no contiene al menos una ruta clave de la app como /api/blogs o /api/register
        if (!$needsRoutes) {
            $hasAppRoute = isset($router->routes['GET']['/api/blogs']) || isset($router->routes['POST']['/api/register']);
            if (!$hasAppRoute) {
                $needsRoutes = true;
            }
        }

        if ($needsRoutes) {
            $router = new Router();
            Container::instance(Router::class, $router);
            if ($app instanceof App) {
                $app->router = $router;
            }
            $routesDir = App::$root . '/routes';
            if (is_dir($routesDir)) {
                Route::load($routesDir);
            }
        }
    }

    /**
     * Combina las cabeceras por defecto con las específicas de la petición.
     */
    protected function prepareServerVars(array $headers): array
    {
        return array_merge($this->defaultHeaders, $headers);
    }
}
