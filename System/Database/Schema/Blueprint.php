<?php

namespace Cronos\Database\Schema;

use PDO;
use Cronos\Database\Schema\ColumnDefinition;
use Cronos\Database\Schema\ForeignKeyDefinition;

class Blueprint
{
    protected string $table;
    protected PDO $connection;
    protected array $columns = [];
    protected array $indexes = [];
    protected string $command = 'create';

    public function __construct(string $table, PDO $connection, string $command = 'create')
    {
        $this->table = $table;
        $this->connection = $connection;
        $this->command = $command;
    }

    /**
     * Define an auto-incrementing ID column
     */
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn($name, 'INT', 'AUTO_INCREMENT PRIMARY KEY');
    }

    /**
     * Define a VARCHAR column
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, "VARCHAR({$length})", 'NOT NULL');
    }

    /**
     * Define a TEXT column
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT', 'NOT NULL');
    }

    /**
     * Define a LONGTEXT column
     */
    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'LONGTEXT', 'NOT NULL');
    }

    /**
     * Define an INTEGER column
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT', 'NOT NULL');
    }

    /**
     * Define a BIG INTEGER column
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT', 'NOT NULL');
    }

    /**
     * Define a BOOLEAN column
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TINYINT(1)', 'NOT NULL DEFAULT 0');
    }

    /**
     * Define a DECIMAL column
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, "DECIMAL({$precision}, {$scale})", 'NOT NULL');
    }

    /**
     * Define a FLOAT column
     */
    public function float(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, "FLOAT({$precision}, {$scale})", 'NOT NULL');
    }

    /**
     * Define a DATE column
     */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATE', 'NOT NULL');
    }

    /**
     * Define a DATETIME column
     */
    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATETIME', 'NOT NULL');
    }

    /**
     * Define a TIMESTAMP column
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP', 'NOT NULL');
    }

    /**
     * Add created_at and updated_at timestamps
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->useCurrent();
        $this->timestamp('updated_at')->nullable()->default(null);
    }

    /**
     * Add deleted_at timestamp for soft deletes
     */
    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')->nullable();
    }

    /**
     * Define an ENUM column
     */
    public function enum(string $name, array $allowed): ColumnDefinition
    {
        $values = implode("','", array_map(function ($val) {
            return addslashes($val);
        }, $allowed));
        return $this->addColumn($name, "ENUM('{$values}')", 'NOT NULL');
    }

    /**
     * Define a JSON column
     */
    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'JSON', 'NOT NULL');
    }

    /**
     * Define a BINARY column
     */
    public function binary(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BLOB', 'NOT NULL');
    }

    /**
     * Add a column to the blueprint
     */
    protected function addColumn(string $name, string $type, string $attributes): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $attributes);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Add an index to the table
     */
    public function index(array $columns, string $name = null): void
    {
        $indexName = $name ?? $this->table . '_' . implode('_', $columns) . '_index';
        $this->indexes[] = [
            'type' => 'index',
            'name' => $indexName,
            'columns' => $columns
        ];
    }

    /**
     * Add a foreign key constraint
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $foreign = new ForeignKeyDefinition($column, $this->table);
        $this->indexes[] = $foreign;
        return $foreign;
    }

    /**
     * Build and execute the SQL statement
     */
    public function build(): string
    {
        $sql = '';

        if ($this->command === 'create') {
            $sql = "CREATE TABLE `{$this->table}` (\n";
        } elseif ($this->command === 'alter') {
            $sql = "ALTER TABLE `{$this->table}` ";
        }

        $columnDefs = [];
        foreach ($this->columns as $column) {
            $columnDefs[] = $column->build();
        }

        if ($this->command === 'create') {
            $sql .= implode(",\n", $columnDefs);

            // Add indexes
            foreach ($this->indexes as $index) {
                if ($index instanceof ForeignKeyDefinition) {
                    $sql .= ",\n" . $index->build();
                } else {
                    $indexColumns = implode('`, `', $index['columns']);
                    $sql .= ",\nKEY `{$index['name']}` (`{$indexColumns}`)";
                }
            }

            $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        } elseif ($this->command === 'alter') {
            $alterStatements = [];
            foreach ($this->columns as $column) {
                $alterStatements[] = 'ADD COLUMN ' . $column->build();
            }
            $sql .= implode(', ', $alterStatements);
        }

        return $sql;
    }

    /**
     * Execute the blueprint
     */
    public function execute(): void
    {
        $sql = $this->build();
        $this->connection->exec($sql);
    }
}
