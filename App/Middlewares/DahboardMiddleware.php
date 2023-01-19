<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;

class DahboardMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->hasUser()) {
            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
