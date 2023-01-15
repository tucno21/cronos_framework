<?php

namespace Cronos\Http;

use Cronos\Validation\Validation;

class Controller
{
    //almacenamos los middlewares
    protected array $middlewares = [];
    /**
     * validacion de reglas para los datos de envio
     */
    protected function validate(array|object $inputs, array $rules)
    {
        $result = new Validation;

        return $result->validate($inputs, $rules);
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function middleware(string|array $middlewares): self
    {
        if (is_string($middlewares)) {
            //si es un string lo convertimos en array
            $middlewares = [$middlewares];
        }

        //array_map crea un nuevo array con los resultados de la funcion
        //almacenamos los middlewares en el array $middlewares
        $this->middlewares = array_map(fn ($middleware) => new $middleware, $middlewares);

        return $this;
    }
}
