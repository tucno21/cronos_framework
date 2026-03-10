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

function cacheDirectory()
{
    return App::$root . '/storage/cache';
}

function env(string $variable, $default = null)
{
    //obtener el valor de una variable de entorno del archivo .env
    return $_ENV[$variable] ?? $default;
}

function resource_path(string $path = '')
{
    return App::$root . '/resources' . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Aborta la ejecución y lanza una HttpException
 * 
 * @param int $code Código de estado HTTP
 * @param string $message Mensaje de error
 * @return void
 */
function abort(int $code, string $message = ''): void
{
    if (empty($message)) {
        $message = match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'An error occurred'
        };
    }

    throw new \Cronos\Errors\HttpException($code, $message);
}

/**
 * Aborta la ejecución si la condición es verdadera
 * 
 * @param bool $condition Condición a evaluar
 * @param int $code Código de estado HTTP
 * @param string $message Mensaje de error
 * @return void
 */
function abort_if(bool $condition, int $code, string $message = ''): void
{
    if ($condition) {
        abort($code, $message);
    }
}

/**
 * Retorna la clase Logger para acceso a métodos estáticos
 * 
 * @return string
 */
function logger(): string
{
    return \Cronos\Log\Logger::class;
}
