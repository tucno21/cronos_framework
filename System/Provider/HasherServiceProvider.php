<?php

namespace Cronos\Provider;

use Cronos\Crypto\Bcrypt;
use Cronos\Crypto\Hasher;
use Cronos\Container\Container;
use Cronos\Provider\ServiceProvider;

class HasherServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (configGet("hashing.hasher", "bcrypt")) {
            //si del archivo hashing.php se obtiene el valor de la clave 'hasher' es bcrypt
            //ejecutamos Hasher que es la interface y instanciamos Bcrypt que es la clase que implementa la interface
            "bcrypt" => Container::singleton(Hasher::class, Bcrypt::class),
        };
    }
}
