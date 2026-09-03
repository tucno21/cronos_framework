<?php

namespace Cronos\Database;

class Blueprint
{
    protected array $columns = [];
    protected array $primaryKeys = [];
    protected array $foreigns = [];
    protected array $dropColumns = [];
    protected array $indexCommands = [];
    protected array $uniqueCommands = [];

    public function __construct(
        protected string $table,
        protected string $action = 'create'
    ) {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    ////////////////////////////////////////////////////////////////////
    // Tipos de columnas
    ////////////////////////////////////////////////////////////////////

    public function addColumn(string $name, string $type, array $allowed = []): ColumnDefinition
    {
        return $this->columns[] = new ColumnDefinition($this, $name, $type, $allowed);
    }

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT')->unsigned()->autoIncrement()->primary();
    }

    public function increments(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn($name, 'INT')->unsigned()->autoIncrement()->primary();
    }

    public function bigIncrements(string $name = 'id'): ColumnDefinition
    {
        return $this->id($name);
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, "VARCHAR($length)");
    }

    public function char(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, "CHAR($length)");
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT');
    }

    public function mediumText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'MEDIUMTEXT');
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'LONGTEXT');
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT');
    }

    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TINYINT');
    }

    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'SMALLINT');
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT');
    }

    public function unsignedInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT')->unsigned();
    }

    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT')->unsigned();
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TINYINT(1)')->default(false);
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, "DECIMAL($precision, $scale)");
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DOUBLE');
    }

    public function double(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DOUBLE');
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATE');
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATETIME');
    }

    public function time(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIME');
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function softDeletes(string $name = 'deleted_at'): ColumnDefinition
    {
        return $this->timestamp($name)->nullable();
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'JSON');
    }

    public function uuid(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'CHAR(36)');
    }

    public function enum(string $name, array $allowed): ColumnDefinition
    {
        return $this->addColumn($name, 'ENUM', $allowed);
    }

    public function foreignId(string $name): ColumnDefinition
    {
        return $this->unsignedBigInteger($name);
    }

    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    ////////////////////////////////////////////////////////////////////
    // Indices, llaves y comandos
    ////////////////////////////////////////////////////////////////////

    public function primary(string|array $columns): void
    {
        $this->primaryKeys[] = (array) $columns;
    }

    public function unique(string|array $columns): void
    {
        $this->uniqueCommands[] = (array) $columns;
    }

    public function index(string|array $columns): void
    {
        $this->indexCommands[] = (array) $columns;
    }

    public function foreign(string $column): ForeignKeyDefinition
    {
        return $this->foreignFor($column);
    }

    public function foreignFor(string $column): ForeignKeyDefinition
    {
        foreach ($this->foreigns as $foreign) {
            if ($foreign->column === $column) {
                return $foreign;
            }
        }

        return $this->foreigns[] = new ForeignKeyDefinition($column);
    }

    public function dropColumn(string ...$columns): void
    {
        foreach ($columns as $column) {
            $this->dropColumns[] = $column;
        }
    }

    ////////////////////////////////////////////////////////////////////
    // Utilidades
    ////////////////////////////////////////////////////////////////////

    public function pluralize(string $word): string
    {
        if (preg_match('/(s|x|z|ch|sh)$/', $word)) {
            return $word . 'es';
        }

        if (preg_match('/[^aeiou]y$/', $word)) {
            return substr($word, 0, -1) . 'ies';
        }

        return $word . 's';
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    protected function quoteDefault(mixed $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . addslashes($value) . "'";
    }

    protected function columnTypeSql(ColumnDefinition $column): string
    {
        if ($column->type === 'ENUM') {
            $values = implode(', ', array_map(
                fn (string $value) => "'" . addslashes($value) . "'",
                $column->allowed
            ));
            return "ENUM($values)";
        }

        return $column->type;
    }

    protected function columnSql(ColumnDefinition $column): string
    {
        $sql = '`' . $column->name . '` ' . $this->columnTypeSql($column);

        if ($column->unsigned) {
            $sql .= ' UNSIGNED';
        }

        $sql .= $column->nullable ? ' NULL' : ' NOT NULL';

        if ($column->hasDefault) {
            $sql .= ' DEFAULT ' . $this->quoteDefault($column->default);
        }

        if ($column->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }

        if ($column->primaryKey) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    protected function indexName(string $suffix, string|array $columns): string
    {
        return $this->table . '_' . implode('_', (array) $columns) . '_' . $suffix;
    }

    protected function backtickColumns(array $columns): string
    {
        return implode(', ', array_map(fn (string $c) => "`$c`", $columns));
    }

    ////////////////////////////////////////////////////////////////////
    // Generacion de SQL
    ////////////////////////////////////////////////////////////////////

    public function toCreateSql(): string
    {
        $lines = [];

        foreach ($this->columns as $column) {
            $lines[] = $this->columnSql($column);
        }

        if (!empty($this->primaryKeys)) {
            foreach ($this->primaryKeys as $keys) {
                $lines[] = 'PRIMARY KEY (' . implode(', ', array_map(fn (string $c) => "`$c`", $keys)) . ')';
            }
        }

        foreach ($this->columns as $column) {
            if ($column->unique) {
                $lines[] = 'UNIQUE KEY `' . $this->indexName('unique', $column->name) . '` (`' . $column->name . '`)';
            }
            if ($column->index) {
                $lines[] = 'KEY `' . $this->indexName('index', $column->name) . '` (`' . $column->name . '`)';
            }
        }

        foreach ($this->uniqueCommands as $cols) {
            $lines[] = 'UNIQUE KEY `' . $this->indexName('unique', $cols) . '` (' . $this->backtickColumns($cols) . ')';
        }

        foreach ($this->indexCommands as $cols) {
            $lines[] = 'KEY `' . $this->indexName('index', $cols) . '` (' . $this->backtickColumns($cols) . ')';
        }

        foreach ($this->foreigns as $foreign) {
            if (is_null($foreign->on)) {
                throw new \RuntimeException("La llave foranea de '{$foreign->column}' necesita ->on('tabla')");
            }

            $sql = "CONSTRAINT `{$this->table}_{$foreign->column}_foreign` "
                . "FOREIGN KEY (`{$foreign->column}`) "
                . "REFERENCES `{$foreign->on}` (`{$foreign->references}`)";

            if (!is_null($foreign->onDelete)) {
                $sql .= ' ON DELETE ' . strtoupper($foreign->onDelete);
            }

            if (!is_null($foreign->onUpdate)) {
                $sql .= ' ON UPDATE ' . strtoupper($foreign->onUpdate);
            }

            $lines[] = $sql;
        }

        if (empty($lines)) {
            throw new \RuntimeException("La tabla '{$this->table}' no define columnas");
        }

        return "CREATE TABLE `{$this->table}` (\n  "
            . implode(",\n  ", $lines)
            . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    public function toAlterSql(): string
    {
        $parts = [];

        foreach ($this->dropColumns as $column) {
            $parts[] = 'DROP COLUMN `' . $column . '`';
        }

        foreach ($this->columns as $column) {
            $parts[] = 'ADD COLUMN ' . $this->columnSql($column);

            if ($column->unique) {
                $parts[] = 'ADD UNIQUE KEY `' . $this->indexName('unique', $column->name) . '` (`' . $column->name . '`)';
            }
            if ($column->index) {
                $parts[] = 'ADD KEY `' . $this->indexName('index', $column->name) . '` (`' . $column->name . '`)';
            }
        }

        foreach ($this->primaryKeys as $keys) {
            $parts[] = 'ADD PRIMARY KEY (' . implode(', ', array_map(fn (string $c) => "`$c`", $keys)) . ')';
        }

        foreach ($this->uniqueCommands as $cols) {
            $parts[] = 'ADD UNIQUE KEY `' . $this->indexName('unique', $cols) . '` (' . $this->backtickColumns($cols) . ')';
        }

        foreach ($this->indexCommands as $cols) {
            $parts[] = 'ADD KEY `' . $this->indexName('index', $cols) . '` (' . $this->backtickColumns($cols) . ')';
        }

        foreach ($this->foreigns as $foreign) {
            if (is_null($foreign->on)) {
                throw new \RuntimeException("La llave foranea de '{$foreign->column}' necesita ->on('tabla')");
            }

            $sql = "ADD CONSTRAINT `{$this->table}_{$foreign->column}_foreign` "
                . "FOREIGN KEY (`{$foreign->column}`) "
                . "REFERENCES `{$foreign->on}` (`{$foreign->references}`)";

            if (!is_null($foreign->onDelete)) {
                $sql .= ' ON DELETE ' . strtoupper($foreign->onDelete);
            }

            if (!is_null($foreign->onUpdate)) {
                $sql .= ' ON UPDATE ' . strtoupper($foreign->onUpdate);
            }

            $parts[] = $sql;
        }

        if (empty($parts)) {
            throw new \RuntimeException("La tabla '{$this->table}' no define cambios");
        }

        return 'ALTER TABLE `' . $this->table . '` ' . implode(', ', $parts);
    }
}
