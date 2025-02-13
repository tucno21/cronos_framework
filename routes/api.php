<?php

use Cronos\Routing\Route;
use App\Controllers\ApiController;

//api
Route::get('/prueba-api', [ApiController::class, 'index']);
Route::post('/prueba-api', [ApiController::class, 'create']);
Route::get('/prueba-api/{id}', [ApiController::class, 'show']);
// Route::put('/prueba-api/{id}', [ApiController::class, 'update']);
Route::put('/prueba-api', [ApiController::class, 'update']);
Route::delete('/prueba-api/{id}', [ApiController::class, 'destroy']);

//consultas elavoradas
Route::get('/consultas', [ApiController::class, 'consultaJoin']);
