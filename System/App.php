<?php

namespace Cronos;

use Throwable;
use Cronos\Routing\Router;
use Cronos\Container\Container;
use Cronos\Errors\HttpNotFoundException;

class App
{
    public static string $root;

    public Router $router;

    public static function bootstrap(string $root)
    {
        self::$root = $root;

        $app = Container::singleton(self::class);

        return $app
            // ->setHttpConnection()
            ->runServiceProvider("web");
    }

    protected function runServiceProvider(string $type): self
    {
        $this->router = new Router();
        $app = $this->router;
        require_once self::$root . "/routes/$type.php";

        return $this;
    }


    protected function setHttpConnection(): self
    {
        //obtenemos la instancia de la clase Router
        $this->router = new Router();

        return $this;
    }

    public function run()
    {
        try {
            $this->router->resolve($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
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
