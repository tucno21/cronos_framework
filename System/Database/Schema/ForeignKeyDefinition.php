<?php

namespace Cronos\Database\Schema;

class ForeignKeyDefinition
{
    protected string $column;
    protected ?string $references = null;
    protected ?string $on = null;
    protected ?string $onDelete = null;
    protected ?string $onUpdate = null;

    public function __construct(string $column, string $table)
    {
        $this->column = $column;
    }

    /**
     * Specify the referenced column
     */
    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    /**
     * Specify the referenced table
     */
    public function on(string $table): self
    {
        $this->on = $table;
        return $this;
    }

    /**
     * Specify ON DELETE action
     */
    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    /**
     * Specify ON UPDATE action
     */
    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    /**
     * Build the foreign key constraint SQL
     */
    public function build(): string
    {
        $sql = "FOREIGN KEY (`{$this->column}`)";

        if ($this->references !== null && $this->on !== null) {
            $sql .= " REFERENCES `{$this->on}`(`{$this->references}`)";
        }

        if ($this->onDelete !== null) {
            $sql .= " ON DELETE {$this->onDelete}";
        }

        if ($this->onUpdate !== null) {
            $sql .= " ON UPDATE {$this->onUpdate}";
        }

        return $sql;
    }
}
