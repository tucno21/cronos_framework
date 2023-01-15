<?php


return [
    'boot' => [
        Cronos\Provider\DatabaseDriverServiceProvider::class,
        Cronos\Provider\SessionStorageServiceProvider::class,
        Cronos\Provider\ViewServiceProvider::class,
        Cronos\Provider\HasherServiceProvider::class,
    ],

    'runtime' => [
        App\Providers\RouteServiceProvider::class,
    ]
];
