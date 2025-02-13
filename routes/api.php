<?php

use Cronos\Routing\Route;
use App\Controllers\ApiController;
use App\Middlewares\AuthApiMiddleware;

//api
Route::get('/prueba-api', [ApiController::class, 'index'])->middleware([AuthApiMiddleware::class]);;
Route::post('/prueba-api', [ApiController::class, 'create']);
Route::get('/prueba-api/{id}', [ApiController::class, 'show']);
// Route::put('/prueba-api/{id}', [ApiController::class, 'update']);
Route::put('/prueba-api', [ApiController::class, 'update']);
Route::delete('/prueba-api/{id}', [ApiController::class, 'destroy']);

//consultas elavoradas
Route::get('/consultas', [ApiController::class, 'consultaJoin']);

//PRUEBA DE AGRUPACION DE RUTAS
Route::group(['prefix' => '/dashboard'], function () {
    Route::get('/users', [ApiController::class, 'grupos']);
    Route::post('/users', [ApiController::class, 'gruposStore']);
});

Route::group(['prefix' => '/panel-control', 'middleware' => [AuthApiMiddleware::class]], function () {
    Route::get('/users', [ApiController::class, 'grupos']);
    Route::post('/users', [ApiController::class, 'gruposStore']);
});
