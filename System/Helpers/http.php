<?php

use Cronos\Http\Response;
use Cronos\Routing\Router;
use Cronos\Container\Container;

function json(array|object $data, int $statusCode = 200): Response
{
    return Response::json($data, $statusCode);
}

function redirect(string $url = null): Response
{
    return Response::redirect($url);
}

function view(string $viewName, array $params = [], string $layout = null): Response
{
    return Response::view($viewName, $params, $layout);
}


if (!function_exists('route')) {
    /**
     * funcion para redireccionar a otra web usando el nombre de la ruta
     */
    function route(string $nameRoute, string|array $params = null)
    {
        return Container::resolve(Router::class)->route($nameRoute, $params);
    }
}
