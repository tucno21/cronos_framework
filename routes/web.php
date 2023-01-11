<?php

use Cronos\Routing\Route;
use App\Controllers\UserController;

Route::get('/producto/{producto}', function () {
    echo 'Hello producto';
});

Route::get('/', function () {
    echo 'Hello World s';
});

Route::get('/users', function () {
    echo 'Hello Users';
});

Route::post('/post', function () {
    echo 'Hello post';
});

Route::put('/put', function () {
    echo 'Hello put';
});

Route::patch('/patch', function () {
    echo 'Hello patch';
});

Route::delete('/delete', function () {
    echo 'Hello delete';
});

Route::get('/get_controller', [UserController::class, 'index']);
Route::post('/post_controller', [UserController::class, 'store']);
Route::put('/put_controller', [UserController::class, 'update']);
Route::patch('/patch_controller', [UserController::class, 'update']);
Route::delete('/delete_controller', [UserController::class, 'destroy']);
