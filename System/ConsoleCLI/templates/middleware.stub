<?php

namespace App\Middlewares;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;

class MameMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // if (!session()->hasUser()) {
        //     return redirect()->route('home.login');
        // }

        return $next($request);
    }
}
