<?php

use Cronos\App;
use Cronos\Config\Config;
use Cronos\Container\Container;

function app(string $class = App::class)
{
    return Container::resolve($class);
}

function singleton(string $class, string|callable|null $build = null)
{
    Container::singleton($class, $build);
}

function configGet(string $configuration, $default = null)
{
    return Config::get($configuration, $default);
}

function resourcesDirectory()
{
    return App::$root . '/resources';
}

function env(string $variable, $default = null)
{
    //obtener el valor de una variable de entorno del archivo .env
    return $_ENV[$variable] ?? $default;
}
