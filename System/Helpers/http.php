<?php

use Cronos\Http\Response;

function json(array|object $data, int $statusCode = 200): Response
{
    return Response::json($data, $statusCode);
}

function redirect(string $url): Response
{
    return Response::redirect($url);
}

function view(string $viewName, array $params = [], string $layout = null): Response
{
    return Response::view($viewName, $params, $layout);
}
