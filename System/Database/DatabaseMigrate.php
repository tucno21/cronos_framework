<?php

namespace Cronos\Database;

use PDO;

class DatabaseMigrate
{
    protected PDO $pdo;
    protected string $migrationsPath;
    protected string $migrationsTable = 'cronos_migrations';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = dirname(__DIR__, 2) . '/App/Migrations/';
    }

    /**
     * Create the migrations tracking table if it doesn't exist
     */
    protected function ensureMigrationsTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->pdo->exec($sql);
    }

    /**
     * Get all migration files from the migrations directory
     */
    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '*.php');

        // Filter out the old Database.php file and index files
        $files = array_filter($files, function ($file) {
            $basename = basename($file);
            return $basename !== 'Database.php' && $basename !== 'index.php';
        });

        // Sort by filename (which includes timestamp)
        sort($files);

        return $files;
    }

    /**
     * Get migration class name from filename
     */
    protected function getMigrationClass(string $file): string
    {
        $basename = basename($file, '.php');

        // Extract class name from timestamp format
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $basename, $matches)) {
            return $matches[1];
        }

        return $basename;
    }

    /**
     * Get all executed migrations from the database
     */
    protected function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->migrationsTable}");
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'migration');
    }

    /**
     * Get pending migrations
     */
    protected function getPendingMigrations(): array
    {
        $this->ensureMigrationsTableExists();

        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        $pending = [];
        foreach ($files as $file) {
            $basename = basename($file);
            if (!in_array($basename, $executed)) {
                $pending[] = $file;
            }
        }

        return $pending;
    }

    /**
     * Get the latest batch number
     */
    protected function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($result && $result['max_batch']) ? (int) $result['max_batch'] + 1 : 1;
    }

    /**
     * Record a migration as executed
     */
    protected function logMigration(string $migrationName, int $batch): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)"
        );
        $stmt->execute([$migrationName, $batch]);
    }

    /**
     * Remove a migration from the log
     */
    protected function removeMigration(string $migrationName): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->migrationsTable} WHERE migration = ?"
        );
        $stmt->execute([$migrationName]);
    }

    /**
     * Load and instantiate a migration class
     */
    protected function loadMigration(string $file): object
    {
        require_once $file;

        $className = $this->getMigrationClass($file);
        $namespace = "App\\Migrations\\{$className}";

        if (!class_exists($namespace)) {
            throw new \Exception("Migration class {$namespace} not found in {$file}");
        }

        return new $namespace();
    }

    /**
     * Run all pending migrations
     */
    public function run(): int
    {
        $this->ensureMigrationsTableExists();

        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "\n\e[0;33mNo pending migrations to run.\e[0m\n";
            return 0;
        }

        $batch = $this->getNextBatchNumber();
        $count = 0;

        echo "\n\e[0;36mRunning migrations...\e[0m\n\n";

        foreach ($pending as $file) {
            $basename = basename($file);

            try {
                echo "\e[0;32mMigrating:\e[0m {$basename}\n";

                $migration = $this->loadMigration($file);

                if (!method_exists($migration, 'up')) {
                    throw new \Exception("Migration class must have an up() method");
                }

                $migration->up();
                $this->logMigration($basename, $batch);

                echo "\e[0;32mMigrated:\e[0m  {$basename}\n";
                $count++;
            } catch (\Exception $e) {
                echo "\n\e[0;31mMigration failed:\e[0m {$basename}\n";
                echo "\e[0;31mError:\e[0m " . $e->getMessage() . "\n\n";

                // Rollback the current migration
                try {
                    if (method_exists($migration, 'down')) {
                        $migration->down();
                        echo "\e[0;33mRolled back:\e[0m {$basename}\n";
                    }
                } catch (\Exception $rollbackError) {
                    echo "\e[0;31mRollback failed:\e[0m " . $rollbackError->getMessage() . "\n";
                }

                throw $e;
            }
        }

        echo "\n\e[0;32mSuccessfully migrated {$count} migration(s).\e[0m\n";

        return $count;
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(int $steps = 1): int
    {
        $this->ensureMigrationsTableExists();

        // Get the latest batch numbers
        $stmt = $this->pdo->query(
            "SELECT DISTINCT batch FROM {$this->migrationsTable} ORDER BY batch DESC LIMIT {$steps}"
        );

        $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($batches)) {
            echo "\n\e[0;33mNo migrations to rollback.\e[0m\n";
            return 0;
        }

        $batchesString = implode(',', $batches);

        // Get migrations in these batches, in reverse order
        $stmt = $this->pdo->query(
            "SELECT migration FROM {$this->migrationsTable} 
             WHERE batch IN ({$batchesString}) 
             ORDER BY id DESC"
        );

        $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;

        echo "\n\e[0;36mRolling back migrations...\e[0m\n\n";

        foreach ($migrationsToRollback as $migrationName) {
            try {
                echo "\e[0;33mRolling back:\e[0m {$migrationName}\n";

                $file = $this->migrationsPath . $migrationName;

                if (!file_exists($file)) {
                    echo "\e[0;31mMigration file not found:\e[0m {$file}\n";
                    $this->removeMigration($migrationName);
                    continue;
                }

                $migration = $this->loadMigration($file);

                if (!method_exists($migration, 'down')) {
                    throw new \Exception("Migration class must have a down() method");
                }

                $migration->down();
                $this->removeMigration($migrationName);

                echo "\e[0;32mRolled back:\e[0m  {$migrationName}\n";
                $count++;
            } catch (\Exception $e) {
                echo "\n\e[0;31mRollback failed:\e[0m {$migrationName}\n";
                echo "\e[0;31mError:\e[0m " . $e->getMessage() . "\n\n";
                throw $e;
            }
        }

        echo "\n\e[0;32mSuccessfully rolled back {$count} migration(s).\e[0m\n";

        return $count;
    }

    /**
     * Rollback all migrations
     */
    public function reset(): int
    {
        $this->ensureMigrationsTableExists();

        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM {$this->migrationsTable}");
        $count = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($count === 0) {
            echo "\n\e[0;33mNo migrations to reset.\e[0m\n";
            return 0;
        }

        echo "\n\e[0;36mResetting all migrations...\e[0m\n";

        // Rollback all migrations
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        $maxBatch = (int) $stmt->fetch(PDO::FETCH_ASSOC)['max_batch'];

        $totalRollbacked = 0;
        while ($maxBatch > 0) {
            $rollbacks = $this->rollback($maxBatch);
            if ($rollbacks === 0) {
                break;
            }
            $totalRollbacked += $rollbacks;

            $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
            $maxBatch = (int) $stmt->fetch(PDO::FETCH_ASSOC)['max_batch'];
        }

        return $totalRollbacked;
    }

    /**
     * Reset and re-run all migrations
     */
    public function refresh(): int
    {
        echo "\n\e[0;36mRefreshing migrations...\e[0m\n";

        $rollbacks = $this->reset();
        $migrations = $this->run();

        echo "\n\e[0;32mRefresh complete. Rolled back {$rollbacks} migration(s) and migrated {$migrations} migration(s).\e[0m\n";

        return $migrations;
    }

    /**
     * Show the status of all migrations
     */
    public function status(): void
    {
        $this->ensureMigrationsTableExists();

        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        if (empty($files)) {
            echo "\n\e[0;33mNo migration files found.\e[0m\n";
            return;
        }

        echo "\n\e[0;36mMigration Status:\e[0m\n\n";

        // Get batch info for executed migrations
        $stmt = $this->pdo->query(
            "SELECT migration, batch, executed_at FROM {$this->migrationsTable}"
        );
        $migrationInfo = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $migrationInfo[$row['migration']] = $row;
        }

        $count = 0;
        foreach ($files as $file) {
            $basename = basename($file);
            $isExecuted = in_array($basename, $executed);

            if ($isExecuted) {
                $status = "\e[0;32m[Executed]\e[0m";
                $batch = $migrationInfo[$basename]['batch'];
                $executedAt = $migrationInfo[$basename]['executed_at'];
                echo "\e[0;32m[✓]\e[0m {$basename}\n";
                echo "    Status: {$status}\n";
                echo "    Batch: {$batch}\n";
                echo "    Executed at: {$executedAt}\n";
            } else {
                echo "\e[0;33m[ ]\e[0m {$basename}\n";
                echo "    Status: \e[0;33m[Pending]\e[0m\n";
            }

            $count++;

            if ($count < count($files)) {
                echo "\n";
            }
        }

        echo "\n\e[0;36mTotal: {$count} migration(s), " . count($executed) . " executed, " . (count($files) - count($executed)) . " pending.\e[0m\n";
    }
}
