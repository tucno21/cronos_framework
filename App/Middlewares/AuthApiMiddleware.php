<?php

namespace App\Middlewares;

use App\Library\JWT\JWTAuth;
use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;

class AuthApiMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->headers('X-Token');
        if (empty($token)) {
            $data = [
                'status' => 'error',
                'message' => 'No se ha enviado el token',
            ];
            return json($data, 400);
        }

        $jwt = new JWTAuth();
        if (!$jwt->validateToken($token)) {
            $data = [
                'status' => 'error',
                'message' => 'Token inválido',
            ];
            return json($data, 400);
        }

        return $next($request);
    }
}
