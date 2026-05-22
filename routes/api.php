<?php

use Cronos\Routing\Route;
use App\Controllers\AuthController;
use App\Middlewares\AuthApiMiddleware;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => [AuthApiMiddleware::class]], function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
