<?php

// use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Routing\Route;
use Cronos\Http\Middleware;
use App\Controllers\HomeController;


// class AuthMiddleware implements Middleware
// {
//     public function handle(Request $request, Closure $next): Response
//     {
//         if ($request->headers('Connection') !== 'keep-alive') {
//             return json(['message' => 'Unauthorized'], 401);
//         }

//         return $next($request);
//     }
// }
// class LoginMiddleware implements Middleware
// {
//     public function handle(Request $request, Closure $next): Response
//     {
//         if ($request->name === "" || $request->email === "") {
//             return json(['message' => 'Unauthorized'], 401);
//         }

//         return $next($request);
//     }
// }

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/login', [HomeController::class, 'login'])->name('home.login');
Route::post('/login', [HomeController::class, 'store']);
Route::get('/register', [HomeController::class, 'register'])->name('home.register');
Route::post('/register', [HomeController::class, 'create']);
