<?php

use Cronos\Routing\Route;
use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\ComentarioController;
use App\Controllers\DashboardController;
use App\Controllers\EtiquetaController;
use App\Controllers\OrmLabController;
use App\Controllers\PublicacionController;
use App\Controllers\UsuarioController;
use App\Middlewares\AuthApiMiddleware;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => [AuthApiMiddleware::class]], function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Métricas del Dashboard con Agregados ORM
    Route::get('/stats', [DashboardController::class, 'stats']);

    // Usuarios y Perfiles (1:1, N:M con roles, auto-referencial invitados)
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);

    // Categorías y Árbol (Auto-referencial)
    Route::get('/categorias/arbol', [CategoriaController::class, 'arbol']);
    Route::get('/categorias', [CategoriaController::class, 'index']);

    // Etiquetas (N:M con publicaciones)
    Route::get('/etiquetas', [EtiquetaController::class, 'index']);

    // Comentarios (1:N con publicaciones y usuarios)
    Route::get('/comentarios', [ComentarioController::class, 'index']);
    Route::delete('/comentarios/{id}', [ComentarioController::class, 'destroy']);

    // Laboratorio / Explorador de Consultas del ORM Cronos
    Route::get('/orm-lab', [OrmLabController::class, 'index']);

    // Blogs / Publicaciones (mutaciones protegidas)
    Route::post('/blogs', [PublicacionController::class, 'store']);
    Route::put('/blogs/{publicacion}', [PublicacionController::class, 'update']);
    Route::delete('/blogs/{publicacion}', [PublicacionController::class, 'destroy']);
});

// Blogs de consulta (públicos / lectura)
Route::get('/blogs', [PublicacionController::class, 'index']);
Route::get('/blogs/{publicacion:slug}', [PublicacionController::class, 'show']);

