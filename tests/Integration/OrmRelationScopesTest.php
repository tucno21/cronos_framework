<?php

namespace Tests\Integration;

use App\Models\Comentario;
use App\Models\Publicacion;
use App\Models\Rol;
use App\Models\Usuario;
use Cronos\Model\Model;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de la Fase A.3 del ORM: has(), whereHas() y withCount().
 *
 * Estructura de datos de cada test:
 * - carlos (rol usuario) con 2 publicaciones: publicada (2 comentarios de
 *   carlos) y borrador (0 comentarios)
 * - el comentario con contenido unico esta en la publicada
 */
class OrmRelationScopesTest extends OrmTestCase
{
    private Usuario $carlos;

    private Publicacion $publicada;

    private Publicacion $borrador;

    private function crearEscenario(): void
    {
        $this->carlos = $this->crearUsuario(['nombre' => 'Carlos Scopes']);

        $slug = uniqid('scopes-');

        $this->publicada = Publicacion::create([
            'usuario_id' => $this->carlos->id,
            'titulo' => 'Con Comentarios',
            'slug' => $slug . '-con',
            'contenido' => 'contenido con comentarios',
        ]);
        Model::db()->statementC_U_D(
            'UPDATE publicaciones SET estado = ? WHERE id = ?',
            ['publicado', $this->publicada->id]
        );

        $this->borrador = Publicacion::create([
            'usuario_id' => $this->carlos->id,
            'titulo' => 'Sin Comentarios',
            'slug' => $slug . '-sin',
            'contenido' => 'contenido sin comentarios',
        ]);

        Comentario::create([
            'publicacion_id' => $this->publicada->id,
            'usuario_id' => $this->carlos->id,
            'contenido' => 'primer comentario',
        ]);

        Comentario::create([
            'publicacion_id' => $this->publicada->id,
            'usuario_id' => $this->carlos->id,
            'contenido' => 'comentario unico para whereHas',
        ]);
    }

    //******************************************************************
    // HAS
    //******************************************************************

    public function testHasBasico(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $conComentarios = Publicacion::whereIn('slug', [$this->publicada->slug, $this->borrador->slug])
                ->has('comentarios')
                ->get();

            $this->assertNotNull($conComentarios);
            $this->assertSame(1, $conComentarios->count());
            $this->assertSame($this->publicada->slug, $conComentarios->first()->slug);
        });
    }

    public function testHasConCantidad(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $mias = [$this->publicada->slug, $this->borrador->slug];

            //2 comentarios: la publicada cumple con >= 2
            $resultado = Publicacion::whereIn('slug', $mias)->has('comentarios', '>=', 2)->get();
            $this->assertNotNull($resultado);
            $this->assertSame(1, $resultado->count());

            //3 comentarios: ninguna cumple
            $resultado3 = Publicacion::whereIn('slug', $mias)->has('comentarios', '>=', 3)->get();
            $this->assertNull($resultado3);
        });
    }

    public function testHasSobreBelongsTo(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //todos los comentarios de MI publicacion tienen usuario existente
            $comentarios = Comentario::where('publicacion_id', $this->publicada->id)->has('usuario')->get();
            $this->assertNotNull($comentarios);
            $this->assertSame(2, $comentarios->count());
        });
    }

    public function testHasSobreBelongsToMany(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //asignar un rol a carlos via pivote
            Model::db()->statementC_U_D(
                'INSERT INTO rol_usuario (usuario_id, rol_id) VALUES (?, ?)',
                [$this->carlos->id, Rol::first()->id]
            );

            $conRoles = Usuario::has('roles')->get();
            $this->assertNotNull($conRoles);

            $encontrado = $conRoles->first(fn ($u) => $u->correo === $this->carlos->correo);
            $this->assertNotNull($encontrado);
        });
    }

    public function testHasCombinadoConWhere(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //el orden where + whereHas debe respetar el orden de los valores
            $resultado = Publicacion::whereIn('slug', [$this->publicada->slug, $this->borrador->slug])
                ->where('estado', 'publicado')
                ->has('comentarios')
                ->get();

            $this->assertNotNull($resultado);
            $this->assertSame(1, $resultado->count());
            $this->assertSame($this->publicada->slug, $resultado->first()->slug);
        });
    }

    //******************************************************************
    // WHERE HAS
    //******************************************************************

    public function testWhereHasConClosure(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $resultado = Publicacion::whereHas(
                'comentarios',
                fn ($q) => $q->where('contenido', 'comentario unico para whereHas')
            )->get();

            $this->assertNotNull($resultado);
            $this->assertSame(1, $resultado->count());
            $this->assertSame($this->publicada->slug, $resultado->first()->slug);
        });
    }

    public function testWhereHasSobreElAutorDeLaPublicacion(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $mias = [$this->publicada->slug, $this->borrador->slug];

            //ninguna de MIS publicaciones tiene un autor admin
            $sinAdmin = Publicacion::whereIn('slug', $mias)
                ->whereHas('usuario', fn ($q) => $q->where('rol', 'admin'))
                ->get();
            $this->assertNull($sinAdmin);

            //todas MIS publicaciones tienen un autor con rol usuario
            $conRolUsuario = Publicacion::whereIn('slug', $mias)
                ->whereHas('usuario', fn ($q) => $q->where('rol', 'usuario'))
                ->get();
            $this->assertNotNull($conRolUsuario);
            $this->assertSame(2, $conRolUsuario->count());
        });
    }

    public function testWhereHasConCantidadYClosure(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //1 comentario coincide con 'comentario%': >= 1 cumple
            $resultado = Publicacion::where('slug', $this->publicada->slug)
                ->whereHas(
                    'comentarios',
                    fn ($q) => $q->where('contenido', 'LIKE', 'comentario%'),
                    '>=',
                    1
                )
                ->get();

            $this->assertNotNull($resultado);
            $this->assertSame(1, $resultado->count());
        });
    }

    public function testWhereHasRelacionInexistenteLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('La relacion noexiste no existe en el modelo');

        Publicacion::whereHas('noexiste');
    }

    public function testWhereHasOperadorInvalidoLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Operador no permitido');

        Publicacion::whereHas('comentarios', null, 'LIKE');
    }

    //******************************************************************
    // WITH COUNT
    //******************************************************************

    public function testWithCountHasMany(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $conComentarios = Publicacion::withCount('comentarios')
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();

            $this->assertSame(2, $conComentarios->comentarios_count);
            $this->assertTrue($conComentarios->relationLoaded('comentarios_count'));

            $sinComentarios = Publicacion::withCount('comentarios')
                ->where('slug', $this->borrador->slug)
                ->firstOrFail();

            $this->assertSame(0, $sinComentarios->comentarios_count);
        });
    }

    public function testWithCountEnColeccionYToArray(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $publicaciones = Publicacion::withCount('comentarios')
                ->whereIn('slug', [$this->publicada->slug, $this->borrador->slug])
                ->get();

            $this->assertNotNull($publicaciones);

            foreach ($publicaciones as $publicacion) {
                $esperado = $publicacion->slug === $this->publicada->slug ? 2 : 0;
                $this->assertSame($esperado, $publicacion->comentarios_count);

                //el conteo se incluye en toArray()
                $array = $publicacion->toArray();
                $this->assertArrayHasKey('comentarios_count', $array);
                $this->assertSame($esperado, $array['comentarios_count']);
            }
        });
    }

    public function testWithCountBelongsToYBelongsToMany(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            Model::db()->statementC_U_D(
                'INSERT INTO rol_usuario (usuario_id, rol_id) SELECT ?, id FROM roles LIMIT 2',
                [$this->carlos->id]
            );

            $usuario = Usuario::withCount('roles')
                ->where('correo', $this->carlos->correo)
                ->firstOrFail();

            $this->assertSame(2, $usuario->roles_count);

            $autor = Publicacion::withCount('usuario')
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();

            $this->assertSame(1, $autor->usuario_count);
        });
    }

    public function testWithCountConClosure(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $publicacion = Publicacion::withCount([
                'comentarios' => fn ($q) => $q->where('contenido', 'LIKE', '%unico%'),
            ])->where('slug', $this->publicada->slug)->firstOrFail();

            $this->assertSame(1, $publicacion->comentarios_count);

            //0 cuando el filtro no coincide
            $borrador = Publicacion::withCount([
                'comentarios' => fn ($q) => $q->where('contenido', 'nada-que-ver'),
            ])->where('slug', $this->publicada->slug)->firstOrFail();

            $this->assertSame(0, $borrador->comentarios_count);
        });
    }

    public function testWithCountCombinaConHasYWith(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $resultado = Publicacion::whereIn('slug', [$this->publicada->slug, $this->borrador->slug])
                ->has('comentarios')
                ->withCount('comentarios')
                ->with('usuario')
                ->where('estado', 'publicado')
                ->get();

            $this->assertNotNull($resultado);
            $this->assertSame(1, $resultado->count());

            $primera = $resultado->first();
            $this->assertSame(2, $primera->comentarios_count);
            $this->assertTrue($primera->relationLoaded('usuario'));
        });
    }

    public function testWithCountPrimeraPosicionEnFirst(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $publicacion = Publicacion::withCount('comentarios')->latest()->first();

            $this->assertNotNull($publicacion);
            $this->assertIsInt($publicacion->comentarios_count);
        });
    }

    public function testWithCountValorNoCallableLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('debe ser un closure');

        Publicacion::withCount(['comentarios' => 'no-soy-closure']);
    }
}
