<?php

namespace App\Resources;

use Cronos\Http\JsonResource;

class UsuarioResource extends JsonResource
{
    public static ?string $wrap = null;

    /**
     * Transforma el recurso a un array estructurado para respuestas JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'rol' => $this->rol,
            'avatar' => $this->avatar,
            'correo_verificado_en' => $this->correo_verificado_en,
            'invitado_por' => $this->invitado_por ? (int) $this->invitado_por : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'perfil' => $this->perfil ? (is_object($this->perfil) ? $this->perfil->toArray() : $this->perfil) : null,
            'roles' => $this->roles ? (is_object($this->roles) && method_exists($this->roles, 'toArray') ? $this->roles->toArray() : (array) $this->roles) : [],
            'invitadoPor' => $this->invitadoPor ? (is_object($this->invitadoPor) ? $this->invitadoPor->toArray() : $this->invitadoPor) : null,
            'publicaciones_count' => $this->publicaciones_count ?? null,
            'comentarios_count' => $this->comentarios_count ?? null,
        ];
    }
}
