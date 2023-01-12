<?php

use Cronos\Routing\Route;
use App\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index']);
Route::get('/form', [HomeController::class, 'form']);
Route::post('/form', [HomeController::class, 'store']);
