<?php

namespace Cronos\Database;

use Closure;
use PDO;
use RuntimeException;

class Schema
{
    protected static ?PDO $pdo = null;

    public static function setPDO(PDO $pdo): void
    {
        static::$pdo = $pdo;
    }

    public static function getConnection(): PDO
    {
        if (is_null(static::$pdo)) {
            throw new RuntimeException(
                'Schema no tiene una conexion PDO. Use Schema::setPDO() o ejecute las migraciones por consola (php cronos migrate).'
            );
        }

        return static::$pdo;
    }

    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, 'create');
        $callback($blueprint);
        static::getConnection()->exec($blueprint->toCreateSql());
    }

    public static function table(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, 'table');
        $callback($blueprint);
        static::getConnection()->exec($blueprint->toAlterSql());
    }

    public static function drop(string $table): void
    {
        static::getConnection()->exec('DROP TABLE `' . $table . '`');
    }

    public static function dropIfExists(string $table): void
    {
        static::getConnection()->exec('DROP TABLE IF EXISTS `' . $table . '`');
    }

    public static function hasTable(string $table): bool
    {
        $statement = static::getConnection()->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $statement->execute([$table]);

        return (bool) $statement->fetchColumn();
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $statement = static::getConnection()->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
        $statement->execute([$column]);

        return $statement->rowCount() > 0;
    }
}
