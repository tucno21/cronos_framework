<?php

namespace Cronos\Database\Schema;

class ColumnDefinition
{
    protected string $name;
    protected string $type;
    protected string $attributes;
    protected bool $nullable = false;
    protected $default = null;
    protected bool $unique = false;
    protected bool $unsigned = false;
    protected ?string $after = null;
    protected ?string $comment = null;
    protected bool $useCurrent = false;

    public function __construct(string $name, string $type, string $attributes = '')
    {
        $this->name = $name;
        $this->type = $type;
        $this->attributes = $attributes;
    }

    /**
     * Mark the column as nullable
     */
    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * Set a default value for the column
     */
    public function default($value): self
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Mark the column as unique
     */
    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    /**
     * Mark the column as unsigned (for numeric types)
     */
    public function unsigned(): self
    {
        $this->unsigned = true;
        return $this;
    }

    /**
     * Place the column after another column (ALTER TABLE)
     */
    public function after(string $column): self
    {
        $this->after = $column;
        return $this;
    }

    /**
     * Add a comment to the column
     */
    public function comment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Set the column to use CURRENT_TIMESTAMP (for timestamps)
     */
    public function useCurrent(): self
    {
        $this->useCurrent = true;
        return $this;
    }

    /**
     * Build the column definition SQL
     */
    public function build(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        // Add unsigned attribute
        if ($this->unsigned && in_array($this->type, ['INT', 'BIGINT', 'DECIMAL', 'FLOAT'])) {
            $sql .= ' UNSIGNED';
        }

        // Replace NOT NULL with NULL if nullable
        if ($this->nullable) {
            $sql = str_replace('NOT NULL', 'NULL', $sql);
        } elseif (strpos($this->attributes, 'NOT NULL') === false) {
            $sql .= ' NOT NULL';
        }

        // Add default value
        if ($this->default !== null) {
            if (is_string($this->default) && $this->default !== 'CURRENT_TIMESTAMP') {
                $sql .= " DEFAULT '" . addslashes($this->default) . "'";
            } elseif (is_bool($this->default)) {
                $sql .= ' DEFAULT ' . ($this->default ? '1' : '0');
            } elseif ($this->default === 'CURRENT_TIMESTAMP' || $this->useCurrent) {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif (is_numeric($this->default)) {
                $sql .= ' DEFAULT ' . $this->default;
            } else {
                $sql .= ' DEFAULT NULL';
            }
        } elseif ($this->nullable) {
            $sql .= ' DEFAULT NULL';
        } elseif ($this->useCurrent && !$this->nullable) {
            $sql .= ' DEFAULT CURRENT_TIMESTAMP';
        }

        // Add unique constraint
        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        // Add comment
        if ($this->comment !== null) {
            $sql .= " COMMENT '" . addslashes($this->comment) . "'";
        }

        // Add after clause (for ALTER TABLE)
        if ($this->after !== null) {
            $sql .= " AFTER `{$this->after}`";
        }

        return $sql;
    }
}
