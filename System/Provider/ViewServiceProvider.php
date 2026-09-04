<?php

namespace Cronos\Provider;

use Cronos\View\View;
use Cronos\View\BladeEngine;
use Cronos\Provider\ServiceProvider;

class ViewServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        //match es una expresion que se utiliza para comparar un valor con diferentes patrones
        match (configGet('view.engine', 'blade')) {
            //motor de plantillas blade: compila a php plano con cache por dependencias
            'blade' => singleton(
                View::class,
                fn () => new BladeEngine(configGet('view.path'), configGet('view.cache'))
            ),
            default => throw new \InvalidArgumentException(
                'Motor de vistas no soportado: ' . configGet('view.engine', 'blade')
            ),
        };
    }
}
