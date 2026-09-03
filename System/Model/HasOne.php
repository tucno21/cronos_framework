<?php

namespace Cronos\Model;

class HasOne
{
    protected Model $related;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function get(): Model|null
    {
        return $this->related->newQuery()->where($this->foreignKey, $this->parent->{$this->localKey})->first();
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

    public function getLocalKey(): string
    {
        return $this->localKey;
    }
}
