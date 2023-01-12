<?php

namespace Cronos;

use Throwable;
use Cronos\View\View;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Routing\Router;
use Cronos\View\CronosEngine;
use Cronos\Container\Container;
use Cronos\Errors\HttpNotFoundException;

class App
{
    public static string $root;

    public Router $router;

    public Request $request;

    public Response $response;

    public static function bootstrap(string $root)
    {
        //obtenemos la ruta del proyecto framework
        self::$root = $root;

        //obtenemos la instancia de la clase App y almacenamos en el contenedor
        $app = Container::singleton(self::class);

        //retornamos la instancia de la clase App y ejecutamos el metodo runServiceProvider
        return $app
            ->setHttpConnection()
            ->runServiceProvider("web");
    }

    protected function runServiceProvider(string $type): self
    {
        //obtenemos la ruta del archivo routes/web.php
        require_once self::$root . "/routes/$type.php";

        return $this;
    }


    protected function setHttpConnection(): self
    {
        //instanciamos la clase Router y almacenamos en la propiedad router
        $this->router = new Router();

        //instanciamos la clase Request y almacenamos en la propiedad request
        $this->request = Container::singleton(Request::class);

        //instanciamos la clase Response y almacenamos en la propiedad response
        $this->response = Container::singleton(Response::class);

        Container::singleton(
            View::class,
            fn () => new CronosEngine(__DIR__ . '/../resources/views')
        );

        return $this;
    }

    public function run()
    {
        try {

            $response = $this->router->resolve($this->request);
            if (!is_null($response)) {
                //se ejecuta solo si es una instancia de la clase Response
                $this->response->sendResponse($response);
            }
        } catch (HttpNotFoundException $e) {
            $response = view('error/404');
            $this->abort($response->setStatusCode(404));
        } catch (Throwable $e) {
            $response = json([
                "Type error" => $e::class,
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTrace(),
                "TraceAsString" => $e->getTraceAsString(),
            ]);
            $this->abort($response->setStatusCode(500));
        }
    }

    public function abort(Response $response)
    {
        $this->terminate($response);
    }

    public function terminate(Response $response)
    {
        $this->response->sendResponse($response);
        exit();
    }
}
