<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;
use Cronos\Model\SoftDeletes;

/**
 * Variante de Publicacion con soft deletes (la tabla publicaciones
 * tiene la columna eliminado_en). Model::delete() marca en lugar de borrar.
 */
class PublicacionBorrable extends Publicacion
{
    use SoftDeletes;
}
