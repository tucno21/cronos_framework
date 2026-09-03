<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;

/**
 * Variante de Publicacion con casts activados para probar la
 * conversion de tipos del ORM (todo lo que llega de PDO es string).
 */
class PublicacionCasteada extends Publicacion
{
    protected array $casts = [
        'usuario_id' => 'int',
        'vistas' => 'int',
    ];
}
