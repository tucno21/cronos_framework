<?php

namespace Cronos\Database\Schema;

use PDO;

class Schema
{
    /**
     * Get the database connection
     */
    protected static function getConnection(): PDO
    {
        return app(\Cronos\Database\DatabaseDriver::class)->getPDO();
    }

    /**
     * Create a new table
     */
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, self::getConnection(), 'create');
        $callback($blueprint);
        $blueprint->execute();
    }

    /**
     * Modify an existing table
     */
    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, self::getConnection(), 'alter');
        $callback($blueprint);
        $blueprint->execute();
    }

    /**
     * Drop a table
     */
    public static function drop(string $table): void
    {
        $connection = self::getConnection();
        $connection->exec("DROP TABLE `{$table}`");
    }

    /**
     * Drop a table if it exists
     */
    public static function dropIfExists(string $table): void
    {
        $connection = self::getConnection();
        $connection->exec("DROP TABLE IF EXISTS `{$table}`");
    }

    /**
     * Check if a table exists
     */
    public static function hasTable(string $table): bool
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM information_schema.tables 
                                      WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if a column exists in a table
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM information_schema.columns 
                                      WHERE table_schema = DATABASE() 
                                      AND table_name = ? 
                                      AND column_name = ?");
        $stmt->execute([$table, $column]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Rename a table
     */
    public static function rename(string $from, string $to): void
    {
        $connection = self::getConnection();
        $connection->exec("RENAME TABLE `{$from}` TO `{$to}`");
    }
}
