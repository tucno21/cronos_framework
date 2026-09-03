<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `roles` (`nombre`, `slug`, `descripcion`, `created_at`) VALUES
            ('Administrador', 'administrador', 'Control total del sistema.', NOW()),
            ('Editor',        'editor',        'Publica y edita contenido.', NOW()),
            ('Usuario',       'usuario',       'Acceso basico de lectura.',  NOW()),
            ('Colaborador',   'colaborador',   'Puede proponer borradores.', NOW());
        ");

        //usuario 6 (Lucia) sin roles; usuarios 5 y 8 con multi-rol
        $this->pdo()->exec("
            INSERT INTO `rol_usuario` (`usuario_id`, `rol_id`) VALUES
            (1, 1),
            (2, 2),
            (3, 3),
            (4, 3),
            (5, 2), (5, 4),
            (7, 3),
            (8, 3), (8, 4);
        ");
    }
}
