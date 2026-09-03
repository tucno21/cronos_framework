<?php

namespace Cronos\Database;

class ForeignKeyDefinition
{
    public string $references = 'id';
    public ?string $on = null;
    public ?string $onDelete = null;
    public ?string $onUpdate = null;

    public function __construct(
        public string $column
    ) {
    }

    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->on = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->onDelete = 'cascade';
        return $this;
    }

    public function nullOnDelete(): self
    {
        $this->onDelete = 'set null';
        return $this;
    }
}
