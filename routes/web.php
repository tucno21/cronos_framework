<?php

use Cronos\Routing\Route;
use App\Controllers\HomeController;
use App\Middlewares\LoginMiddleware;
use App\Controllers\DashboardController;

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/login', [HomeController::class, 'login'])->name('home.login')->middleware(LoginMiddleware::class);
Route::post('/login', [HomeController::class, 'store']);
Route::get('/register', [HomeController::class, 'register'])->name('home.register')->middleware(LoginMiddleware::class);
Route::post('/register', [HomeController::class, 'create']);
Route::get('/logout', [HomeController::class, 'logout'])->name('home.logout');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/user/{id}', [DashboardController::class, 'user'])->name('dashboard.user');
