<?php

use Cronos\Routing\Route;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\RegisterController;
use App\Middlewares\DahboardMiddleware;
use App\Controllers\DashboardController;

Route::get('/', [HomeController::class, 'index'])->name('home.index')->middleware(DahboardMiddleware::class);

Route::get('/login', [LoginController::class, 'index'])->name('login.index')->middleware(DahboardMiddleware::class);
Route::post('/login', [LoginController::class, 'store']);
Route::get('/logout', [LoginController::class, 'logout'])->name('login.logout')->middleware(DahboardMiddleware::class);

Route::get('/register', [RegisterController::class, 'index'])->name('register.index')->middleware(DahboardMiddleware::class);
Route::post('/register', [RegisterController::class, 'create']);

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/create', [DashboardController::class, 'create'])->name('dashboard.create');
Route::post('/dashboard/create', [DashboardController::class, 'store']);
Route::get('/dashboard/{blog:slug}', [DashboardController::class, 'show'])->name('dashboard.show');
Route::get('/dashboard/{blog}/edit', [DashboardController::class, 'edit'])->name('dashboard.edit');
Route::post('/dashboard/{blog}/edit', [DashboardController::class, 'update']);
Route::get('/dashboard/{blog}/delete', [DashboardController::class, 'destroy'])->name('dashboard.destroy');
