<?php

namespace Tests\Integration;

use App\Models\Comentario;
use App\Models\Perfil;
use App\Models\Publicacion;
use App\Models\Usuario;
use Cronos\Model\Model;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de la Fase A del ORM:
 * - Eager loading anidado: with('usuario.perfil'), arboles y 3 niveles
 * - Constraints (closures) en with(): filtros sobre la consulta de la relacion
 * - Combinacion de anidado + constraints, arrays mixtos y validaciones
 *
 * Estructura de datos de cada test:
 * - admin  -> invita a -> carlos (invitado_por)
 * - carlos -> tiene perfil y 2 publicaciones (una publicada, una borrador)
 * - la publicacion publicada tiene un comentario de carlos
 */
class OrmEagerAvanzadoTest extends OrmTestCase
{
    private Usuario $admin;

    private Usuario $carlos;

    private Publicacion $publicada;

    private Publicacion $borrador;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Crea la estructura de datos dentro de la transaccion del test.
     * Los modelos quedan en $this->admin, $this->carlos, $this->publicada,
     * $this->borrador.
     */
    private function crearEscenario(): void
    {
        $this->admin = $this->crearUsuario(['nombre' => 'Admin Eager']);

        $this->carlos = $this->crearUsuario(['nombre' => 'Carlos Eager']);
        Model::db()->statementC_U_D(
            'UPDATE usuarios SET invitado_por = ? WHERE id = ?',
            [$this->admin->id, $this->carlos->id]
        );

        Perfil::create([
            'usuario_id' => $this->carlos->id,
            'biografia' => 'Biografia de Carlos',
            'telefono' => '555-1234',
            'fecha_nacimiento' => '1990-05-10',
            'sitio_web' => 'https://carlos.test',
        ]);

        $slug = uniqid('eager-');

        $this->publicada = Publicacion::create([
            'usuario_id' => $this->carlos->id,
            'titulo' => 'Publicacion Publicada',
            'slug' => $slug . '-publicada',
            'contenido' => 'contenido publicada',
        ]);
        Model::db()->statementC_U_D(
            'UPDATE publicaciones SET estado = ? WHERE id = ?',
            ['publicado', $this->publicada->id]
        );

        $this->borrador = Publicacion::create([
            'usuario_id' => $this->carlos->id,
            'titulo' => 'Publicacion Borrador',
            'slug' => $slug . '-borrador',
            'contenido' => 'contenido borrador',
        ]);

        Comentario::create([
            'publicacion_id' => $this->publicada->id,
            'usuario_id' => $this->carlos->id,
            'contenido' => 'Comentario de prueba',
        ]);
    }

    //******************************************************************
    // PASO 1: EAGER LOADING ANIDADO
    //******************************************************************

    public function testWithAnidadoDosNiveles(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $publicacion = Publicacion::with('usuario.perfil')
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();

            $this->assertTrue($publicacion->relationLoaded('usuario'));

            $usuario = $publicacion->getRelation('usuario');
            $this->assertInstanceOf(Usuario::class, $usuario);
            $this->assertTrue($usuario->relationLoaded('perfil'));

            $perfil = $usuario->getRelation('perfil');
            $this->assertInstanceOf(Perfil::class, $perfil);
            $this->assertSame('Biografia de Carlos', $perfil->biografia);
        });
    }

    public function testWithAnidadoTresNiveles(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //publicacion -> usuario (carlos) -> invitadoPor (admin) -> perfil? no tiene
            $publicacion = Publicacion::with('usuario.invitadoPor')
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();

            $usuario = $publicacion->getRelation('usuario');
            $this->assertInstanceOf(Usuario::class, $usuario);
            $this->assertTrue($usuario->relationLoaded('invitadoPor'));

            $admin = $usuario->getRelation('invitadoPor');
            $this->assertInstanceOf(Usuario::class, $admin);
            $this->assertSame($this->admin->correo, $admin->correo);
        });
    }

    public function testWithAnidadoEnArbolCargaCadaRelacionUnaVez(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //arbol: usuario -> perfil + invitadoPor (ambos anidados bajo usuario)
            $publicacion = Publicacion::with('usuario.perfil', 'usuario.invitadoPor')
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();

            $usuario = $publicacion->getRelation('usuario');
            $this->assertTrue($usuario->relationLoaded('perfil'));
            $this->assertTrue($usuario->relationLoaded('invitadoPor'));
            $this->assertSame('Biografia de Carlos', $usuario->getRelation('perfil')->biografia);
            $this->assertSame('Admin Eager', $usuario->getRelation('invitadoPor')->nombre);
        });
    }

    public function testWithAnidadoSobreColeccion(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $publicaciones = Publicacion::with('usuario.perfil')
                ->whereIn('slug', [$this->publicada->slug, $this->borrador->slug])
                ->get();

            $this->assertNotNull($publicaciones);
            $this->assertSame(2, $publicaciones->count());

            foreach ($publicaciones as $publicacion) {
                $usuario = $publicacion->getRelation('usuario');
                $this->assertInstanceOf(Usuario::class, $usuario);
                $this->assertTrue($usuario->relationLoaded('perfil'));
                $this->assertSame('Biografia de Carlos', $usuario->getRelation('perfil')->biografia);
            }
        });
    }

    public function testWithAnidadoDesdeHasMany(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //usuario -> publicaciones -> comentarios (hasMany anidado)
            $usuario = Usuario::with('publicaciones.comentarios')
                ->where('correo', $this->carlos->correo)
                ->firstOrFail();

            $publicaciones = $usuario->getRelation('publicaciones');
            $this->assertInstanceOf(\Cronos\Model\ModelCollection::class, $publicaciones);
            $this->assertSame(2, $publicaciones->count());

            foreach ($publicaciones as $publicacion) {
                $this->assertTrue($publicacion->relationLoaded('comentarios'));

                if ($publicacion->slug === $this->publicada->slug) {
                    $this->assertSame(1, $publicacion->getRelation('comentarios')->count());
                } else {
                    $this->assertSame(0, $publicacion->getRelation('comentarios')->count());
                }
            }
        });
    }

    //******************************************************************
    // PASO 2: CONSTRAINTS (CLOSURES) EN WITH
    //******************************************************************

    public function testConstraintEnHasManyFiltraResultados(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $usuario = Usuario::with(['publicaciones' => fn ($q) => $q->where('estado', 'publicado')])
                ->where('correo', $this->carlos->correo)
                ->firstOrFail();

            $publicaciones = $usuario->getRelation('publicaciones');
            $this->assertSame(1, $publicaciones->count());
            $this->assertSame($this->publicada->slug, $publicaciones->first()->slug);
        });
    }

    public function testConstraintEnHasManyConOrderBy(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $usuario = Usuario::with(['publicaciones' => fn ($q) => $q->orderBy('id', 'DESC')])
                ->where('correo', $this->carlos->correo)
                ->firstOrFail();

            $publicaciones = $usuario->getRelation('publicaciones');
            $this->assertSame(2, $publicaciones->count());
            //DESC: el borrador (creado despues) primero
            $this->assertSame($this->borrador->slug, $publicaciones->first()->slug);
        });
    }

    public function testConstraintEnBelongsToConSelect(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $publicacion = Publicacion::with(['usuario' => fn ($q) => $q->select('id', 'nombre', 'correo')])
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();

            $usuario = $publicacion->getRelation('usuario');
            $this->assertInstanceOf(Usuario::class, $usuario);
            $this->assertSame('Carlos Eager', $usuario->nombre);
            $this->assertNull($usuario->rol); //no estaba en el select
        });
    }

    public function testConstraintCombinadoConAnidado(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //constraint sobre usuario + anidado usuario.perfil en el mismo with
            $publicacion = Publicacion::with([
                'usuario' => fn ($q) => $q->where('correo', $this->carlos->correo),
                'usuario.perfil',
            ])->where('slug', $this->publicada->slug)->firstOrFail();

            $usuario = $publicacion->getRelation('usuario');
            $this->assertInstanceOf(Usuario::class, $usuario);
            $this->assertTrue($usuario->relationLoaded('perfil'));
            $this->assertSame('Biografia de Carlos', $usuario->getRelation('perfil')->biografia);
        });
    }

    public function testConstraintEnHasOne(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            //constraint que NO coincide: la relacion queda null
            $usuario = Usuario::with(['perfil' => fn ($q) => $q->where('biografia', 'no-existe')])
                ->where('correo', $this->carlos->correo)
                ->firstOrFail();

            $this->assertNull($usuario->getRelation('perfil'));

            //constraint que SI coincide
            $usuario2 = Usuario::with(['perfil' => fn ($q) => $q->where('biografia', 'Biografia de Carlos')])
                ->where('correo', $this->carlos->correo)
                ->firstOrFail();

            $this->assertInstanceOf(Perfil::class, $usuario2->getRelation('perfil'));
        });
    }

    public function testWithAceptaArrayMixtoDeStringsYClosures(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $usuario = Usuario::with([
                'perfil',
                'publicaciones' => fn ($q) => $q->where('estado', 'borrador'),
            ])->where('correo', $this->carlos->correo)->firstOrFail();

            $this->assertTrue($usuario->relationLoaded('perfil'));
            $this->assertSame(1, $usuario->getRelation('publicaciones')->count());
        });
    }

    public function testConstraintEnBelongsToManyLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no esta soportado para la relacion belongsToMany');

        $this->rollbackAfter(function () {
            $this->crearEscenario();

            Publicacion::with(['etiquetas' => fn ($q) => $q->where('id', 1)])
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();
        });
    }

    //******************************************************************
    // VALIDACIONES
    //******************************************************************

    public function testWithRelacionAnidadaInexistenteLanzaError(): void
    {
        $this->rollbackAfter(function () {
            $this->crearEscenario();

            $this->expectException(\Error::class);
            $this->expectExceptionMessage('La relacion noexiste no existe en el modelo');

            Publicacion::with('usuario.noexiste')
                ->where('slug', $this->publicada->slug)
                ->firstOrFail();
        });
    }

    public function testWithNombreInvalidoLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Nombre de relacion no valido');

        Publicacion::with('usuario; DROP TABLE usuarios');
    }

    public function testWithAnidadoPuntoFinalLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Nombre de relacion no valido');

        Publicacion::with('usuario.');
    }

    public function testWithValorNoCallableEnMapaLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('debe ser un closure');

        Publicacion::with(['usuario' => 'no-soy-closure']);
    }
}
