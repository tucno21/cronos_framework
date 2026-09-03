<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class EtiquetaSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `etiquetas` (`nombre`, `slug`, `created_at`) VALUES
            ('PHP',     'php',     NOW()),
            ('MySQL',   'mysql',   NOW()),
            ('API',     'api',     NOW()),
            ('Laravel', 'laravel', NOW()),
            ('Docker',  'docker',  NOW()),
            ('Testing', 'testing', NOW());
        ");

        //pivot polimorfico n:m (morphToMany futuro)
        $this->pdo()->exec("
            INSERT INTO `etiquetables` (`etiqueta_id`, `etiquetable_tipo`, `etiquetable_id`, `created_at`) VALUES
            (1, 'publicaciones', 1,  NOW()),
            (2, 'publicaciones', 3,  NOW()),
            (3, 'publicaciones', 11, NOW());
        ");
    }
}
