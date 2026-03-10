<?php

// require_once __DIR__ . "./../vendor/autoload.php";
require_once "./../vendor/autoload.php";

// Registrar ExceptionHandler ANTES de inicializar la aplicación
\Cronos\Errors\ExceptionHandler::register();

Cronos\App::bootstrap(dirname(__DIR__))->run();
