<?php

namespace App\Seeders;

use Cronos\Database\Seeder;

class ComentarioSeeder extends Seeder
{
    public function run(): void
    {
        //distribucion irregular: publicacion 1: 6, 2: 2, 3: 4, 11: 2, 5: 1, 6: 1
        $this->pdo()->exec("
            INSERT INTO `comentarios` (`publicacion_id`, `usuario_id`, `contenido`, `created_at`) VALUES
            (1,  2, 'Gran presentacion, esperando mas tutoriales.', NOW()),
            (1,  3, 'Me encanta lo simple que es el enrutamiento.', NOW()),
            (1,  4, 'Funciona bien en mi entorno local.', NOW()),
            (1,  5, 'Revisado y aprobado por editorial.', NOW()),
            (1,  7, 'Primera vez que uso un mini framework.', NOW()),
            (1,  8, 'La documentacion esta muy clara.', NOW()),
            (2,  1, 'Buen punto de partida para el ORM.', NOW()),
            (2,  3, 'Me sirvio para entender las relaciones.', NOW()),
            (3,  2, 'Agregaria ejemplos de belongsToMany.', NOW()),
            (3,  4, 'Muy claro el ejemplo de hasMany.', NOW()),
            (3,  8, 'Funciono en mi proyecto de la universidad.', NOW()),
            (3,  7, 'Esperamos la parte de eager loading.', NOW()),
            (11, 1, 'Buena comparativa, faltaria Doctrine.', NOW()),
            (11, 5, 'Coincido con el analisis final.', NOW()),
            (5,  3, 'Las readonly properties son geniales.', NOW()),
            (6,  2, 'Serie de migraciones muy util.', NOW());
        ");

        //pivot polimorfico 1:n (morphMany/morphTo futuro)
        $this->pdo()->exec("
            INSERT INTO `comentables` (`comentable_tipo`, `comentable_id`, `usuario_id`, `texto`, `created_at`) VALUES
            ('publicaciones', 1, 4, 'Comentario polimorfico sobre la publicacion de bienvenida.', NOW()),
            ('publicaciones', 3, 2, 'Comentario polimorfico sobre modelos y relaciones.',         NOW()),
            ('usuarios',      1, 2, 'Mensaje en el perfil del administrador.',                    NOW());
        ");
    }
}
