<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class TokenSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `tokens_acceso` (`usuario_id`, `nombre`, `token`, `habilidades`, `created_at`) VALUES
            (1, 'token-cli-admin', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', '[\"*\"]', NOW());
        ");

        $this->pdo()->exec("
            INSERT INTO `restablecimientos_contrasena` (`correo`, `token`, `created_at`) VALUES
            ('maria@admin.com', 'reset-token-ejemplo-para-pruebas', NOW());
        ");
    }
}
