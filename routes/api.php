<?php

use Cronos\Routing\Route;
use App\Controllers\AuthController;
use App\Controllers\PublicacionController;
use App\Middlewares\AuthApiMiddleware;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => [AuthApiMiddleware::class]], function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/blogs', [PublicacionController::class, 'index']);
    Route::post('/blogs', [PublicacionController::class, 'store']);
    Route::get('/blogs/{publicacion:slug}', [PublicacionController::class, 'show']);
    Route::put('/blogs/{publicacion}', [PublicacionController::class, 'update']);
    Route::delete('/blogs/{publicacion}', [PublicacionController::class, 'destroy']);
});
