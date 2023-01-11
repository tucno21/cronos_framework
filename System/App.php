<?php

namespace Cronos;

use Throwable;
use Cronos\Http\Request;
use Cronos\Routing\Router;
use Cronos\Container\Container;
use Cronos\Errors\HttpNotFoundException;
use Cronos\Http\Response;

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
            echo 'no existe la ruta Error: 404';
            echo '<br>';
            echo '<br>';
            echo $e->getFile();
            echo '<br>';
            echo '<br>';
            echo $e->getLine();
        } catch (Throwable $e) {
            echo $e::class;
            echo '<br>';
            echo '<br>';
            echo $e->getMessage();
            echo '<br>';
            echo '<br>';
            echo $e->getTraceAsString();
            echo '<br>';
            echo '<br>';
            echo $e->getFile();
            echo '<br>';
            echo '<br>';
            echo $e->getLine();
        }
    }
}
