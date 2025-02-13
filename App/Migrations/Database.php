<?php

namespace App\Migrations;

use Cronos\Database\DatabaseMigrate;

class Database extends DatabaseMigrate
{
    public function migrate()
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            echo "\nIniciando migración...\n";

            echo "Eliminando si existen tablas ...\n";
            $this->pdo->exec("
                DROP TABLE IF EXISTS `blogs`;
                DROP TABLE IF EXISTS `users`;
            ");

            echo "Creando tabla users...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(60),
                    `email` VARCHAR(60),
                    `password` VARCHAR(60),
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL
                );
            ");

            echo "Creando tabla blogs...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `blogs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(100),
                    `sglu` VARCHAR(150),
                    `content` TEXT,
                    `user_id` INT,
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
            ");

            echo "\nMigración completada exitosamente.\n";
            return true;
        } catch (\PDOException $e) {
            echo "\nError en la migración: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
