<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;

/**
 * Variante de Publicacion con casts de tipos ricos:
 * - datetime: created_at se convierte a DateTimeImmutable
 * - decimal:N: vistas se formatea como string con 2 decimales
 * - array/json: contenido (longtext) se decodifica de JSON a array
 */
class PublicacionConCastsRicos extends Publicacion
{
    protected array $casts = [
        'created_at' => 'datetime',
        'vistas' => 'decimal:2',
        'contenido' => 'array',
    ];
}
