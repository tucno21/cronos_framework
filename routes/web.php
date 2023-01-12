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
        if ($request->headers('Connection') !== 'keep-alive') {
            return json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
class LoginMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->name === "" || $request->email === "") {
            return json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

Route::get('/', [HomeController::class, 'index'])->middleware([AuthMiddleware::class]);
Route::get('/form', [HomeController::class, 'form']);
Route::post('/form', [HomeController::class, 'store'])->middleware([LoginMiddleware::class]);
