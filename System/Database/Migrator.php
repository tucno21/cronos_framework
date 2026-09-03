<?php

namespace Cronos\Database;

use PDO;
use RuntimeException;

class Migrator
{
    protected PDO $pdo;
    protected string $path;

    public function __construct(?PDO $pdo = null, ?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/App/Migrations';
        $this->pdo = $pdo ?? $this->defaultConnection();

        Schema::setPDO($this->pdo);
    }

    protected function defaultConnection(): PDO
    {
        $migrate = new DatabaseMigrate();

        if (!$migrate->connect()) {
            throw new RuntimeException('No se pudo conectar a MySQL para ejecutar las migraciones');
        }

        return $migrate->getPDO();
    }

    ////////////////////////////////////////////////////////////////////
    // Descubrimiento de archivos
    ////////////////////////////////////////////////////////////////////

    public static function getMigrationFiles(string $path): array
    {
        $files = glob(rtrim($path, '/\\') . '/*.php') ?: [];
        sort($files);

        return $files;
    }

    public static function getMigrationName(string $file): string
    {
        return basename($file, '.php');
    }

    public function resolve(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new RuntimeException(
                'La migracion ' . basename($file) . ' debe retornar una instancia de ' . Migration::class
            );
        }

        return $migration;
    }

    ////////////////////////////////////////////////////////////////////
    // Repositorio (tabla migrations)
    ////////////////////////////////////////////////////////////////////

    public function ensureRepository(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                UNIQUE KEY `migrations_migration_unique` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function ran(): array
    {
        $this->ensureRepository();

        return $this->pdo
            ->query('SELECT `migration` FROM `migrations` ORDER BY `id`')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function pending(): array
    {
        return array_values(array_diff(
            array_map([self::class, 'getMigrationName'], self::getMigrationFiles($this->path)),
            $this->ran()
        ));
    }

    public function getLastBatch(): ?int
    {
        $this->ensureRepository();

        $result = $this->pdo->query('SELECT MAX(`batch`) FROM `migrations`')->fetchColumn();

        return $result === null ? null : (int) $result;
    }

    public function log(string $name, int $batch): void
    {
        $statement = $this->pdo->prepare('INSERT INTO `migrations` (`migration`, `batch`) VALUES (?, ?)');
        $statement->execute([$name, $batch]);
    }

    public function delete(string $name): void
    {
        $statement = $this->pdo->prepare('DELETE FROM `migrations` WHERE `migration` = ?');
        $statement->execute([$name]);
    }

    ////////////////////////////////////////////////////////////////////
    // Comandos
    ////////////////////////////////////////////////////////////////////

    public function runPending(): int
    {
        $this->ensureRepository();

        $pending = $this->pending();

        if (empty($pending)) {
            echo "\nNothing to migrate.\n";
            return 0;
        }

        $batch = ($this->getLastBatch() ?? 0) + 1;
        $count = 0;

        foreach ($pending as $name) {
            echo "\nMigrating: {$name}";

            $migration = $this->resolve($this->path . '/' . $name . '.php');
            $migration->up();
            $this->log($name, $batch);

            echo "\nMigrated:  {$name}";
            $count++;
        }

        echo "\n\nMigracion completada. {$count} migracion(es) ejecutada(s) en el lote {$batch}.\n";
        return $count;
    }

    public function rollback(int $steps = 1): int
    {
        $this->ensureRepository();

        $last = $this->getLastBatch();

        if (is_null($last)) {
            echo "\nNothing to rollback.\n";
            return 0;
        }

        $minBatch = max(1, $last - $steps + 1);
        $count = 0;

        for ($batch = $last; $batch >= $minBatch; $batch--) {
            $statement = $this->pdo->prepare(
                'SELECT `migration` FROM `migrations` WHERE `batch` = ? ORDER BY `id` DESC'
            );
            $statement->execute([$batch]);
            $names = $statement->fetchAll(PDO::FETCH_COLUMN);

            foreach ($names as $name) {
                echo "\nRolling back: {$name}";

                $migration = $this->resolve($this->path . '/' . $name . '.php');
                $migration->down();
                $this->delete($name);

                echo "\nRolled back:  {$name}";
                $count++;
            }
        }

        echo "\n\nRollback completado. {$count} migracion(es) revertida(s).\n";
        return $count;
    }

    public function status(): array
    {
        $this->ensureRepository();

        $statement = $this->pdo->query('SELECT `migration`, `batch` FROM `migrations`');
        $batches = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $batches[$row['migration']] = (int) $row['batch'];
        }

        $rows = [];

        foreach (self::getMigrationFiles($this->path) as $file) {
            $name = self::getMigrationName($file);
            $rows[] = [
                'migration' => $name,
                'batch' => $batches[$name] ?? null,
            ];
        }

        return $rows;
    }

    public function fresh(): int
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = $this->pdo
            ->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            echo "\nDropping table: {$table}";
            $this->pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "\n";

        return $this->runPending();
    }

    public function refresh(): int
    {
        $last = $this->getLastBatch();
        $steps = is_null($last) ? 1 : $last;
        $this->rollback($steps);

        return $this->runPending();
    }
}
