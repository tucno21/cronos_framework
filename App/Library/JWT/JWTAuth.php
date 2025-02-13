<?php

namespace App\Library\JWT;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class JWTAuth
{
    // Genera un par de claves RSA y usa la privada para firmar
    private string $jwtAlgorithm = 'HS256';
    //agregar la propiedad $secret_key env('JWT_SECRET_KEY', 'secret')
    private string $secret_key;

    private string $dominio;

    public function __construct()
    {
        $this->secret_key = env('JWT_SECRET_KEY', 'secret');
        $this->dominio = env('APP_URL', 'http://localhost');
    }

    public function generateToken($data)
    {

        $issuedAt = time(); // Tiempo que inició el token
        // token para una semana
        $expire = $issuedAt + 604800; // tiempo que expirará el token

        $payload = [
            // 'iss' => $this->dominio, // Dominio de la aplicación
            'iat' => $issuedAt,
            'sub' => $data['id'], // ID del usuario
            'exp' => $expire,
            'data' => $data
        ];

        return JWT::encode($payload, $this->secret_key, $this->jwtAlgorithm);
    }

    public function validateToken($token)
    {
        try {
            JWT::decode($token, new Key($this->secret_key, $this->jwtAlgorithm));
            return true; // Si no hay excepción, el token es válido
        } catch (\Exception $e) {
            return false;
        }
    }

    public function decodeToken($token)
    {
        try {
            return JWT::decode($token, new Key($this->secret_key, $this->jwtAlgorithm));
        } catch (\Exception $e) {
            return null; // o lanza una excepción personalizada
        }
    }
}
