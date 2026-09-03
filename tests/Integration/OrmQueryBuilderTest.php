<?php

namespace Tests\Integration;

use App\Models\Perfil;
use App\Models\Publicacion;
use App\Models\Rol;
use App\Models\Usuario;
use Cronos\Model\Model;
use Cronos\Model\ModelCollection;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de regresion del query builder del ORM: la API existente
 * (where, select, join, orderBy, limit, agregados, relaciones, dd)
 * debe seguir funcionando EXACTAMENTE igual tras la refactorizacion,
 * y el estado de una consulta nunca debe contaminar a otra.
 */
class OrmQueryBuilderTest extends OrmTestCase
{
    public function testWhereFirstEncuentraUsuario(): void
    {
        $usuario = Usuario::where('correo', 'admin@admin.com')->first();

        $this->assertNotNull($usuario);
        $this->assertInstanceOf(Usuario::class, $usuario);
        $this->assertSame('Admin', $usuario->nombre);
    }

    public function testWhereConOperador(): void
    {
        $usuarios = Usuario::where('id', '>', 0)->get();

        $this->assertNotNull($usuarios);
        $this->assertGreaterThanOrEqual(1, $usuarios->count());
    }

    public function testWhereConValorCeroNoLanzaError(): void
    {
        //regresion: antes empty(0) lanzaba "Debe proveer el segundo parametro"
        $resultado = Usuario::where('id', 0)->get();

        $this->assertNull($resultado);
    }

    public function testAislamientoDeEstadoEntreModelos(): void
    {
        //la consulta pendiente de Usuario NO debe contaminar la consulta de Rol
        //(antes del refactor el estado era estatico global y esto fallaba)
        $this->rollbackAfter(function () {
            Usuario::create([
                'nombre' => 'Aislamiento',
                'correo' => 'aislamiento-' . uniqid() . '@test.com',
                'contrasena' => password_hash('x', PASSWORD_BCRYPT),
                'rol' => 'usuario',
                'avatar' => null,
                'correo_verificado_en' => null,
                'token_recordar' => null,
            ]);

            Rol::create([
                'nombre' => 'RolAislamiento' . uniqid(),
                'slug' => 'rol-aislamiento-' . uniqid(),
                'descripcion' => null,
            ]);

            $rol = Rol::where('nombre', 'RolAislamiento')->first();
            $this->assertNull($rol);

            $rolCreado = Rol::orderBy('id', 'DESC')->first();
            $this->assertNotNull($rolCreado);
            $this->assertSame('RolAislamiento', substr($rolCreado->nombre, 0, 14));
        });
    }

    public function testSelectJoinGetMantieneFormaDeUso(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Publicacion Join',
                'slug' => 'publicacion-join-' . uniqid(),
                'contenido' => 'Contenido de la publicacion join',
            ]);

            $resultado = Publicacion::select('publicaciones.*', 'usuarios.nombre')
                ->join('usuarios', 'usuarios.id', '=', 'publicaciones.usuario_id')
                ->orderBy('publicaciones.created_at', 'DESC')
                ->get();

            $this->assertNotNull($resultado);

            $filtradas = $resultado->filter(
                fn ($item) => $item->id == $publicacion->id
            );

            $this->assertSame(1, $filtradas->count());
            $this->assertSame('Usuario ORM Test', $filtradas->first()->nombre);
        });
    }

    public function testLimit(): void
    {
        $this->rollbackAfter(function () {
            $this->crearUsuario();

            $resultado = Usuario::limit(2)->get();

            $this->assertNotNull($resultado);
            $this->assertLessThanOrEqual(2, $resultado->count());
        });
    }

    public function testAgregadosMaxMinSumAvg(): void
    {
        $admin = Usuario::where('correo', 'admin@admin.com')->first();
        $this->assertNotNull($admin);

        $max = Publicacion::where('usuario_id', $admin->id)->select('vistas')->max();
        $this->assertIsNumeric($max);

        $min = Publicacion::where('usuario_id', $admin->id)->select('vistas')->min();
        $this->assertIsNumeric($min);

        $sum = Publicacion::where('usuario_id', $admin->id)->select('vistas')->sum();
        $this->assertIsNumeric($sum);

        $avg = Publicacion::where('usuario_id', $admin->id)->select('vistas')->avg();
        $this->assertIsNumeric($avg);
    }

    public function testAgregadosRequierenUnaColumna(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("no agrego ninguna columna para obtener el valor maximo Model::select('columna')->max()");

        Publicacion::max();
    }

    public function testAgregadosRechazanVariasColumnas(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("solo se puede obtener el valor suma de una columna Model::select('columna')->sum()");

        Publicacion::select('vistas', 'usuario_id')->sum();
    }

    public function testDdRetornaEstructuraSinEjecutar(): void
    {
        $data = Usuario::where('correo', 'prueba-dd@test.com')->limit(5)->dd();

        $this->assertArrayHasKey('sql_raw', $data);
        $this->assertArrayHasKey('sql_debug', $data);
        $this->assertArrayHasKey('bindings', $data);
        $this->assertArrayHasKey('model', $data);
        $this->assertSame(Usuario::class, $data['model']);
        $this->assertStringContainsString('LIMIT 5', $data['sql_raw']);
        $this->assertStringContainsString("'prueba-dd@test.com'", $data['sql_debug']);
    }

    public function testFind(): void
    {
        $usuario = Usuario::find(1);

        $this->assertNotNull($usuario);
        $this->assertSame('1', (string) $usuario->id);

        $this->assertNull(Usuario::find(999999999));
    }

    public function testAll(): void
    {
        $usuarios = Usuario::all();

        $this->assertInstanceOf(ModelCollection::class, $usuarios);
        $this->assertGreaterThanOrEqual(1, $usuarios->count());
    }

    public function testFirstNotHidden(): void
    {
        $usuario = Usuario::where('correo', 'admin@admin.com')->firstNotHidden();

        $this->assertNotNull($usuario);
        $this->assertInstanceOf(Usuario::class, $usuario);
    }

    public function testRelacionHasOne(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            Perfil::create([
                'usuario_id' => $usuario->id,
                'biografia' => 'Bio de prueba',
                'telefono' => null,
                'fecha_nacimiento' => null,
                'sitio_web' => null,
            ]);

            $encontrado = Usuario::where('correo', $usuario->correo)->first();

            $perfil = $encontrado->perfil()->get();

            $this->assertInstanceOf(Perfil::class, $perfil);
            $this->assertEquals($usuario->id, $perfil->usuario_id);
        });
    }

    public function testRelacionHasMany(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            for ($i = 1; $i <= 2; $i++) {
                Publicacion::create([
                    'usuario_id' => $usuario->id,
                    'titulo' => "Publicacion $i",
                    'slug' => "publicacion-$i-" . uniqid(),
                    'contenido' => "Contenido $i",
                ]);
            }

            $encontrado = Usuario::where('correo', $usuario->correo)->first();

            $publicaciones = $encontrado->publicaciones()->get();

            $this->assertInstanceOf(ModelCollection::class, $publicaciones);
            $this->assertSame(2, $publicaciones->count());
        });
    }

    public function testRelacionBelongsToMany(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $rol = Rol::create([
                'nombre' => 'RolPivote' . uniqid(),
                'slug' => 'rol-pivote-' . uniqid(),
                'descripcion' => null,
            ]);

            Model::db()->statementC_U_D(
                'INSERT INTO rol_usuario (usuario_id, rol_id) VALUES (?, ?)',
                [$usuario->id, $rol->id]
            );

            $encontrado = Usuario::where('correo', $usuario->correo)->first();

            $roles = $encontrado->roles()->get();

            $this->assertInstanceOf(ModelCollection::class, $roles);
            $this->assertSame(1, $roles->count());
            $this->assertEquals($rol->id, $roles->first()->id);
        });
    }

    public function testWhereYWhereBetweenSonExcluyentes(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('el metodo where() no puede estar con el metodo whereBetween()');

        Usuario::where('id', 1)->whereBetween('id', 1, 2)->get();
    }

    public function testAndWhereRequiereWherePrevio(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no existe el metodo where() o debe estar antes');

        Usuario::andWhere('id', 1)->get();
    }

    public function testOperadoresDeComparacionValidos(): void
    {
        $this->rollbackAfter(function () {
            $this->crearUsuario();

            $mayores = Usuario::where('id', '>=', 1)->get();
            $this->assertNotNull($mayores);

            $distintos = Usuario::where('id', '<>', 999999999)->get();
            $this->assertNotNull($distintos);
        });
    }

    public function testOrderByInvalidoLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Dirección de orden inválida');

        Usuario::orderBy('id', 'LATERAL')->get();
    }
}
