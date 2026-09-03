<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        //arbol de 3 niveles: 1 -> 3 -> 5, 1 -> 4
        $this->pdo()->exec("
            INSERT INTO `categorias` (`nombre`, `slug`, `descripcion`, `categoria_padre_id`, `created_at`) VALUES
            ('Tecnología',     'tecnologia',     'Todo sobre tecnologia y desarrollo.',     NULL, NOW()),
            ('Tutoriales',     'tutoriales',     'Guias paso a paso.',                      NULL, NOW()),
            ('Frameworks',     'frameworks',     'Frameworks PHP y de otras plataformas.',  1,    NOW()),
            ('Bases de datos', 'bases-de-datos', 'MySQL, Postgres y mas.',                  1,    NOW()),
            ('PHP',            'php',            'El lenguaje que hace posible todo esto.', 3,    NOW()),
            ('Opinión',        'opinion',        'Articulos de opinion de la comunidad.',   NULL, NOW());
        ");
    }
}
