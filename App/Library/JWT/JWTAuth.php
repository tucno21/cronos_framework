<?php

namespace App\JWT\Library;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class JWTAuth
{
    private string $algo = 'HS256';
    //agregar la propiedad $secret_key env('JWT_SECRET_KEY', 'secret')
    private string $secret_key;
    public function __construct()
    {
        $this->secret_key = env('JWT_SECRET_KEY', 'secret');
        // $this->secret_key = $_ENV['JWT_SECRET_KEY'];
    }

    public function generateToken($data)
    {

        $issuedAt = time(); // Tiempo que inició el token
        $expire = $issuedAt + 3600; // El token expirará en 1 hora

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $data
        ];

        return JWT::encode($payload, $this->secret_key, $this->algo);
    }

    public function validateToken($token)
    {
        try {
            // JWT::decode($jwt, new Key($key, 'HS256'));
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algo));
            if ($decoded->exp < time() || !$decoded) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function decodeToken($token)
    {
        $decoded = JWT::decode($token, new Key($this->secret_key, $this->algo));
        return $decoded;
    }
}
