<?php

use Cronos\Http\Response;

function json(array $data): Response
{
    return Response::json($data);
}

function redirect(string $url): Response
{
    return Response::redirect($url);
}

function view(string $viewName, array $params = [], string $layout = null): Response
{
    return Response::view($viewName, $params, $layout);
}
