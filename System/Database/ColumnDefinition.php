<?php

namespace Cronos\Database;

class ColumnDefinition
{
    public bool $nullable = false;
    public bool $hasDefault = false;
    public mixed $default = null;
    public bool $unsigned = false;
    public bool $autoIncrement = false;
    public bool $primaryKey = false;
    public bool $unique = false;
    public bool $index = false;

    public function __construct(
        public Blueprint $blueprint,
        public string $name,
        public string $type,
        public array $allowed = []
    ) {
    }

    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->hasDefault = true;
        $this->default = $value;
        return $this;
    }

    public function unsigned(): self
    {
        $this->unsigned = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function primary(): self
    {
        $this->primaryKey = true;
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function index(): self
    {
        $this->index = true;
        return $this;
    }

    public function constrained(): self
    {
        $base = preg_replace('/_id$/', '', $this->name);
        $this->blueprint->foreignFor($this->name)
            ->references('id')
            ->on($this->blueprint->pluralize($base));
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->blueprint->foreignFor($this->name)->cascadeOnDelete();
        return $this;
    }

    public function nullOnDelete(): self
    {
        $this->blueprint->foreignFor($this->name)->nullOnDelete();
        return $this;
    }
}
