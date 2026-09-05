<?php

namespace App\Requests\Auth;

use Cronos\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el inicio de sesión.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'correo'     => 'required|email|not_unique:Usuario,correo',
            'contrasena' => 'required|password_verify:Usuario,correo',
        ];
    }
}
