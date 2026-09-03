<?php

namespace Cronos\Database;

use PDO;

abstract class Seeder
{
    protected static ?PDO $sharedPdo = null;

    abstract public function run(): void;

    protected function pdo(): PDO
    {
        if (is_null(static::$sharedPdo)) {
            $migrate = new DatabaseMigrate();

            if (!$migrate->connect()) {
                throw new \RuntimeException('No se pudo conectar a MySQL para ejecutar los seeds');
            }

            static::$sharedPdo = $migrate->getPDO();
        }

        return static::$sharedPdo;
    }

    public function call(array $seeders): void
    {
        foreach ($seeders as $seeder) {
            if (!class_exists($seeder)) {
                throw new \RuntimeException("No existe el seeder: {$seeder}");
            }

            echo "\nSeeding: {$seeder}";
            (new $seeder)->run();
        }
    }
}
