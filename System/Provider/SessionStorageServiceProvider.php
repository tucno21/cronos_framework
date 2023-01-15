<?php

namespace Cronos\Provider;

use Cronos\Container\Container;
use Cronos\Session\SessionStorage;
use Cronos\Provider\ServiceProvider;
use Cronos\Session\PhpNativeSessionStorage;

class SessionStorageServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (configGet('session.storage', 'native')) {
            //si del archivo session.php se obtiene el valor de la clave 'storage' es 'native'
            //ejecutamos PhpNativeSessionStorage de nuestro sistema
            'native' => Container::singleton(
                SessionStorage::class,
                PhpNativeSessionStorage::class
            ),
        };
    }
}
