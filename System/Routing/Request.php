<?php

namespace Cronos\Routing;

use Cronos\Http\HttpMethod;

class Request
{
    //la uri que se esta solicitando de la web
    protected string $uri;

    //el metodo que se esta solicitando de la web
    protected HttpMethod $method;

    //los datos que se envian por post, put, patch, delete
    protected array $data;

    //los datos que se envian por get
    protected array|object $dataGet;

    protected array $headers = [];

    public function __construct()
    {
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = HttpMethod::from($_SERVER['REQUEST_METHOD']);
        $this->data = $this->postPutPatchDelete();
        $this->dataGet =  $this->get();
        $this->headers = getallheaders();
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function patch(): string
    {
        return $this->uri;
    }

    public function method(): HttpMethod
    {
        return $this->method;
    }

    protected function postPutPatchDelete(): array
    {
        //obtener los headers cabezera
        $headers = getallheaders();
        //verificar si el header es de tipo json
        $isJSON = isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';

        if ($this->method->value === 'POST' && !$isJSON) {
            //si es post y no es json (de tipo form-data)
            $data = $_POST;
            $this->setPropertiesSelf($data);
            return $data;
        }

        if ($isJSON) {
            //si es post, put, patch, delete y es json (de tipo application/json)
            $data = json_decode(file_get_contents('php://input'), true);
            $this->setPropertiesSelf($data);
        } else {
            //parse_str convierte los datos de tipo string a un array
            parse_str(file_get_contents('php://input'), $data);
        }

        return $data;
    }

    protected function get(): array
    {
        $data = $_GET;
        //serializamos los datos para que se puedan convertir en propiedades de esta clase
        if (!empty($data)) {
            $this->setPropertiesSelf($data);
        }

        return $data;
    }

    //convertit en propiedades de esta clase
    protected function setPropertiesSelf($data): self
    {
        //convertimos los datos en propiedades de esta clase
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function all(): object
    {

        //envia todos los datos en un objeto
        if ($this->method->value === 'GET') {
            $dataGet = $this->dataGet;
            $dataGet = (object)$dataGet;
            return $dataGet;
        } else {
            $data = $this->data;
            $data = (object)$data;
            return $data;
        }
    }

    public function input(string $key): string
    {
        //envia un dato en especifico
        if ($this->method->value === 'GET') {
            return $this->dataGet[$key];
        } else {
            return $this->data[$key];
        }
    }

    public function has(string $key): bool
    {
        //verifica si existe un dato en especifico
        if ($this->method->value === 'GET') {
            return isset($this->dataGet[$key]);
        } else {
            return isset($this->data[$key]);
        }
    }

    public function except(string|array $keys): object
    {
        //elimina un dato o datos en especifico
        if ($this->method->value === 'GET') {
            $data = $this->dataGet;
        } else {
            $data = $this->data;
        }

        if (is_string($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        $data = (object)$data;

        return $data;
    }

    public function only(string|array $keys): object
    {
        //envia un dato o datos en especifico
        if ($this->method->value === 'GET') {
            $data = $this->dataGet;
        } else {
            $data = $this->data;
        }

        if (is_string($keys)) {
            $keys = [$keys];
        }

        $data = array_intersect_key($data, array_flip($keys));

        $data = (object)$data;

        return $data;
    }
}
