<?php

use App\Controllers\UserController;

$app->get('/', function () {
    echo 'Hello World s';
});

$app->get('/users', function () {
    echo 'Hello Users';
});

$app->post('/post', function () {
    echo 'Hello post';
});

$app->put('/put', function () {
    echo 'Hello put';
});

$app->patch('/patch', function () {
    echo 'Hello patch';
});

$app->delete('/delete', function () {
    echo 'Hello delete';
});

$app->get('/get_controller', [UserController::class, 'index']);
$app->post('/post_controller', [UserController::class, 'store']);
$app->put('/put_controller', [UserController::class, 'update']);
$app->patch('/patch_controller', [UserController::class, 'update']);
$app->delete('/delete_controller', [UserController::class, 'destroy']);
