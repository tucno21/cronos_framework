<?php

namespace App\Controllers;

use App\Models\Usuario;
use Cronos\Http\Controller;
use Cronos\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Usuario::with('perfil', 'roles', 'invitadoPor')
            ->withCount('publicaciones', 'comentarios');

        $rol = $request->rol ?? null;
        if ($rol && in_array($rol, ['admin', 'editor', 'usuario'])) {
            $query->where('rol', $rol);
        }

        $conPerfil = $request->con_perfil ?? null;
        if ($conPerfil === 'true' || $conPerfil === '1') {
            $query->has('perfil');
        } elseif ($conPerfil === 'false' || $conPerfil === '0') {
            $query->has('perfil', '=', 0);
        }

        $conInvitador = $request->con_invitador ?? null;
        if ($conInvitador === 'true' || $conInvitador === '1') {
            $query->whereNotNull('invitado_por');
        } elseif ($conInvitador === 'false' || $conInvitador === '0') {
            $query->whereNull('invitado_por');
        }

        $usuarios = $query->orderBy('id', 'ASC')->get();

        return json([
            'status' => 'success',
            'usuarios' => $usuarios ? $usuarios->toArray() : [],
            'total' => $usuarios ? count($usuarios) : 0,
        ]);
    }

    public function show($id)
    {
        $usuario = Usuario::with('perfil', 'roles', 'invitados', 'invitadoPor', 'publicaciones', 'tokens')
            ->withCount('publicaciones', 'comentarios')
            ->where('id', (int) $id)
            ->first();

        if (!$usuario) {
            return json([
                'status' => 'error',
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        return json([
            'status' => 'success',
            'usuario' => $usuario->toArray(),
        ]);
    }
}
