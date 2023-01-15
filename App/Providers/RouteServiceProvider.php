<?php

namespace App\Providers;

use Cronos\App;
use Cronos\Routing\Route;
use Cronos\Provider\ServiceProvider;


class RouteServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        //almacenamos la ruta de la aplicacion
        Route::load(App::$root . "/routes");
    }
}
