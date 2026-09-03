<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Library\JWT\JWTAuth;
use Cronos\Http\Request;
use Cronos\Http\Controller;
use Cronos\Crypto\Hasher;

class AuthController extends Controller
{
    public function register(Request $request, Hasher $hasher)
    {
        $valid = $this->validate($request->all(), [
            'nombre' => 'required|string|min:3|max:100',
            'correo' => 'required|email|unique:Usuario,correo',
            'contrasena' => 'required|min:6|max:50|matches:confirmar_contrasena',
            'confirmar_contrasena' => 'required|matches:contrasena',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid
            ], 400);
        }

        $data = $request->all();
        $data->contrasena = $hasher->hash($data->contrasena);
        unset($data->confirmar_contrasena);

        $data->rol = 'usuario';
        $data->avatar = null;
        $data->correo_verificado_en = null;
        $data->token_recordar = null;

        $usuario = Usuario::create($data);

        $jwt = new JWTAuth();
        $token = $jwt->generateToken([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'correo' => $usuario->correo,
            'rol' => $usuario->rol,
        ]);

        return json([
            'status' => 'success',
            'message' => 'Usuario registrado correctamente',
            'usuario' => $usuario,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'correo' => 'required|email|not_unique:Usuario,correo',
            'contrasena' => 'required|password_verify:Usuario,correo',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid
            ], 401);
        }

        $usuario = Usuario::where('correo', $request->correo)->first();

        $jwt = new JWTAuth();
        $token = $jwt->generateToken([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'correo' => $usuario->correo,
            'rol' => $usuario->rol,
        ]);

        return json([
            'status' => 'success',
            'message' => 'Login exitoso',
            'usuario' => $usuario,
            'token' => $token
        ]);
    }

    public function me(Request $request)
    {
        $token = $request->headers('X-Token');

        $jwt = new JWTAuth();
        $decoded = $jwt->decodeToken($token);

        if (!$decoded) {
            return json([
                'status' => 'error',
                'message' => 'Token invalido'
            ], 401);
        }

        $usuario = Usuario::find($decoded->sub);

        return json([
            'status' => 'success',
            'usuario' => $usuario
        ]);
    }

    public function logout()
    {
        return json([
            'status' => 'success',
            'message' => 'Sesion cerrada correctamente'
        ]);
    }
}
