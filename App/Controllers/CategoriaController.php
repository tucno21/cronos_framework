<?php

namespace App\Controllers;

use App\Models\Categoria;
use Cronos\Http\Controller;
use Cronos\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $soloRaices = $request->solo_raices ?? null;

        $query = Categoria::with('categoriaPadre', 'subcategorias')
            ->withCount('publicaciones');

        if ($soloRaices === 'true' || $soloRaices === '1') {
            $query->whereNull('categoria_padre_id');
        }

        $categorias = $query->orderBy('id', 'ASC')->get();

        return json([
            'status' => 'success',
            'categorias' => $categorias ? $categorias->toArray() : [],
            'total' => $categorias ? count($categorias) : 0,
        ]);
    }

    public function arbol()
    {
        // Árbol jerárquico cargando raíces y sus subcategorías
        $raices = Categoria::whereNull('categoria_padre_id')
            ->with('subcategorias')
            ->withCount('publicaciones')
            ->orderBy('id', 'ASC')
            ->get();

        return json([
            'status' => 'success',
            'arbol' => $raices ? $raices->toArray() : [],
        ]);
    }
}
