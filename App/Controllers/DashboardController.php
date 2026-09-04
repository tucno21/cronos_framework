<?php

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Etiqueta;
use App\Models\Publicacion;
use App\Models\Rol;
use App\Models\Usuario;
use Cronos\Http\Controller;
use Cronos\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        // 1. Contadores base
        $totalUsuarios = Usuario::count();
        $totalPublicaciones = Publicacion::count();
        $totalCategorias = Categoria::count();
        $totalEtiquetas = Etiqueta::count();
        $totalComentarios = Comentario::count();
        $totalRoles = Rol::count();

        // 2. Agregados ORM sobre vistas (sección 3 doc 04-modelos.md)
        $totalVistas = (int) (Publicacion::sum('vistas') ?? 0);
        $promedioVistas = round((float) (Publicacion::avg('vistas') ?? 0), 1);
        $maxVistas = (int) (Publicacion::max('vistas') ?? 0);
        $minVistas = (int) (Publicacion::min('vistas') ?? 0);

        // 3. Distribución por estados (where + count)
        $publicadosCount = Publicacion::where('estado', 'publicado')->count();
        $borradoresCount = Publicacion::where('estado', 'borrador')->count();
        $archivadosCount = Publicacion::where('estado', 'archivado')->count();

        // 4. Top 5 publicaciones más vistas (Eager Loading con usuario y categoria)
        $topPublicaciones = Publicacion::with('usuario', 'categoria')
            ->orderBy('vistas', 'DESC')
            ->limit(5)
            ->get();

        // 5. Últimas 5 publicaciones
        $ultimasPublicaciones = Publicacion::with('usuario', 'categoria', 'etiquetas')
            ->withCount('comentarios')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        // 6. Últimos comentarios con autor y post
        $ultimosComentarios = Comentario::with('usuario', 'publicacion')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        // 7. Categorías con conteo de publicaciones (withCount)
        $categoriasConteo = Categoria::withCount('publicaciones')
            ->orderBy('id', 'ASC')
            ->get();

        // 8. Roles con conteo de usuarios (withCount en belongsToMany)
        $rolesConteo = Rol::withCount('usuarios')
            ->orderBy('id', 'ASC')
            ->get();

        return json([
            'status' => 'success',
            'data' => [
                'metricas' => [
                    'usuarios' => $totalUsuarios,
                    'publicaciones' => $totalPublicaciones,
                    'categorias' => $totalCategorias,
                    'etiquetas' => $totalEtiquetas,
                    'comentarios' => $totalComentarios,
                    'roles' => $totalRoles,
                    'vistas' => [
                        'total' => $totalVistas,
                        'promedio' => $promedioVistas,
                        'max' => $maxVistas,
                        'min' => $minVistas,
                    ],
                    'estados' => [
                        'publicado' => $publicadosCount,
                        'borrador' => $borradoresCount,
                        'archivado' => $archivadosCount,
                    ],
                ],
                'top_publicaciones' => $topPublicaciones ? $topPublicaciones->toArray() : [],
                'ultimas_publicaciones' => $ultimasPublicaciones ? $ultimasPublicaciones->toArray() : [],
                'ultimos_comentarios' => $ultimosComentarios ? $ultimosComentarios->toArray() : [],
                'categorias' => $categoriasConteo ? $categoriasConteo->toArray() : [],
                'roles' => $rolesConteo ? $rolesConteo->toArray() : [],
            ],
        ]);
    }
}
