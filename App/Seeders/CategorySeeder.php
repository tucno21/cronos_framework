<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `categories` (`name`, `slug`, `created_at`) VALUES
            ('Tecnología', 'tecnologia', NOW()),
            ('Tutoriales', 'tutoriales', NOW());
        ");
    }
}
