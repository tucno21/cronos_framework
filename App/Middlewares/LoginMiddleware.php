<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;

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
