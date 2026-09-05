<?php

namespace App\Controllers;

use App\Library\JWT\JWTAuth;
use App\Models\Comentario;
use App\Models\Publicacion;
use App\Models\Usuario;
use App\Requests\StorePublicacionRequest;
use App\Requests\UpdatePublicacionRequest;
use App\Resources\PublicacionResource;
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
        $publicaciones = Publicacion::with('usuario', 'categoria', 'etiquetas')
            ->withCount('comentarios')
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = $publicaciones ? PublicacionResource::collection($publicaciones)->toArray() : [];

        return json([
            'status' => 'success',
            'publicaciones' => $data,
        ]);
    }

    public function show(Publicacion $publicacion)
    {
        $comentarios = Comentario::with('usuario')
            ->where('publicacion_id', $publicacion->id)
            ->orderBy('created_at', 'DESC')
            ->get();

        $data = (new PublicacionResource($publicacion))->resolve();
        $data['comentarios'] = $comentarios ? $comentarios->toArray() : [];

        return json([
            'status' => 'success',
            'publicacion' => $data,
        ]);
    }

    public function store(StorePublicacionRequest $request)
    {
        $data = $request->all();
        $data->usuario_id = $request->user()->id;

        $publicacion = Publicacion::create($data);

        return json([
            'status' => 'success',
            'message' => 'Publicacion creada correctamente',
            'publicacion' => $publicacion ? $publicacion->toArray() : null,
        ], 201);
    }

    public function update(UpdatePublicacionRequest $request, Publicacion $publicacion)
    {
        $publicacion->update($request->all());

        return json([
            'status' => 'success',
            'message' => 'Publicacion actualizada correctamente',
            'publicacion' => $publicacion->refresh()->toArray(),
        ]);
    }

    public function destroy(Publicacion $publicacion)
    {
        $eliminada = $publicacion->delete();

        return json([
            'status' => $eliminada ? 'success' : 'error',
            'message' => $eliminada ? 'Publicacion eliminada correctamente' : 'No se pudo eliminar la publicacion',
        ], $eliminada ? 200 : 400);
    }
}
