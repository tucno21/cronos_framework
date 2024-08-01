<?php

namespace Cronos\Http;

use Cronos\View\View;
use Cronos\Container\Container;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected array $cookies = [];
    protected ?string $content = null;

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function headers(string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->headers;
        }
        return $this->headers[strtolower($key)] ?? null;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function removeHeader(string $header): void
    {
        unset($this->headers[$header]);
    }

    public function cookies(string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->cookies;
        }
        return $this->cookies[strtolower($key)] ?? null;
    }

    public function setCookies(string $name, string $value, array $options = []): self
    {
        //array de opciones predeterminadas de la cookie
        $defaults = [
            "expires" => 0, //tiempo de expiración de la cookie en segundos
            "path" => "/", //ruta de la cookie
            "domain" => "", //dominio de la cookie
            "secure" => true, //si la cookie solo se transmite a través de una conexión segura HTTPS
            "httponly" => true, //si la cookie solo se puede acceder a través del protocolo HTTP
            "samesite" => "none", //si la cookie solo se puede enviar con solicitudes de origen cruzado
        ];

        //unimos las opciones predeterminadas con las opciones que se pasan
        $options = array_merge($defaults, $options);

        //asignamos la cookie
        $this->cookies[$name] = [
            'value' => $value,
            'expires' => $options['expires'],
            'path' => $options['path'],
            'domain' => $options['domain'],
            'secure' => $options['secure'],
            'httponly' => $options['httponly'],
            'samesite' => $options['samesite'],
        ];
        return $this;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setContentType(string $value): self
    {
        $this->setHeader("Content-Type", $value);
        return $this;
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        // Asegúrate de que los datos están en UTF-8
        $data = self::utf8ize($data);

        $json = new JsonResponse($data);

        return (new self())
            ->setContentType("application/json")
            ->setStatusCode($statusCode)
            ->setContent(json_encode($json->getData()));
    }

    public static function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::utf8ize($value);
            }
        } elseif (is_object($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed->$key = self::utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', mb_detect_encoding($mixed, 'UTF-8, ISO-8859-1', true));
        }

        return $mixed;
    }

    public static function text(string $text): self
    {
        return (new self())
            ->setContentType("text/plain")
            ->setContent($text);
    }

    public static function redirect(string $url = null, int $statusCode = 200): self
    {
        if (is_null($url)) {
            return (new self());
        } else {
            return (new self())
                ->setStatusCode($statusCode)
                ->setHeader("Location", $url);
        }
    }

    public static function route(string $nameRoute): self
    {
        $route = route($nameRoute);
        // eliminar http:// o https://
        // $route = preg_replace('/^http(s)?:\/\//', '', $route);
        // // obtener el host
        // $host = $_SERVER['HTTP_HOST'];
        // // eliminar el host
        // $route = preg_replace('/^' . $host . '/', '', $route);

        return (new self())
            ->setStatusCode(200)
            ->setHeader("Location", $route);
    }

    public static function back(): self
    {
        $sesionAnterior = session()->get('_cronos_previous_path');

        return (new self())
            ->setStatusCode(200)
            ->setHeader("Location", $sesionAnterior['new']);
        // ->setHeader("Location", $_SERVER['HTTP_REFERER']);
    }

    public function with(string $key, $value, int $statusCode = 400): self
    {
        $this->setStatusCode($statusCode);
        session()->flash($key, $value);
        return $this;
    }

    public function withErrors(array|object $dataInput, array|object $errors, int $statusCode = 400): self
    {
        $this->setStatusCode($statusCode);
        session()->setErrorsInputs($dataInput, $errors);
        return $this;
    }

    public static function view(string $viewName, array $params = [], string $layout = null): self
    {
        $content = app(View::class)->render($viewName, $params, $layout);

        return (new self())
            ->setContentType("text/html")
            ->setContent($content);
    }

    public function prepare(): void
    {
        if (is_null($this->content)) {
            $this->removeHeader("Content-Type");
            $this->removeHeader("Content-Length");
        } else {
            $this->setHeader("Content-Length", (string) strlen($this->content));
        }
    }

    //ejectuamos la respuestas que hemos preparado
    public function sendResponse(Response $response)
    {
        if (!configGet('cors.supports_credentials')) {
            header('Access-Control-Allow-Origin: *');
        } else {
            //obtener dominio del origen de la solicitud
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            //comprobar si el dominio del origen de la solicitud está en la lista de dominios permitidos
            if (in_array($origin, configGet('cors.allowed_origins_patterns'))) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Credentials: true");
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit();
            }
        }

        //establecer cors que encabezados se pueden exponer para javascript en el cliente
        $exposed_headers = configGet('cors.exposed_headers');
        if ($exposed_headers !== null && $exposed_headers !== []) {
            //['*']
            $origins = implode(', ', $exposed_headers);
            header("Access-Control-Expose-Headers: $origins");
        }

        // header("Content-Type: None"); //cabiamos la cabecera por a defecto none
        // header_remove("Content-Type"); //eliminamos la cabecera por defecto


        //enviamos las cabeceras
        $response->prepare();

        //establecemos el codigo de estado
        http_response_code($response->statusCode());

        //establecemos las cabeceras
        foreach ($response->headers() as $name => $value) {
            header("{$name}: {$value}");
        }

        //establecemos las cookies
        foreach ($response->cookies() as $name => $cookie) {
            $options = [
                'expires' => $cookie['expires'],
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => $cookie['samesite'],
            ];

            setcookie($name, $cookie['value'], $options);
        }

        //enviamos el contenido
        print($response->content());
    }
}
