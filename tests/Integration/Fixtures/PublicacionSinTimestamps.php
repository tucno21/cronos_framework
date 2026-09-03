<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;

/**
 * Variante de Publicacion con timestamps desactivados: create()/save()
 * no tocan created_at ni updated_at (las columnas de la tabla son
 * nullable, por lo que quedan en NULL).
 */
class PublicacionSinTimestamps extends Publicacion
{
    protected bool $timestamps = false;
}
