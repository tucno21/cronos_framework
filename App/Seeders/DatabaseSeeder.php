<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
            PerfilSeeder::class,
            CategoriaSeeder::class,
            EtiquetaSeeder::class,
            PublicacionSeeder::class,
            ComentarioSeeder::class,
            RolSeeder::class,
            TokenSeeder::class,
        ]);
    }
}
