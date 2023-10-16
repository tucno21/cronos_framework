CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(60),
  `email` VARCHAR(60),
  `password` VARCHAR(60),
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
);


CREATE TABLE `blogs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100),
  `sglu` VARCHAR(150),
  `content` TEXT,
  `user_id` INT,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO `users` (`name`, `email`, `password`, `created_at`, `updated_at`) VALUES
('admin', 'admin@admin.com', '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', '2023-01-19 17:43:28', '2023-01-19 17:43:28');

INSERT INTO `blogs` (`title`, `slug`, `content`, `user_id`, `created_at`, `updated_at`) VALUES
('Mi Primer Titulo de BLog', 'mi-primer-titulo-de-blog', 'mi contenido de este blog cambio', 1, '2023-01-19 19:52:06', '2023-10-04 23:24:40');
