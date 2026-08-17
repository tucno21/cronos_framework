<?php

use Cronos\Routing\Route;
use App\Controllers\HomeController;
use App\Middlewares\DahboardMiddleware;
use App\Controllers\SpaController;

Route::get('/', [HomeController::class, 'index'])->name('home.index')->middleware(DahboardMiddleware::class);

Route::get('/login', [SpaController::class, 'index'])->name('login.index');
Route::get('/register', [SpaController::class, 'index'])->name('register.index');

//Rutas SPA (Dashboard)
Route::get('/dashboard', [SpaController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/blogs', [SpaController::class, 'index']);
Route::get('/dashboard/blogs/crear', [SpaController::class, 'index']);
Route::get('/dashboard/blogs/{slug}', [SpaController::class, 'index']);
Route::get('/dashboard/blogs/{slug}/editar', [SpaController::class, 'index']);
