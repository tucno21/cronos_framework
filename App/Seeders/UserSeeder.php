<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `users` (`name`, `email`, `password`, `role`, `created_at`) VALUES
            ('Admin', 'admin@admin.com', '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'admin', NOW()),
            ('Editor', 'editor@admin.com', '\$2y\$10\$hhq6q6ZpdOvfMvLxZkdH9elRyNIP.au0fOPMORZnKhRMRJScsNzBa', 'editor', NOW());
        ");
    }
}
