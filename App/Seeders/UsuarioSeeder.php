<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        //usuarios 4, 6, 7 y 8 NO tienen perfil (probar relaciones nulas)
        $this->pdo()->exec("
            INSERT INTO `usuarios` (`nombre`, `correo`, `contrasena`, `rol`, `invitado_por`, `created_at`) VALUES
            ('Admin',  'admin@admin.com',  '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'admin',   NULL, NOW()),
            ('Editor', 'editor@admin.com', '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'editor',  NULL, NOW()),
            ('Carlos', 'carlos@admin.com', '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 1,    NOW()),
            ('Maria',  'maria@admin.com',  '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 3,    NOW()),
            ('Juan',   'juan@admin.com',   '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'editor',  1,    NOW()),
            ('Lucia',  'lucia@admin.com',  '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', NULL, NOW()),
            ('Pedro',  'pedro@admin.com',  '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 4,    NOW()),
            ('Ana',    'ana@admin.com',    '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'usuario', 3,    NOW());
        ");
    }
}
