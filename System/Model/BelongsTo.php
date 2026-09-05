<?php

namespace Cronos\Model;

class BelongsTo
{
    protected Model $related;
    protected Model $parent;
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $ownerKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    public function get(): ?Model
    {
        $foreignKeyValue = $this->parent->{$this->foreignKey} ?? null;
        if ($foreignKeyValue === null || $foreignKeyValue === '') {
            return null;
        }

        return $this->related->newQuery()->where($this->ownerKey, $foreignKeyValue)->first();
    }

    /**
     * Asigna la clave foranea del padre para apuntar al modelo dado y
     * retorna el modelo padre (para encadenar ->save()).
     *
     *   $comentario->publicacion()->associate($publicacion)->save();
     *   $comentario->usuario()->associate(5)->save();
     */
    public function associate(Model|int|string $modeloOid): Model
    {
        if ($modeloOid instanceof Model) {
            $oid = $modeloOid->{$modeloOid->getPrimaryKey()} ?? null;

            if ($oid === null) {
                throw new \Error('El modelo a asociar no tiene clave primaria');
            }
        } else {
            $oid = $modeloOid;
        }

        $this->parent->{$this->foreignKey} = $oid;

        return $this->parent;
    }

    /**
     * Limpia la clave foranea del padre (la deja en NULL) y retorna el
     * modelo padre (para encadenar ->save()).
     *
     *   $comentario->publicacion()->dissociate()->save();
     */
    public function dissociate(): Model
    {
        $this->parent->{$this->foreignKey} = null;

        return $this->parent;
    }

    public function getRelated(): Model
    {
        return $this->related;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }
}
