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
        return $this->related->newQuery()->where($this->ownerKey, $this->parent->{$this->foreignKey})->first();
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
