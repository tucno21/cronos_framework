<?php

namespace App\Requests\Auth;

use Cronos\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el registro de usuarios.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'nombre'               => 'required|string|min:3|max:100',
            'correo'               => 'required|email|unique:Usuario,correo',
            'contrasena'           => 'required|min:6|max:50|matches:confirmar_contrasena',
            'confirmar_contrasena' => 'required|matches:contrasena',
        ];
    }
}
