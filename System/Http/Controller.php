<?php

namespace Cronos\Http;

use Cronos\Validation\Validation;
use Cronos\Errors\ValidationException;

class Controller
{
    //almacenamos los middlewares
    protected array $middlewares = [];

    /**
     * Validación de reglas para los datos de envío
     * 
     * @param array|object $inputs Datos a validar
     * @param array $rules Reglas de validación
     * @param array $messages (opcional) Mensajes de error personalizados
     * @return true|array|string Retorna true si pasa validación, array de errores si falla
     * 
     * Ejemplos de uso:
     * 
     * // Validación básica con string de reglas
     * $errors = $this->validate($request->all(), [
     *     'email' => 'required|email|unique:User,email|max:100',
     *     'password' => 'required|min:8',
     * ]);
     * 
     * if ($errors !== true) {
     *     return back()->with('error', $errors);
     * }
     * 
     * // Validación con mensajes personalizados
     * $errors = $this->validate($request->all(), $rules, [
     *     'email.required' => 'El correo es obligatorio',
     *     'email.email' => 'Ingresa un correo válido',
     * ]);
     * 
     * // Validación con Rule object (fluent interface)
     * $errors = $this->validate($request->all(), [
     *     'email' => Rule::required()->email()->unique('User', 'email'),
     *     'avatar' => Rule::nullable()->image()->max_size(2048),
     * ]);
     */
    protected function validate(array|object $inputs, array $rules, array $messages = [])
    {
        $result = new Validation;

        return $result->validate($inputs, $rules, $messages);
    }

    /**
     * Validación que lanza excepción si falla
     * 
     * @param array|object $inputs Datos a validar
     * @param array $rules Reglas de validación
     * @param array $messages (opcional) Mensajes de error personalizados
     * @return true Retorna true si pasa validación
     * @throws ValidationException Si la validación falla
     * 
     * Ejemplos de uso:
     * 
     * // Validación con excepción automática
     * $this->validateOrFail($request->all(), [
     *     'email' => 'required|email|unique:User,email',
     *     'password' => 'required|min:8',
     * ]);
     * 
     * // Si falla, se lanza ValidationException que es capturada por ExceptionHandler
     * // y retorna automáticamente respuesta JSON o redirección con errores
     * 
     * // Con mensajes personalizados
     * $this->validateOrFail($request->all(), $rules, [
     *     'email.required' => 'El correo es obligatorio',
     * ]);
     */
    protected function validateOrFail(array|object $inputs, array $rules, array $messages = [])
    {
        $result = new Validation;

        $errors = $result->validate($inputs, $rules, $messages);

        if ($errors !== true) {
            throw new ValidationException($errors);
        }

        return true;
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
        $this->middlewares = array_map(fn($middleware) => new $middleware, $middlewares);

        return $this;
    }
}
