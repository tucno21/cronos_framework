<?php

declare(strict_types=1);

namespace Cronos\Http;

use Cronos\Container\Container;
use Cronos\Errors\AuthorizationException;
use Cronos\Errors\ValidationException;
use Cronos\Validation\Validation;

abstract class FormRequest extends Request
{
    /**
     * Datos validados que pasaron la validación.
     */
    protected array $validatedData = [];

    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la petición.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Mensajes de error personalizados (opcional).
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Retorna el usuario autenticado (si está implementado).
     */
    public function user(): mixed
    {
        return $this->data['user'] ?? null;
    }

    /**
     * Copia las propiedades internas desde una instancia base de Request.
     */
    public function copyFrom(Request $request): self
    {
        $this->uri = $request->uri();
        $this->method = $request->method();
        $this->headers = (array) $request->headers();
        $this->cookies = (array) $request->cookies();
        $this->rawBody = $request->rawBody();
        $this->data = (array) $request->all();
        $this->files = $request->files();

        return $this;
    }

    /**
     * Valida la petición actual invocando authorize() y rules().
     *
     * @throws AuthorizationException Si authorize() retorna false.
     * @throws ValidationException Si las reglas de validación fallan.
     */
    public function validateResolved(): void
    {
        $this->prepareForValidation();

        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $rules = $this->rules();
        if (!empty($rules)) {
            $dataToValidate = (array) $this->all();
            $validator = new Validation();
            $result = $validator->validate($dataToValidate, $rules);

            if ($result !== true) {
                $this->failedValidation($result);
            }

            // Filtrar solo las claves presentes en las reglas
            $validatedKeys = array_keys($rules);
            $this->validatedData = array_intersect_key($dataToValidate, array_flip($validatedKeys));
        } else {
            $this->validatedData = (array) $this->all();
        }

        $this->passedValidation();
    }

    /**
     * Hook antes de validar (para sanitizar datos, normalizar inputs, etc.).
     */
    protected function prepareForValidation(): void
    {
        // Sobrescribir en la subclase si se necesita
    }

    /**
     * Hook posterior a la validación exitosa.
     */
    protected function passedValidation(): void
    {
        // Sobrescribir en la subclase si se necesita
    }

    /**
     * Comprueba la autorización.
     */
    protected function passesAuthorization(): bool
    {
        return $this->authorize();
    }

    /**
     * Maneja el fallo de autorización.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Esta acción no está autorizada.', 403);
    }

    /**
     * Maneja el fallo de validación.
     *
     * @throws ValidationException
     */
    protected function failedValidation(mixed $errors): void
    {
        if ($this->expectsJson()) {
            $response = json([
                'status' => 'error',
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $errors,
            ], 422);
        } else {
            $response = back()->withErrors($this->all(), $errors);
        }

        throw new ValidationException($errors, $response, 'Los datos proporcionados no son válidos.');
    }

    /**
     * Obtiene los datos que han sido validados según las reglas.
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated(string|array|null $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->validatedData;
        }

        if (is_array($key)) {
            return array_intersect_key($this->validatedData, array_flip($key));
        }

        return $this->validatedData[$key] ?? $default;
    }
}
