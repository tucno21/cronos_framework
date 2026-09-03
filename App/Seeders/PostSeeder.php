<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo()->exec("
            INSERT INTO `posts` (`user_id`, `category_id`, `title`, `slug`, `excerpt`, `content`, `status`, `published_at`, `created_at`) VALUES
            (1, 1, 'Mi primer post', 'mi-primer-post', 'Resumen del post.', 'Contenido completo del post de prueba.', 'published', NOW(), NOW());

            INSERT INTO `post_tag` (`post_id`, `tag_id`) VALUES (1, 1), (1, 3);
        ");
    }
}
