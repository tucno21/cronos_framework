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
            echo "\nIniciando migracion...\n";

            echo "Eliminando si existen tablas ...\n";
            $this->pdo->exec("
                SET FOREIGN_KEY_CHECKS = 0;
                DROP TABLE IF EXISTS `post_tag`;
                DROP TABLE IF EXISTS `posts`;
                DROP TABLE IF EXISTS `tags`;
                DROP TABLE IF EXISTS `categories`;
                DROP TABLE IF EXISTS `password_resets`;
                DROP TABLE IF EXISTS `personal_access_tokens`;
                DROP TABLE IF EXISTS `users`;
                SET FOREIGN_KEY_CHECKS = 1;
            ");

            echo "Creando tabla users...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id`                INT           AUTO_INCREMENT PRIMARY KEY,
                    `name`              VARCHAR(100)  NOT NULL,
                    `email`             VARCHAR(191)  NOT NULL,
                    `password`          VARCHAR(255)  NOT NULL,
                    `role`              ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
                    `avatar`            VARCHAR(255)  DEFAULT NULL,
                    `email_verified_at` TIMESTAMP     DEFAULT NULL,
                    `remember_token`    VARCHAR(100)  DEFAULT NULL,
                    `created_at`        TIMESTAMP     NULL DEFAULT NULL,
                    `updated_at`        TIMESTAMP     NULL DEFAULT NULL,
                    UNIQUE KEY `users_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "Creando tabla personal_access_tokens...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
                    `id`           INT          AUTO_INCREMENT PRIMARY KEY,
                    `user_id`      INT          NOT NULL,
                    `name`         VARCHAR(100) NOT NULL,
                    `token`        VARCHAR(64)  NOT NULL,
                    `abilities`    TEXT         DEFAULT NULL,
                    `last_used_at` TIMESTAMP    DEFAULT NULL,
                    `expires_at`   TIMESTAMP    DEFAULT NULL,
                    `created_at`   TIMESTAMP    NULL DEFAULT NULL,
                    UNIQUE KEY `tokens_token_unique` (`token`),
                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "Creando tabla password_resets...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `password_resets` (
                    `id`         INT          AUTO_INCREMENT PRIMARY KEY,
                    `email`      VARCHAR(191) NOT NULL,
                    `token`      VARCHAR(255) NOT NULL,
                    `created_at` TIMESTAMP    NULL DEFAULT NULL,
                    KEY `password_resets_email_index` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "Creando tabla categories...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id`          INT          AUTO_INCREMENT PRIMARY KEY,
                    `name`        VARCHAR(100) NOT NULL,
                    `slug`        VARCHAR(100) NOT NULL,
                    `description` TEXT         DEFAULT NULL,
                    `created_at`  TIMESTAMP    NULL DEFAULT NULL,
                    `updated_at`  TIMESTAMP    NULL DEFAULT NULL,
                    UNIQUE KEY `categories_slug_unique` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "Creando tabla tags...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `tags` (
                    `id`         INT         AUTO_INCREMENT PRIMARY KEY,
                    `name`       VARCHAR(50) NOT NULL,
                    `slug`       VARCHAR(50) NOT NULL,
                    `created_at` TIMESTAMP   NULL DEFAULT NULL,
                    UNIQUE KEY `tags_slug_unique` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "Creando tabla posts...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `posts` (
                    `id`           INT          AUTO_INCREMENT PRIMARY KEY,
                    `user_id`      INT          NOT NULL,
                    `category_id`  INT          DEFAULT NULL,
                    `title`        VARCHAR(255) NOT NULL,
                    `slug`         VARCHAR(191) NOT NULL,
                    `excerpt`      TEXT         DEFAULT NULL,
                    `content`      LONGTEXT     NOT NULL,
                    `cover_image`  VARCHAR(255) DEFAULT NULL,
                    `status`       ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
                    `views`        INT          NOT NULL DEFAULT 0,
                    `published_at` TIMESTAMP    DEFAULT NULL,
                    `created_at`   TIMESTAMP    NULL DEFAULT NULL,
                    `updated_at`   TIMESTAMP    NULL DEFAULT NULL,
                    `deleted_at`   TIMESTAMP    DEFAULT NULL,
                    UNIQUE KEY `posts_slug_unique` (`slug`),
                    KEY `posts_status_index` (`status`),
                    FOREIGN KEY (`user_id`)     REFERENCES `users`      (`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "Creando tabla post_tag...\n";
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `post_tag` (
                    `post_id` INT NOT NULL,
                    `tag_id`  INT NOT NULL,
                    PRIMARY KEY (`post_id`, `tag_id`),
                    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`tag_id`)  REFERENCES `tags`  (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            echo "\nMigracion completada exitosamente.\n";
            return true;
        } catch (\PDOException $e) {
            echo "\nError en la migracion: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
