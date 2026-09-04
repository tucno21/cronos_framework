<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;

/**
 * Variante de Publicacion con accessor, mutator y $appends para probar
 * la transformacion de atributos (estilo Eloquent).
 */
class PublicacionConAccesorios extends Publicacion
{
    protected array $appends = ['titulo_mayuscula'];

    public function getTituloMayusculaAttribute(?string $valor): ?string
    {
        $titulo = $valor ?? $this->attributes['titulo'] ?? null;

        return $titulo === null ? null : mb_strtoupper($titulo);
    }

    public function setTituloAttribute(?string $valor): ?string
    {
        return $valor === null ? null : mb_strtolower(trim($valor));
    }
}
