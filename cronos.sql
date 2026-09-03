-- ============================================================
--  Schema MySQL - Framework PHP tipo Laravel
--  Esquema completo de pruebas para el ORM
--
--  Tablas (relaciones):
--    usuarios                       1:n publicaciones, 1:1 perfiles, n:m rol_usuario,
--                                   auto-referencial (invitado_por)
--    perfiles                       1:1 con usuarios
--    tokens_acceso                  1:n con usuarios
--    restablecimientos_contrasena   sin FK
--    categorias                     1:n publicaciones, auto-referencial (categoria_padre_id)
--    etiquetas                      n:m publicacion_etiqueta, n:m polimorfica etiquetables
--    publicaciones                  1:n comentarios, n:m publicacion_etiqueta
--    publicacion_etiqueta           pivote n:m
--    comentarios                    1:n doble (publicaciones y usuarios)
--    roles + rol_usuario            n:m con usuarios (pivote)
--    comentables                    1:n polimorfica (morphMany/morphTo futuro)
--    etiquetables                   n:m polimorfica (morphToMany futuro)
--
--  Convencion de nombres: tablas y columnas en espanol,
--  excepto id, created_at y updated_at (en ingles).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. usuarios
--    - invitado_por: auto-referencial (quien invito al usuario)
-- ------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id`                    INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre`                VARCHAR(100)  NOT NULL,
  `correo`                VARCHAR(191)  NOT NULL,
  `contrasena`            VARCHAR(255)  NOT NULL,
  `rol`                   ENUM('admin','editor','usuario') NOT NULL DEFAULT 'usuario',
  `avatar`                VARCHAR(255)  DEFAULT NULL,
  `correo_verificado_en`  TIMESTAMP     DEFAULT NULL,
  `token_recordar`        VARCHAR(100)  DEFAULT NULL,
  `invitado_por`          INT           DEFAULT NULL,
  `created_at`            TIMESTAMP     NULL DEFAULT NULL,
  `updated_at`            TIMESTAMP     NULL DEFAULT NULL,
  UNIQUE KEY `usuarios_correo_unique` (`correo`),
  FOREIGN KEY (`invitado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. perfiles  (1:1 con usuarios)
--    - usuario_id UNIQUE garantiza la relacion 1:1
-- ------------------------------------------------------------
CREATE TABLE `perfiles` (
  `id`               INT           AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`       INT           NOT NULL,
  `biografia`        TEXT          DEFAULT NULL,
  `telefono`         VARCHAR(50)   DEFAULT NULL,
  `fecha_nacimiento` DATE          DEFAULT NULL,
  `sitio_web`        VARCHAR(255)  DEFAULT NULL,
  `created_at`       TIMESTAMP     NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP     NULL DEFAULT NULL,
  UNIQUE KEY `perfiles_usuario_unique` (`usuario_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. tokens_acceso
-- ------------------------------------------------------------
CREATE TABLE `tokens_acceso` (
  `id`            INT          AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`    INT          NOT NULL,
  `nombre`        VARCHAR(100) NOT NULL,
  `token`         VARCHAR(64)  NOT NULL,
  `habilidades`   TEXT         DEFAULT NULL,
  `ultimo_uso_en` TIMESTAMP    DEFAULT NULL,
  `expira_en`     TIMESTAMP    DEFAULT NULL,
  `created_at`    TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY `tokens_acceso_token_unique` (`token`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. restablecimientos_contrasena
-- ------------------------------------------------------------
CREATE TABLE `restablecimientos_contrasena` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `correo`     VARCHAR(191) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NULL DEFAULT NULL,
  KEY `restablecimientos_correo_index` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. categorias
--    - categoria_padre_id: auto-referencial (arbol de categorias)
-- ------------------------------------------------------------
CREATE TABLE `categorias` (
  `id`                  INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`              VARCHAR(100) NOT NULL,
  `slug`                VARCHAR(100) NOT NULL,
  `descripcion`         TEXT         DEFAULT NULL,
  `categoria_padre_id`  INT          DEFAULT NULL,
  `created_at`          TIMESTAMP    NULL DEFAULT NULL,
  `updated_at`          TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY `categorias_slug_unique` (`slug`),
  FOREIGN KEY (`categoria_padre_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. etiquetas
-- ------------------------------------------------------------
CREATE TABLE `etiquetas` (
  `id`         INT         AUTO_INCREMENT PRIMARY KEY,
  `nombre`     VARCHAR(50) NOT NULL,
  `slug`       VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP   NULL DEFAULT NULL,
  UNIQUE KEY `etiquetas_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. publicaciones
--    - estado con soft delete (eliminado_en)
--    - vistas de 0 a 5000 para pruebas de agregados
-- ------------------------------------------------------------
CREATE TABLE `publicaciones` (
  `id`            INT          AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`    INT          NOT NULL,
  `categoria_id`  INT          DEFAULT NULL,
  `titulo`        VARCHAR(255) NOT NULL,
  `slug`          VARCHAR(191) NOT NULL,
  `resumen`       TEXT         DEFAULT NULL,
  `contenido`     LONGTEXT     NOT NULL,
  `imagen_portada` VARCHAR(255) DEFAULT NULL,
  `estado`        ENUM('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
  `vistas`        INT          NOT NULL DEFAULT 0,
  `publicado_en`  TIMESTAMP    DEFAULT NULL,
  `created_at`    TIMESTAMP    NULL DEFAULT NULL,
  `updated_at`    TIMESTAMP    NULL DEFAULT NULL,
  `eliminado_en`  TIMESTAMP    DEFAULT NULL,
  UNIQUE KEY `publicaciones_slug_unique` (`slug`),
  KEY `publicaciones_estado_index` (`estado`),
  FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`   (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. publicacion_etiqueta  (pivote n:m entre publicaciones y etiquetas)
-- ------------------------------------------------------------
CREATE TABLE `publicacion_etiqueta` (
  `publicacion_id` INT NOT NULL,
  `etiqueta_id`    INT NOT NULL,
  PRIMARY KEY (`publicacion_id`, `etiqueta_id`),
  FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`etiqueta_id`)    REFERENCES `etiquetas`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. comentarios  (1:n doble: publicaciones 1:n comentarios, usuarios 1:n comentarios)
-- ------------------------------------------------------------
CREATE TABLE `comentarios` (
  `id`             INT   AUTO_INCREMENT PRIMARY KEY,
  `publicacion_id` INT   NOT NULL,
  `usuario_id`     INT   NOT NULL,
  `contenido`      TEXT  NOT NULL,
  `created_at`     TIMESTAMP NULL DEFAULT NULL,
  `updated_at`     TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. roles  (n:m con usuarios)
-- ------------------------------------------------------------
CREATE TABLE `roles` (
  `id`          INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(50)  NOT NULL,
  `slug`        VARCHAR(50)  NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `created_at`  TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. rol_usuario  (pivote n:m entre usuarios y roles)
-- ------------------------------------------------------------
CREATE TABLE `rol_usuario` (
  `usuario_id` INT NOT NULL,
  `rol_id`     INT NOT NULL,
  PRIMARY KEY (`usuario_id`, `rol_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rol_id`)     REFERENCES `roles`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. comentables  (1:n polimorfica: comentable_tipo + comentable_id)
--     Tabla preparada para morphMany/morphTo cuando el ORM lo soporte
-- ------------------------------------------------------------
CREATE TABLE `comentables` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `comentable_tipo` VARCHAR(255) NOT NULL,
  `comentable_id`   INT          NOT NULL,
  `usuario_id`      INT          NOT NULL,
  `texto`           TEXT         NOT NULL,
  `created_at`      TIMESTAMP    NULL DEFAULT NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  KEY `comentables_comentable_index` (`comentable_tipo`, `comentable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. etiquetables  (n:m polimorfica: etiquetable_tipo + etiquetable_id)
--     Tabla preparada para morphToMany cuando el ORM lo soporte
-- ------------------------------------------------------------
CREATE TABLE `etiquetables` (
  `id`               INT          AUTO_INCREMENT PRIMARY KEY,
  `etiqueta_id`      INT          NOT NULL,
  `etiquetable_tipo` VARCHAR(255) NOT NULL,
  `etiquetable_id`   INT          NOT NULL,
  `created_at`       TIMESTAMP    NULL DEFAULT NULL,
  UNIQUE KEY `etiquetables_unicos` (`etiqueta_id`, `etiquetable_tipo`, `etiquetable_id`),
  FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  Seed de pruebas para el ORM
--  Incluye casos borde: relaciones nulas, valores extremos
--  para agregados, fechas repartidas en meses, borrados suaves
-- ============================================================

-- ------------------------------------------------------------
-- usuarios (8): roles variados, invitado_por encadenado
-- usuarios 4, 6, 7 y 8 NO tienen perfil (probar relaciones nulas)
-- ------------------------------------------------------------
INSERT INTO `usuarios` (`nombre`, `correo`, `contrasena`, `rol`, `invitado_por`, `created_at`) VALUES
  ('Admin',  'admin@admin.com',  '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'admin',   NULL, NOW()),
  ('Editor', 'editor@admin.com', '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'editor',  NULL, NOW()),
  ('Carlos', 'carlos@admin.com', '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 1,    NOW()),
  ('Maria',  'maria@admin.com',  '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 3,    NOW()),
  ('Juan',   'juan@admin.com',   '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'editor',  1,    NOW()),
  ('Lucia',  'lucia@admin.com',  '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', NULL, NOW()),
  ('Pedro',  'pedro@admin.com',  '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 4,    NOW()),
  ('Ana',    'ana@admin.com',    '$2y$10$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 3,    NOW());

-- ------------------------------------------------------------
-- perfiles (1:1): solo usuarios 1, 2, 3 y 5 tienen perfil
-- fechas_nacimiento repartidas para pruebas con fechas
-- ------------------------------------------------------------
INSERT INTO `perfiles` (`usuario_id`, `biografia`, `telefono`, `fecha_nacimiento`, `sitio_web`, `created_at`) VALUES
  (1, 'Administrador del sistema y fundador del blog.', '999-111-2233', '1990-05-14', 'https://admin.example.com', NOW()),
  (2, 'Editora de contenido tecnico, apasionada por las bases de datos.', '999-222-3344', '1985-11-02', NULL, NOW()),
  (3, 'Desarrollador backend en formacion.', NULL, '1998-03-22', 'https://carlos.example.dev', NOW()),
  (5, 'Editor senior y revisor de tutoriales.', '999-555-6677', '1979-08-30', NULL, NOW());

-- ------------------------------------------------------------
-- categorias: arbol de 3 niveles
--   Tecnologia (1) -> Frameworks (3) -> PHP (5)
--   Tecnologia (1) -> Bases de datos (4)
--   Opinión (6) y Tutoriales (2) sin padre
-- ------------------------------------------------------------
INSERT INTO `categorias` (`nombre`, `slug`, `descripcion`, `categoria_padre_id`, `created_at`) VALUES
  ('Tecnología',     'tecnologia',     'Todo sobre tecnologia y desarrollo.',        NULL, NOW()),
  ('Tutoriales',     'tutoriales',     'Guias paso a paso.',                         NULL, NOW()),
  ('Frameworks',     'frameworks',     'Frameworks PHP y de otras plataformas.',     1,    NOW()),
  ('Bases de datos', 'bases-de-datos', 'MySQL, Postgres y mas.',                     1,    NOW()),
  ('PHP',            'php',            'El lenguaje que hace posible todo esto.',    3,    NOW()),
  ('Opinión',        'opinion',        'Articulos de opinion de la comunidad.',      NULL, NOW());

-- ------------------------------------------------------------
-- etiquetas
-- ------------------------------------------------------------
INSERT INTO `etiquetas` (`nombre`, `slug`, `created_at`) VALUES
  ('PHP',     'php',     NOW()),
  ('MySQL',   'mysql',   NOW()),
  ('API',     'api',     NOW()),
  ('Laravel', 'laravel', NOW()),
  ('Docker',  'docker',  NOW()),
  ('Testing', 'testing', NOW());

-- ------------------------------------------------------------
-- publicaciones (12): 3 estados, vistas de 0 a 5000 (max/min/sum/avg),
-- publicado_en repartido en enero-marzo (whereBetween con fechas),
-- publicaciones con categoria NULL, usuario 4 con una sola publicacion,
-- usuario 6 sin publicaciones, publicacion 12 con soft delete
-- ------------------------------------------------------------
INSERT INTO `publicaciones` (`usuario_id`, `categoria_id`, `titulo`, `slug`, `resumen`, `contenido`, `estado`, `vistas`, `publicado_en`, `created_at`) VALUES
  (1, 1,    'Bienvenidos al framework Cronos',        'bienvenidos-framework-cronos',   'Presentacion del framework.',      'Contenido completo de bienvenida al framework Cronos.',        'publicado',  1520, '2026-01-10 10:00:00', NOW()),
  (2, 1,    'Primeros pasos con el ORM',              'primeros-pasos-orm',             'Aprende a usar el ORM basico.',    'Contenido completo del tutorial de primeros pasos con el ORM.', 'publicado',   890, '2026-01-22 09:30:00', NOW()),
  (3, 3,    'Creando modelos y relaciones',           'creando-modelos-relaciones',     'hasOne, hasMany y mas.',           'Contenido completo sobre modelos y relaciones del ORM.',        'publicado',  2450, '2026-02-05 11:00:00', NOW()),
  (2, 4,    'Consultas con MySQL desde PHP',          'consultas-mysql-php',            'PDO y prepared statements.',       'Contenido completo sobre consultas MySQL desde PHP con PDO.',   'publicado',   780, '2026-02-18 15:00:00', NOW()),
  (5, 5,    'Novedades de PHP 8.3',                   'novedades-php-83',               'Lo nuevo del lenguaje.',           'Contenido completo sobre las novedades de PHP 8.3.',            'publicado',  5000, '2026-03-01 08:00:00', NOW()),
  (1, 2,    'Tutorial de migraciones',                'tutorial-migraciones',           'Migra tu esquema paso a paso.',    'Contenido completo del tutorial de migraciones del framework.', 'publicado',   310, '2026-03-08 12:00:00', NOW()),
  (4, NULL, 'Opinion sobre los mini frameworks',      'opinion-mini-frameworks',        'Mi experiencia personal.',         'Contenido completo de opinion sobre los mini frameworks PHP.',  'borrador',     45, NULL, NOW()),
  (3, NULL, 'Borrador sin categoria',                 'borrador-sin-categoria',         'Aun en construccion.',             'Contenido incompleto de un borrador sin categoria asignada.',   'borrador',      0, NULL, NOW()),
  (5, 2,    'Guia de instalacion',                    'guia-instalacion',               'Instala el framework en minutos.', 'Contenido completo de la guia de instalacion del framework.',   'archivado',  1200, '2026-01-30 16:00:00', NOW()),
  (7, 6,    'Mi experiencia aprendiendo',             'mi-experiencia-aprendiendo',     'Como empece a programar.',         'Contenido completo sobre mi experiencia aprendiendo a programar.', 'borrador',    0, NULL, NOW()),
  (2, 1,    'Comparativa de ORMs en PHP',             'comparativa-orms-php',           'Cual ORM conviene y por que.',     'Contenido completo de la comparativa de ORMs disponibles en PHP.', 'publicado', 3340, '2026-02-27 14:00:00', NOW()),
  (8, 6,    'Post eliminado de prueba',               'post-eliminado-prueba',          'Este post fue eliminado.',         'Contenido de un post con soft delete para pruebas del ORM.',    'archivado',    75, '2026-01-05 13:00:00', NOW());

UPDATE `publicaciones` SET `eliminado_en` = NOW() WHERE `slug` = 'post-eliminado-prueba';

-- ------------------------------------------------------------
-- publicacion_etiqueta: publicaciones con 0, 1, 2 y 3 etiquetas
-- publicaciones 7, 8, 10 y 12 no tienen etiquetas
-- ------------------------------------------------------------
INSERT INTO `publicacion_etiqueta` (`publicacion_id`, `etiqueta_id`) VALUES
  (1, 1), (1, 3),
  (2, 1), (2, 2),
  (3, 1), (3, 4),
  (4, 2),
  (5, 1), (5, 5),
  (6, 3),
  (9, 5),
  (11, 1), (11, 4), (11, 6);

-- ------------------------------------------------------------
-- comentarios: distribucion irregular
-- publicacion 1: 6 comentarios | 2: 2 | 3: 4 |
-- 11: 2 | 5 y 6: 1 | publicaciones 4, 7, 8, 9, 10, 12: 0
-- ------------------------------------------------------------
INSERT INTO `comentarios` (`publicacion_id`, `usuario_id`, `contenido`, `created_at`) VALUES
  (1,  2, 'Gran presentacion, esperando mas tutoriales.', NOW()),
  (1,  3, 'Me encanta lo simple que es el enrutamiento.', NOW()),
  (1,  4, 'Funciona bien en mi entorno local.', NOW()),
  (1,  5, 'Revisado y aprobado por editorial.', NOW()),
  (1,  7, 'Primera vez que uso un mini framework.', NOW()),
  (1,  8, 'La documentacion esta muy clara.', NOW()),
  (2,  1, 'Buen punto de partida para el ORM.', NOW()),
  (2,  3, 'Me sirvio para entender las relaciones.', NOW()),
  (3,  2, 'Agregaria ejemplos de belongsToMany.', NOW()),
  (3,  4, 'Muy claro el ejemplo de hasMany.', NOW()),
  (3,  8, 'Funciono en mi proyecto de la universidad.', NOW()),
  (3,  7, 'Esperamos la parte de eager loading.', NOW()),
  (11, 1, 'Buena comparativa, faltaria Doctrine.', NOW()),
  (11, 5, 'Coincido con el analisis final.', NOW()),
  (5,  3, 'Las readonly properties son geniales.', NOW()),
  (6,  2, 'Serie de migraciones muy util.', NOW());

-- ------------------------------------------------------------
-- roles + rol_usuario: usuarios 1-5, 7 y 8 con roles;
-- usuario 6 (Lucia) sin roles; usuarios 5 y 8 con multi-rol
-- ------------------------------------------------------------
INSERT INTO `roles` (`nombre`, `slug`, `descripcion`, `created_at`) VALUES
  ('Administrador', 'administrador', 'Control total del sistema.',        NOW()),
  ('Editor',        'editor',        'Publica y edita contenido.',        NOW()),
  ('Usuario',       'usuario',       'Acceso basico de lectura.',         NOW()),
  ('Colaborador',   'colaborador',   'Puede proponer borradores.',        NOW());

INSERT INTO `rol_usuario` (`usuario_id`, `rol_id`) VALUES
  (1, 1),
  (2, 2),
  (3, 3),
  (4, 3),
  (5, 2), (5, 4),
  (7, 3),
  (8, 3), (8, 4);

-- ------------------------------------------------------------
-- comentables (polimorfica): comentarios sobre publicaciones y usuarios
-- ------------------------------------------------------------
INSERT INTO `comentables` (`comentable_tipo`, `comentable_id`, `usuario_id`, `texto`, `created_at`) VALUES
  ('publicaciones', 1, 4, 'Comentario polimorfico sobre la publicacion de bienvenida.', NOW()),
  ('publicaciones', 3, 2, 'Comentario polimorfico sobre modelos y relaciones.',         NOW()),
  ('usuarios',      1, 2, 'Mensaje en el perfil del administrador.',                    NOW());

-- ------------------------------------------------------------
-- etiquetables (polimorfica): etiquetas sobre publicaciones
-- ------------------------------------------------------------
INSERT INTO `etiquetables` (`etiqueta_id`, `etiquetable_tipo`, `etiquetable_id`, `created_at`) VALUES
  (1, 'publicaciones', 1,  NOW()),
  (2, 'publicaciones', 3,  NOW()),
  (3, 'publicaciones', 11, NOW());

-- ------------------------------------------------------------
-- tokens_acceso y restablecimientos_contrasena: ejemplo basico
-- ------------------------------------------------------------
INSERT INTO `tokens_acceso` (`usuario_id`, `nombre`, `token`, `habilidades`, `created_at`) VALUES
  (1, 'token-cli-admin', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', '["*"]', NOW());

INSERT INTO `restablecimientos_contrasena` (`correo`, `token`, `created_at`) VALUES
  ('maria@admin.com', 'reset-token-ejemplo-para-pruebas', NOW());
