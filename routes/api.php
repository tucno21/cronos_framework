<?php

use Cronos\Routing\Route;
use App\Controllers\ApiController;

//api
Route::get('/api/prueba-api', [ApiController::class, 'index']);
Route::post('/api/prueba-api', [ApiController::class, 'create']);
Route::get('/api/prueba-api/{id}', [ApiController::class, 'show']);
Route::put('/api/prueba-api/{id}', [ApiController::class, 'update']);
Route::delete('/api/prueba-api/{id}', [ApiController::class, 'destroy']);

//consultas elavoradas
Route::get('/api/consultas', [ApiController::class, 'consultaJoin']);
