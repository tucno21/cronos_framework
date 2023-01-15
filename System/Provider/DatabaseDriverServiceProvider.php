<?php

namespace Cronos\Provider;

use Cronos\Database\PdoDriver;
use Cronos\Database\DatabaseDriver;
use Cronos\Provider\ServiceProvider;


class DatabaseDriverServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        //si del archivo database.php se obtiene el valor de la clave 'connection' es 'mysql'
        //ejecutamos el PdoDriver::class de nuestro sistema
        match (configGet('database.connection', 'mysql')) {
            'mysql', 'pgsql' => singleton(
                DatabaseDriver::class,
                PdoDriver::class
            ),
        };
    }
}
