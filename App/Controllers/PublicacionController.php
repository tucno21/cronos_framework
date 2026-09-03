<?php

namespace App\Controllers;

use App\Library\JWT\JWTAuth;
use App\Models\Publicacion;
use App\Models\Usuario;
use Cronos\Http\Controller;
use Cronos\Http\Request;

class PublicacionController extends Controller
{
    protected function authUser(Request $request): ?Usuario
    {
        $token = $request->headers('X-Token');

        if (empty($token)) {
            return null;
        }

        $jwt = new JWTAuth();
        $decoded = $jwt->decodeToken($token);

        if (!$decoded) {
            return null;
        }

        return Usuario::find($decoded->sub);
    }

    public function index()
    {
        $publicaciones = Publicacion::select('publicaciones.*', 'usuarios.nombre')
            ->join('usuarios', 'usuarios.id', '=', 'publicaciones.usuario_id')
            ->orderBy('publicaciones.created_at', 'DESC')
            ->get();

        return json([
            'status' => 'success',
            'publicaciones' => $publicaciones ? $publicaciones->toArray() : [],
        ]);
    }

    public function show(Publicacion $publicacion)
    {
        $publicacion->nombre_autor = Usuario::find($publicacion->usuario_id)->nombre ?? null;

        return json([
            'status' => 'success',
            'publicacion' => $publicacion->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $valid = $this->validate($request->all(), [
            'titulo' => 'required|string|min:3|max:100',
            'slug' => 'required|slug|unique:Publicacion,slug',
            'contenido' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid,
            ], 422);
        }

        $usuario = $this->authUser($request);
        if (!$usuario) {
            return json([
                'status' => 'error',
                'message' => 'No autenticado',
            ], 401);
        }

        $data = $request->all();
        $data->usuario_id = $usuario->id;

        $publicacion = Publicacion::create($data);

        return json([
            'status' => 'success',
            'message' => 'Publicacion creada correctamente',
            'publicacion' => $publicacion ? $publicacion->toArray() : null,
        ], 201);
    }

    public function update(Request $request, Publicacion $publicacion)
    {
        $slugRule = $request->slug === $publicacion->slug
            ? 'required|slug'
            : 'required|slug|unique:Publicacion,slug';

        $valid = $this->validate($request->all(), [
            'titulo' => 'required|string|min:3|max:100',
            'slug' => $slugRule,
            'contenido' => 'required|string|min:3|max:1000',
        ]);

        if ($valid !== true) {
            return json([
                'status' => 'error',
                'message' => $valid,
            ], 422);
        }

        $actualizada = Publicacion::update($publicacion->id, $request->all());

        return json([
            'status' => 'success',
            'message' => 'Publicacion actualizada correctamente',
            'publicacion' => $actualizada ? $actualizada->toArray() : null,
        ]);
    }

    public function destroy(Publicacion $publicacion)
    {
        $eliminada = Publicacion::delete($publicacion->id);

        return json([
            'status' => $eliminada ? 'success' : 'error',
            'message' => $eliminada ? 'Publicacion eliminada correctamente' : 'No se pudo eliminar la publicacion',
        ], $eliminada ? 200 : 400);
    }
}
