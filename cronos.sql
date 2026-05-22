-- ============================================================
--  Schema MySQL - Framework PHP tipo Laravel
--  Tablas: users, personal_access_tokens, password_resets,
--          categories, tags, posts, post_tag
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. users
-- ------------------------------------------------------------
CREATE TABLE `users` (
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

-- ------------------------------------------------------------
-- 2. personal_access_tokens
-- ------------------------------------------------------------
CREATE TABLE `personal_access_tokens` (
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

-- ------------------------------------------------------------
-- 3. password_resets
-- ------------------------------------------------------------
CREATE TABLE `password_resets` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(191) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. categories
-- ------------------------------------------------------------
CREATE TABLE `categories` (
  `id`          INT          AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(100) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. tags
-- ------------------------------------------------------------
CREATE TABLE `tags` (
  `id`         INT         AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(50) NOT NULL,
  `slug`       VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP   NULL DEFAULT NULL,
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. posts  (era "blogs" en tu schema anterior)
-- ------------------------------------------------------------
CREATE TABLE `posts` (
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

-- ------------------------------------------------------------
-- 7. post_tag  (pivote many-to-many)
-- ------------------------------------------------------------
CREATE TABLE `post_tag` (
  `post_id` INT NOT NULL,
  `tag_id`  INT NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`)  REFERENCES `tags`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  Seed básico de prueba
-- ============================================================

INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`) VALUES
  ('Admin', 'admin@admin.com', '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'admin', NOW()),
  ('Editor', 'editor@admin.com', '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'editor', NOW());


INSERT INTO `categories` (`name`, `slug`, `created_at`) VALUES
  ('Tecnología', 'tecnologia', NOW()),
  ('Tutoriales', 'tutoriales', NOW());

INSERT INTO `tags` (`name`, `slug`, `created_at`) VALUES
  ('PHP', 'php', NOW()),
  ('MySQL', 'mysql', NOW()),
  ('API', 'api', NOW());

INSERT INTO `posts` (`user_id`, `category_id`, `title`, `slug`, `excerpt`, `content`, `status`, `published_at`, `created_at`) VALUES
  (1, 1, 'Mi primer post', 'mi-primer-post', 'Resumen del post.', 'Contenido completo del post de prueba.', 'published', NOW(), NOW());

INSERT INTO `post_tag` (`post_id`, `tag_id`) VALUES (1, 1), (1, 3);