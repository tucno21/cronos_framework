<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        //solo usuarios 1, 2, 3 y 5 tienen perfil
        $this->pdo()->exec("
            INSERT INTO `perfiles` (`usuario_id`, `biografia`, `telefono`, `fecha_nacimiento`, `sitio_web`, `created_at`) VALUES
            (1, 'Administrador del sistema y fundador del blog.', '999-111-2233', '1990-05-14', 'https://admin.example.com', NOW()),
            (2, 'Editora de contenido tecnico, apasionada por las bases de datos.', '999-222-3344', '1985-11-02', NULL, NOW()),
            (3, 'Desarrollador backend en formacion.', NULL, '1998-03-22', 'https://carlos.example.dev', NOW()),
            (5, 'Editor senior y revisor de tutoriales.', '999-555-6677', '1979-08-30', NULL, NOW());
        ");
    }
}
