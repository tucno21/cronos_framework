<?php

namespace Cronos\Http;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;

interface Middleware
{
    public function handle(Request $request, Closure $next): Response;
}
