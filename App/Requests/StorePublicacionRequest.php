<?php

namespace App\Requests;

use App\Library\JWT\JWTAuth;
use App\Models\Usuario;
use Cronos\Http\FormRequest;

class StorePublicacionRequest extends FormRequest
{
    protected ?Usuario $resolvedUser = null;

    /**
     * Determina si el usuario está autenticado mediante el token JWT.
     */
    public function authorize(): bool
    {
        $token = $this->headers('X-Token');

        if (empty($token)) {
            return false;
        }

        $jwt = new JWTAuth();
        $decoded = $jwt->decodeToken($token);

        if (!$decoded || empty($decoded->sub)) {
            return false;
        }

        $this->resolvedUser = Usuario::find($decoded->sub);

        return $this->resolvedUser !== null;
    }

    /**
     * Retorna el modelo Usuario autenticado.
     */
    public function user(): ?Usuario
    {
        return $this->resolvedUser;
    }

    /**
     * Reglas de validación para crear una publicación.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'titulo'    => 'required|string|min:3|max:100',
            'slug'      => 'required|slug|unique:Publicacion,slug',
            'contenido' => 'required|string|min:3|max:1000',
        ];
    }
}
