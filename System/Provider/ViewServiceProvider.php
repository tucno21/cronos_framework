<?php

namespace Cronos\Provider;

use Cronos\View\View;
use Cronos\View\CronosEngine;
use Cronos\Provider\ServiceProvider;

class ViewServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        //match es una expresion que se utiliza para comparar un valor con diferentes patrones
        match (configGet('view.engine', 'cronos')) {
            //si del archivo view.php se obtiene el valor de la clave 'engine' es cronos
            //ejecutamos CronosEngine de nuestro sistema
            'cronos' => singleton(
                View::class,
                fn () => new CronosEngine(configGet('view.path'), configGet('view.cache'))
            ),
        };
    }
}
