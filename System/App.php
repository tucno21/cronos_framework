<?php

namespace Cronos;

use Cronos\Container\Container;

class App
{
    public static string $root;
    public static function bootstrap(string $root)
    {
        self::$root = $root;

        $app = Container::singleton(self::class);

        return $app;
    }

    public function run()
    {
        echo 'Hello World';
    }
}
