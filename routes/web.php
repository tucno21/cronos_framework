<?php

// use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Routing\Route;
use Cronos\Http\Middleware;
use App\Controllers\HomeController;


class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->hasUser()) {
            return redirect()->route('home.login');
        }

        return $next($request);
    }
}
class LoginMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->hasUser()) {
            return redirect()->route('home.dashboard');
        }

        return $next($request);
    }
}

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/login', [HomeController::class, 'login'])->name('home.login')->middleware(LoginMiddleware::class);
Route::post('/login', [HomeController::class, 'store']);
Route::get('/register', [HomeController::class, 'register'])->name('home.register')->middleware(LoginMiddleware::class);
Route::post('/register', [HomeController::class, 'create']);
Route::get('/logout', [HomeController::class, 'logout'])->name('home.logout');
Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('home.dashboard')->middleware(AuthMiddleware::class);
