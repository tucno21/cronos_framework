<?php

namespace Cronos\Model;

/**
 * Soft deletes opt-in para modelos de Cronos.
 *
 * Al usarlo en un modelo, el ORM:
 * - Filtra automaticamente los registros con eliminado_en NOT NULL en
 *   todas las consultas SELECT (get, first, find, all, count, agregados).
 * - Convierte $modelo->delete() y las eliminaciones por query builder en
 *   un UPDATE de eliminado_en (borrado logico).
 *
 * Columna personalizada: declare `protected string $deletedAt = 'mi_columna';`
 * en el modelo. Por defecto es la constante DELETED_AT ('eliminado_en').
 *
 * Metodos de instancia: $modelo->delete(), $modelo->forceDelete(),
 * $modelo->restore(), $modelo->trashed().
 * Metodos de consulta: withTrashed(), onlyTrashed(), restore().
 *
 * Ejemplo:
 *
 *   class Publicacion extends Model
 *   {
 *       use SoftDeletes;
 *       ...
 *   }
 */
trait SoftDeletes
{
    public const DELETED_AT = 'eliminado_en';

    protected string $deletedAt = self::DELETED_AT;

    /**
     * Indica si el registro esta marcado como eliminado (soft delete).
     */
    public function trashed(): bool
    {
        $columna = $this->getDeletedAtColumn();

        return array_key_exists($columna, $this->attributes) && $this->attributes[$columna] !== null;
    }
}
