<?php

namespace App\Controllers;

use App\Models\Etiqueta;
use Cronos\Http\Controller;

class EtiquetaController extends Controller
{
    public function index()
    {
        $etiquetas = Etiqueta::withCount('publicaciones')
            ->with('publicaciones')
            ->orderBy('id', 'ASC')
            ->get();

        return json([
            'status' => 'success',
            'etiquetas' => $etiquetas ? $etiquetas->toArray() : [],
            'total' => $etiquetas ? count($etiquetas) : 0,
        ]);
    }
}
