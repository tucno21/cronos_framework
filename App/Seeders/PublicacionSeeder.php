<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class PublicacionSeeder extends Seeder
{
    public function run(): void
    {
        //12 publicaciones: 3 estados, vistas 0-5000 (agregados),
        //fechas enero-marzo (whereBetween), 2 sin categoria,
        //usuario 6 sin publicaciones, publicacion 12 con soft delete
        $this->pdo()->exec("
            INSERT INTO `publicaciones` (`usuario_id`, `categoria_id`, `titulo`, `slug`, `resumen`, `contenido`, `estado`, `vistas`, `publicado_en`, `created_at`) VALUES
            (1, 1,    'Bienvenidos al framework Cronos',   'bienvenidos-framework-cronos',   'Presentacion del framework.',      'Contenido completo de bienvenida al framework Cronos.',        'publicado',  1520, '2026-01-10 10:00:00', NOW()),
            (2, 1,    'Primeros pasos con el ORM',         'primeros-pasos-orm',             'Aprende a usar el ORM basico.',    'Contenido completo del tutorial de primeros pasos con el ORM.', 'publicado',   890, '2026-01-22 09:30:00', NOW()),
            (3, 3,    'Creando modelos y relaciones',      'creando-modelos-relaciones',     'hasOne, hasMany y mas.',           'Contenido completo sobre modelos y relaciones del ORM.',        'publicado',  2450, '2026-02-05 11:00:00', NOW()),
            (2, 4,    'Consultas con MySQL desde PHP',     'consultas-mysql-php',            'PDO y prepared statements.',       'Contenido completo sobre consultas MySQL desde PHP con PDO.',   'publicado',   780, '2026-02-18 15:00:00', NOW()),
            (5, 5,    'Novedades de PHP 8.3',              'novedades-php-83',               'Lo nuevo del lenguaje.',           'Contenido completo sobre las novedades de PHP 8.3.',            'publicado',  5000, '2026-03-01 08:00:00', NOW()),
            (1, 2,    'Tutorial de migraciones',           'tutorial-migraciones',           'Migra tu esquema paso a paso.',    'Contenido completo del tutorial de migraciones del framework.', 'publicado',   310, '2026-03-08 12:00:00', NOW()),
            (4, NULL, 'Opinion sobre los mini frameworks', 'opinion-mini-frameworks',        'Mi experiencia personal.',         'Contenido completo de opinion sobre los mini frameworks PHP.',  'borrador',     45, NULL, NOW()),
            (3, NULL, 'Borrador sin categoria',            'borrador-sin-categoria',         'Aun en construccion.',             'Contenido incompleto de un borrador sin categoria asignada.',   'borrador',      0, NULL, NOW()),
            (5, 2,    'Guia de instalacion',               'guia-instalacion',               'Instala el framework en minutos.', 'Contenido completo de la guia de instalacion del framework.',   'archivado',  1200, '2026-01-30 16:00:00', NOW()),
            (7, 6,    'Mi experiencia aprendiendo',        'mi-experiencia-aprendiendo',     'Como empece a programar.',         'Contenido completo sobre mi experiencia aprendiendo a programar.', 'borrador',    0, NULL, NOW()),
            (2, 1,    'Comparativa de ORMs en PHP',        'comparativa-orms-php',           'Cual ORM conviene y por que.',     'Contenido completo de la comparativa de ORMs disponibles en PHP.', 'publicado', 3340, '2026-02-27 14:00:00', NOW()),
            (8, 6,    'Post eliminado de prueba',          'post-eliminado-prueba',          'Este post fue eliminado.',         'Contenido de un post con soft delete para pruebas del ORM.',    'archivado',    75, '2026-01-05 13:00:00', NOW());
        ");

        $this->pdo()->exec("
            UPDATE `publicaciones` SET `eliminado_en` = NOW() WHERE `slug` = 'post-eliminado-prueba';
        ");

        $this->pdo()->exec("
            INSERT INTO `publicacion_etiqueta` (`publicacion_id`, `etiqueta_id`) VALUES
            (1, 1), (1, 3),
            (2, 1), (2, 2),
            (3, 1), (3, 4),
            (4, 2),
            (5, 1), (5, 5),
            (6, 3),
            (9, 5),
            (11, 1), (11, 4), (11, 6);
        ");
    }
}
