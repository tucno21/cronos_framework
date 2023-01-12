<?php

namespace Cronos\Http;

use Cronos\Validation\Validation;

class Controller
{
    /**
     * validacion de reglas para los datos de envio
     */
    protected function validate(array|object $inputs, array $rules)
    {
        $result = new Validation;

        return $result->validate($inputs, $rules);
    }
}
