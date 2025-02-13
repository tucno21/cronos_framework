<?php

namespace App\Middlewares;

use Closure;
use App\Models\User;
use Cronos\Http\Request;
use Cronos\Http\Response;
use Cronos\Http\Middleware;
use App\Library\JWT\JWTAuth;

class TokenValidationMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->headers('x-token');
        if (empty($token)) {
            $data = [
                'status' => 'error',
                'message' => 'Acceso no autorizado',
            ];
            return json($data, 401);
        }

        $jwt = new JWTAuth();

        if (!$jwt->validateToken($token)) {
            $data = [
                'status' => 'error',
                'message' => 'Token inválido o expirado',
            ];
            return json($data, 401);
        }

        $decoded = $jwt->decodeToken($token);
        $user = User::find($decoded->sub);

        if (!$user) {
            $data = [
                'status' => 'error',
                'message' => 'datos incorrectos',
            ];
            return json($data, 401);
        }

        // Verificar si el token está próximo a expirar (opcional)
        $expMargin = $decoded->exp - time();
        if ($expMargin < 300) { // 5 minutos antes de expirar
            $request->expire = true;
        }

        $request->expire = false;
        $request->email = $user->email;

        return $next($request);
    }
}
