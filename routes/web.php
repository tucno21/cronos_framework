<?php

use Cronos\Routing\Route;
use App\Controllers\UserController;
use Cronos\Routing\Request;

Route::get('/producto/{producto}', function (Request $request) {
    dd($request->dataGet());
});

Route::get('/', function () {
    echo 'Hello World s';
});

Route::get('/users/{id}', function (string $id) {
    echo $id;
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
