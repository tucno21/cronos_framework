<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `tags` (`name`, `slug`, `created_at`) VALUES
            ('PHP', 'php', NOW()),
            ('MySQL', 'mysql', NOW()),
            ('API', 'api', NOW());
        ");
    }
}
