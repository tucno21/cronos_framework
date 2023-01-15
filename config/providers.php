<?php


return [
    'boot' => [
        Cronos\Provider\DatabaseDriverServiceProvider::class,
        Cronos\Provider\SessionStorageServiceProvider::class,
        Cronos\Provider\ViewServiceProvider::class,
    ],

    'runtime' => []
];
