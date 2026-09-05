<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;

/**
 * Fixture de Publicacion con Scopes Locales:
 * - scopePublicadas: filtra por estado = 'publicado'
 * - scopePopulares: filtra por vistas >= $minVistas
 * - scopeDelUsuario: filtra por usuario_id = $usuarioId
 */
class PublicacionConScopes extends Publicacion
{
    public function scopePublicadas($query)
    {
        return $query->where('estado', 'publicado');
    }

    public function scopePopulares($query, int $minVistas = 100)
    {
        return $query->where('vistas', '>=', $minVistas);
    }

    public function scopeDelUsuario($query, int $usuarioId)
    {
        $query->where('usuario_id', $usuarioId);
    }
}
