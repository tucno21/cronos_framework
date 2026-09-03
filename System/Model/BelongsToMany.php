<?php

namespace Cronos\Model;

class BelongsToMany
{
    protected Model $related;
    protected Model $parent;
    protected string $pivotTable;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;

    public function __construct(Model $related, Model $parent, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    public function get(): ?ModelCollection
    {
        return $this->related->newQuery()
            ->join(
                $this->pivotTable,
                $this->related->getTable() . '.' . $this->related->getPrimaryKey(),
                '=',
                $this->pivotTable . '.' . $this->relatedPivotKey
            )
            ->where(
                $this->pivotTable . '.' . $this->foreignPivotKey,
                $this->parent->{$this->parent->getPrimaryKey()}
            )
            ->get();
    }

    public function getRelated(): Model
    {
        return $this->related;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getPivotTable(): string
    {
        return $this->pivotTable;
    }

    public function getForeignPivotKey(): string
    {
        return $this->foreignPivotKey;
    }

    public function getRelatedPivotKey(): string
    {
        return $this->relatedPivotKey;
    }
}
