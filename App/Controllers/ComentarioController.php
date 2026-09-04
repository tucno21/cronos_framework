<?php

namespace App\Controllers;

use App\Models\Comentario;
use Cronos\Http\Controller;

class ComentarioController extends Controller
{
    public function index()
    {
        $comentarios = Comentario::with('usuario', 'publicacion')
            ->orderBy('created_at', 'DESC')
            ->get();

        return json([
            'status' => 'success',
            'comentarios' => $comentarios ? $comentarios->toArray() : [],
            'total' => $comentarios ? count($comentarios) : 0,
        ]);
    }

    public function destroy(string $id)
    {
        $comentario = Comentario::find((int) $id);

        if (!$comentario) {
            return json([
                'status' => 'error',
                'message' => 'Comentario no encontrado',
            ], 404);
        }

        $borrado = $comentario->delete();

        return json([
            'status' => $borrado ? 'success' : 'error',
            'message' => $borrado ? 'Comentario eliminado correctamente' : 'No se pudo eliminar el comentario',
        ], $borrado ? 200 : 400);
    }
}
