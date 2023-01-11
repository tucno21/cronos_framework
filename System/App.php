<?php

namespace Cronos;

use Cronos\Routing\Router;
use Cronos\Container\Container;

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
        //ejecutar el archivo web.php donde esta las rutas
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
        echo 'Hello World';
    }
}
