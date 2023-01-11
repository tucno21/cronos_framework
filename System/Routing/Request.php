<?php

namespace Cronos\Routing;

use Cronos\Http\HttpMethod;

class Request
{

    protected string $uri;

    // protected Route $route;

    protected HttpMethod $method;

    protected array $dataPost;

    protected array $dataGet;

    protected array $headers = [];

    public function __construct()
    {
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = HttpMethod::from($_SERVER['REQUEST_METHOD']);
        $this->dataPost = $_POST;
        $this->dataGet =  $_GET;
        $this->headers = getallheaders();
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function method(): HttpMethod
    {
        return $this->method;
    }

    public function dataPost(): array
    {
        return $this->dataPost;
    }

    public function dataGet(): array
    {
        return $this->dataGet;
    }
}
