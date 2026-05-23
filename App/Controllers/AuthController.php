<?php

namespace App\Controllers;

use App\Models\User;
use App\Library\JWT\JWTAuth;
use Cronos\Http\Request;
use Cronos\Http\Controller;
use Cronos\Crypto\Hasher;

class AuthController extends Controller
{
    public function register(Request $request, Hasher $hasher)
    {
        $valid = $this->validate($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|unique:User,email',
            'password' => 'required|min:6|max:50|matches:confirm_password',
            'confirm_password' => 'required|matches:password',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid
            ], 400);
        }

        $data = $request->all();
        $data->password = $hasher->hash($data->password);
        unset($data->confirm_password);

        $data->role = 'user';
        $data->avatar = null;
        $data->email_verified_at = null;
        $data->remember_token = null;

        $user = User::create($data);

        $jwt = new JWTAuth();
        $token = $jwt->generateToken([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return json([
            'status' => 'success',
            'message' => 'Usuario registrado correctamente',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'email' => 'required|email|not_unique:User,email',
            'password' => 'required|password_verify:User,email',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        $jwt = new JWTAuth();
        $token = $jwt->generateToken([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return json([
            'status' => 'success',
            'message' => 'Login exitoso',
            'user' => $user,
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

        $user = User::find($decoded->sub);

        return json([
            'status' => 'success',
            'user' => $user
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
