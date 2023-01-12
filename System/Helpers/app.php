<?php

use Cronos\App;
use Cronos\Container\Container;

function app(string $class = App::class)
{
    return Container::resolve($class);
}

function singleton(string $class, string|callable|null $build = null)
{
    Container::singleton($class, $build);
}
