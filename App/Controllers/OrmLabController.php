<?php

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Comentario;
use App\Models\Etiqueta;
use App\Models\Perfil;
use App\Models\Publicacion;
use App\Models\Rol;
use App\Models\Usuario;
use Cronos\Http\Controller;
use Cronos\Model\Model;

class OrmLabController extends Controller
{
    public function index()
    {
        $queries = [];

        // 1. Agregados
        $t0 = microtime(true);
        $agregados = [
            'total_publicaciones' => Publicacion::count(),
            'suma_vistas' => (int) Publicacion::sum('vistas'),
            'promedio_vistas' => round((float) Publicacion::avg('vistas'), 2),
            'max_vistas' => (int) Publicacion::max('vistas'),
            'min_vistas' => (int) Publicacion::min('vistas'),
        ];
        $queries[] = [
            'id' => 'agregados',
            'seccion' => '3. Query Builder (Terminales y Agregados)',
            'titulo' => 'Agregados Estadísticos (count, sum, avg, min, max)',
            'descripcion' => 'Cálculo directo en MySQL usando los métodos agregados de columna del ORM Cronos.',
            'php' => "Publicacion::count();\nPublicacion::sum('vistas');\nPublicacion::avg('vistas');\nPublicacion::max('vistas');\nPublicacion::min('vistas');",
            'sql' => "SELECT COUNT(*) FROM publicaciones;\nSELECT SUM(vistas) FROM publicaciones;\nSELECT AVG(vistas) FROM publicaciones;\nSELECT MAX(vistas) FROM publicaciones;\nSELECT MIN(vistas) FROM publicaciones;",
            'resultado' => $agregados,
            'filas' => 1,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 2. whereBetween
        $t0 = microtime(true);
        $resBetween = Publicacion::whereBetween('vistas', 500, 3500)
            ->orderBy('vistas', 'DESC')
            ->get();
        $queries[] = [
            'id' => 'whereBetween',
            'seccion' => '3. Query Builder (Filtros WHERE)',
            'titulo' => 'Rango Numérico con whereBetween()',
            'descripcion' => 'Filtra publicaciones que tienen entre 500 y 3500 vistas ordenadas descendentemente.',
            'php' => "Publicacion::whereBetween('vistas', 500, 3500)\n    ->orderBy('vistas', 'DESC')\n    ->get();",
            'sql' => "SELECT * FROM publicaciones WHERE vistas BETWEEN 500 AND 3500 ORDER BY vistas DESC;",
            'resultado' => $resBetween ? $resBetween->toArray() : [],
            'filas' => $resBetween ? count($resBetween) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 3. whereIn
        $t0 = microtime(true);
        $resIn = Usuario::whereIn('rol', ['admin', 'editor'])
            ->orderBy('id', 'ASC')
            ->get();
        $queries[] = [
            'id' => 'whereIn',
            'seccion' => '3. Query Builder (Filtros WHERE)',
            'titulo' => 'Filtro de Lista con whereIn()',
            'descripcion' => 'Obtiene usuarios cuyo rol sea exclusivamente admin o editor.',
            'php' => "Usuario::whereIn('rol', ['admin', 'editor'])\n    ->orderBy('id', 'ASC')\n    ->get();",
            'sql' => "SELECT * FROM usuarios WHERE rol IN ('admin', 'editor') ORDER BY id ASC;",
            'resultado' => $resIn ? $resIn->toArray() : [],
            'filas' => $resIn ? count($resIn) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 4. whereNull vs whereNotNull
        $t0 = microtime(true);
        $resNull = Usuario::whereNotNull('invitado_por')
            ->with('invitadoPor')
            ->get();
        $queries[] = [
            'id' => 'whereNotNull',
            'seccion' => '3. Query Builder (Filtros WHERE)',
            'titulo' => 'Comprobación de Nulos con whereNotNull()',
            'descripcion' => 'Obtiene solo usuarios que fueron invitados por otro usuario, cargando su invitador.',
            'php' => "Usuario::whereNotNull('invitado_por')\n    ->with('invitadoPor')\n    ->get();",
            'sql' => "SELECT * FROM usuarios WHERE invitado_por IS NOT NULL;\nSELECT * FROM usuarios WHERE id IN (...);",
            'resultado' => $resNull ? $resNull->toArray() : [],
            'filas' => $resNull ? count($resNull) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 5. has() con umbral
        $t0 = microtime(true);
        $resHas = Publicacion::has('comentarios', '>=', 3)
            ->withCount('comentarios')
            ->with('usuario')
            ->get();
        $queries[] = [
            'id' => 'has_threshold',
            'seccion' => '6. Filtrar por Relaciones (has)',
            'titulo' => 'Filtro Relacional has() con Umbral (>= 3)',
            'descripcion' => 'Busca publicaciones que tienen al menos 3 comentarios usando subquery EXISTS con conteo.',
            'php' => "Publicacion::has('comentarios', '>=', 3)\n    ->withCount('comentarios')\n    ->with('usuario')\n    ->get();",
            'sql' => "SELECT * FROM publicaciones p WHERE (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) >= 3;",
            'resultado' => $resHas ? $resHas->toArray() : [],
            'filas' => $resHas ? count($resHas) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 6. whereHas() con constraint
        $t0 = microtime(true);
        $resWhereHas = Publicacion::whereHas(
            'comentarios',
            fn($q) => $q->where('contenido', 'LIKE', '%tutorial%')
        )->with('usuario')->get();
        $queries[] = [
            'id' => 'whereHas',
            'seccion' => '6. Filtrar por Relaciones (whereHas)',
            'titulo' => 'Filtro Condicional en Relación con whereHas()',
            'descripcion' => 'Encuentra publicaciones que tienen comentarios cuyo contenido contiene la palabra "tutorial".',
            'php' => "Publicacion::whereHas(\n    'comentarios',\n    fn(\$q) => \$q->where('contenido', 'LIKE', '%tutorial%')\n)->with('usuario')->get();",
            'sql' => "SELECT * FROM publicaciones p WHERE EXISTS (SELECT 1 FROM comentarios c WHERE c.publicacion_id = p.id AND c.contenido LIKE '%tutorial%');",
            'resultado' => $resWhereHas ? $resWhereHas->toArray() : [],
            'filas' => $resWhereHas ? count($resWhereHas) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 7. has('comentarios', '=', 0)
        $t0 = microtime(true);
        $resDoesntHave = Publicacion::has('comentarios', '=', 0)
            ->with('usuario')
            ->get();
        $queries[] = [
            'id' => 'sin_comentarios',
            'seccion' => '6. Filtrar por Relaciones (has con cantidad = 0)',
            'titulo' => 'Registros sin Relaciones con has(..., "=", 0)',
            'descripcion' => 'Publicaciones que aún no han recibido ningún comentario.',
            'php' => "Publicacion::has('comentarios', '=', 0)\n    ->with('usuario')\n    ->get();",
            'sql' => "SELECT * FROM publicaciones p WHERE (SELECT COUNT(*) FROM comentarios c WHERE c.publicacion_id = p.id) = 0;",
            'resultado' => $resDoesntHave ? $resDoesntHave->toArray() : [],
            'filas' => $resDoesntHave ? count($resDoesntHave) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 8. Relación N:M belongsToMany
        $t0 = microtime(true);
        $resRoles = Usuario::has('roles')
            ->with('roles')
            ->get();
        $queries[] = [
            'id' => 'belongsToMany',
            'seccion' => '4. Relaciones N:M (belongsToMany)',
            'titulo' => 'Relaciones N:M con Tabla Pivote (rol_usuario)',
            'descripcion' => 'Usuarios que tienen roles asignados a través de la tabla intermedia rol_usuario.',
            'php' => "Usuario::has('roles')\n    ->with('roles')\n    ->get();",
            'sql' => "SELECT * FROM usuarios WHERE EXISTS (SELECT 1 FROM rol_usuario WHERE rol_usuario.usuario_id = usuarios.id);\nSELECT roles.*, rol_usuario.usuario_id FROM roles JOIN rol_usuario ...;",
            'resultado' => $resRoles ? $resRoles->toArray() : [],
            'filas' => $resRoles ? count($resRoles) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 9. Eager Loading múltiple y anidado
        $t0 = microtime(true);
        $resEager = Publicacion::with('usuario.perfil', 'categoria', 'etiquetas')
            ->withCount('comentarios')
            ->where('estado', 'publicado')
            ->orderBy('vistas', 'DESC')
            ->limit(3)
            ->get();
        $queries[] = [
            'id' => 'eager_loading',
            'seccion' => '5. Eager Loading (with anidado)',
            'titulo' => 'Eager Loading Múltiple y Anidado (dot notation)',
            'descripcion' => 'Carga publicaciones con el autor y su perfil (usuario.perfil), su categoría, sus etiquetas y el conteo de comentarios sin N+1.',
            'php' => "Publicacion::with('usuario.perfil', 'categoria', 'etiquetas')\n    ->withCount('comentarios')\n    ->where('estado', 'publicado')\n    ->orderBy('vistas', 'DESC')\n    ->limit(3)\n    ->get();",
            'sql' => "SELECT * FROM publicaciones WHERE estado = 'publicado' ORDER BY vistas DESC LIMIT 3;\nSELECT * FROM usuarios WHERE id IN (...);\nSELECT * FROM perfiles WHERE usuario_id IN (...);\nSELECT * FROM categorias WHERE id IN (...);\nSELECT etiquetas.*, publicacion_etiqueta.publicacion_id ...",
            'resultado' => $resEager ? $resEager->toArray() : [],
            'filas' => $resEager ? count($resEager) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 10. Jerarquía auto-referencial (Categorías Árbol)
        $t0 = microtime(true);
        $resArbol = Categoria::whereNull('categoria_padre_id')
            ->with('subcategorias')
            ->withCount('publicaciones')
            ->get();
        $queries[] = [
            'id' => 'arbol_categorias',
            'seccion' => '4. Relaciones Auto-referenciales',
            'titulo' => 'Árbol Jerárquico de Categorías (categoriaPadre y subcategorias)',
            'descripcion' => 'Categorías principales (raíces) con sus subcategorías anidadas y el conteo de artículos en cada una.',
            'php' => "Categoria::whereNull('categoria_padre_id')\n    ->with('subcategorias')\n    ->withCount('publicaciones')\n    ->get();",
            'sql' => "SELECT * FROM categorias WHERE categoria_padre_id IS NULL;\nSELECT * FROM categorias WHERE categoria_padre_id IN (...);",
            'resultado' => $resArbol ? $resArbol->toArray() : [],
            'filas' => $resArbol ? count($resArbol) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 11. Red de invitaciones (Auto-referencial usuarios)
        $t0 = microtime(true);
        $resInvitados = Usuario::whereNotNull('invitado_por')
            ->with('invitadoPor')
            ->orderBy('id', 'ASC')
            ->get();
        $queries[] = [
            'id' => 'red_invitados',
            'seccion' => '4. Relaciones Auto-referenciales',
            'titulo' => 'Red de Invitación de Usuarios (invitadoPor)',
            'descripcion' => 'Mapeo de usuarios invitados por otros miembros de la comunidad, cargando la relación con su anfitrión.',
            'php' => "Usuario::whereNotNull('invitado_por')\n    ->with('invitadoPor')\n    ->orderBy('id', 'ASC')\n    ->get();",
            'sql' => "SELECT * FROM usuarios WHERE invitado_por IS NOT NULL ORDER BY id ASC;\nSELECT * FROM usuarios WHERE id IN (...);",
            'resultado' => $resInvitados ? $resInvitados->toArray() : [],
            'filas' => $resInvitados ? count($resInvitados) : 0,
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        // 12. Soft Deletes en BD
        $t0 = microtime(true);
        $filasEliminadas = Model::db()->statement(
            "SELECT id, titulo, slug, estado, vistas, eliminado_en FROM publicaciones WHERE eliminado_en IS NOT NULL"
        );
        $queries[] = [
            'id' => 'soft_deletes',
            'seccion' => '13. Soft Deletes',
            'titulo' => 'Registros con Soft Delete en BD (eliminado_en)',
            'descripcion' => 'Inspección de registros marcados con borrado lógico según el esquema de cronos.sql.',
            'php' => "// En modelos con SoftDeletes:\n// Publicacion::onlyTrashed()->get();\n// O verificando columna eliminado_en:",
            'sql' => "SELECT id, titulo, slug, estado, vistas, eliminado_en FROM publicaciones WHERE eliminado_en IS NOT NULL;",
            'resultado' => $filasEliminadas,
            'filas' => count($filasEliminadas),
            'duracion_ms' => round((microtime(true) - $t0) * 1000, 2),
        ];

        return json([
            'status' => 'success',
            'total_consultas' => count($queries),
            'consultas' => $queries,
        ]);
    }
}
