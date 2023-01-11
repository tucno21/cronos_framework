<?php

use Cronos\Routing\Router;

$app = new Router();

$app->get('/', function () {
    return 'Hello World';
});

$app->get('/users', function () {
    return 'Hello Users';
});

$app->post('/post', function () {
    return 'Hello post';
});

$app->put('/put', function () {
    return 'Hello put';
});

$app->patch('/patch', function () {
    return 'Hello patch';
});

$app->delete('/delete', function () {
    return 'Hello delete';
});

$app->get('/get_controller', [UserController::class, 'index']);
$app->post('/post_controller', [UserController::class, 'store']);
$app->put('/put_controller', [UserController::class, 'update']);
$app->patch('/patch_controller', [UserController::class, 'update']);
$app->delete('/delete_controller', [UserController::class, 'destroy']);


$app->resolve($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
