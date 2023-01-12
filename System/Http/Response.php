<?php

namespace Cronos\Http;

use Cronos\View\View;
use Cronos\Container\Container;

class Response
{
    //iniciamos con el codigo de respuesta 200
    protected int $statusCode = 200;

    //almacenamos la cabecera
    protected array $headers = [];

    //almacenamos el contenido de la respuesta
    protected ?string $content = null;

    public function statusCode(): int
    {
        //retornamos es codigo de respuesta
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        //asignamos el codigo de respuesta
        $this->statusCode = $statusCode;
        return $this;
    }

    public function headers(string $key = null): array|string|null
    {
        //retornamos la cabecera
        if (is_null($key)) {
            return $this->headers;
        }
        return $this->headers[strtolower($key)] ?? null;
    }

    public function setHeaders(string $header, string $value): self
    {
        //asignamos la cabecera con su valor
        $this->headers[strtolower($header)] = $value;
        return $this;
    }

    public function removeHeader(string $header): void
    {
        //eliminamos la cabecera
        unset($this->headers[strtolower($header)]);
    }

    public function setContentType(string $value): self
    {
        //asignamos el tipo de contenido
        $this->setHeaders("Content-Type", $value);
        return $this;
    }

    public function content(): ?string
    {
        //retornamos el contenido
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        //asignamos el contenido
        $this->content = $content;
        return $this;
    }

    public function prepare(): void
    {
        //preparamos la respuesta
        if (is_null($this->content)) {
            //si no hay contenido eliminamos la cabecera
            $this->removeHeader("Content-Type");
            $this->removeHeader("Content-Length");
        } else {
            //si hay contenido asignamos la cabecera
            $this->setHeaders("Content-Length", (string) strlen($this->content));
        }
    }

    public static function json(array|object $data, int $statusCode = 200): self
    {
        return (new self())
            ->setContentType("application/json")
            ->setStatusCode($statusCode)
            ->setContent(json_encode($data));
    }

    public static function text(string $text): self
    {
        return (new self())
            ->setContentType("text/plain")
            ->setContent($text);
    }

    public static function redirect(string $url, int $statusCode = 200): self
    {
        return (new self())
            ->setStatusCode($statusCode)
            ->setHeaders("Location", $url);
    }

    public static function view(string $viewName, array $params = [], string $layout = null): self
    {
        $content = app(View::class)->render($viewName, $params, $layout);

        return (new self())
            ->setContentType("text/html")
            ->setContent($content);
    }

    //ejectuamos la respuestas que hemos preparado
    public function sendResponse(Response $response)
    {
        header("Content-Type: None"); //cabiamos la cabecera por a defecto none
        header_remove("Content-Type"); //eliminamos la cabecera por defecto

        $response->prepare();
        http_response_code($response->statusCode());

        foreach ($response->headers() as $header => $value) {
            header("{$header}: {$value}");
        }

        print($response->content());
    }
}
