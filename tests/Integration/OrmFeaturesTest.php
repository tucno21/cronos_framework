<?php

namespace Tests\Integration;

use App\Models\Perfil;
use App\Models\Publicacion;
use App\Models\Rol;
use App\Models\Usuario;
use Cronos\Model\Model;
use Cronos\Model\ModelCollection;
use Tests\Integration\Fixtures\PublicacionBorrable;
use Tests\Integration\Fixtures\PublicacionCasteada;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de las mejoras del ORM:
 * - whereIn / whereNotIn / whereNull / whereNotNull
 * - count() / offset() / update() y delete() encadenables
 * - transacciones (Model::transaction)
 * - eager loading (with) + acceso magico a relaciones
 * - casts opt-in y SoftDeletes opt-in
 * - seguridad: sanitizacion de identificadores y operadores
 * - toJson() respeta $hidden
 */
class OrmFeaturesTest extends OrmTestCase
{
    public function testWhereIn(): void
    {
        $this->rollbackAfter(function () {
            $a = $this->crearUsuario();
            $b = $this->crearUsuario();

            $resultado = Usuario::whereIn('correo', [$a->correo, $b->correo])->get();

            $this->assertNotNull($resultado);
            $this->assertSame(2, $resultado->count());
        });
    }

    public function testWhereInVacioLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('whereIn() requiere un array con al menos un valor');

        Usuario::whereIn('correo', [])->get();
    }

    public function testWhereNotIn(): void
    {
        $this->rollbackAfter(function () {
            $a = $this->crearUsuario();
            $b = $this->crearUsuario();

            $resultado = Usuario::whereNotIn('correo', [$a->correo, $b->correo])->get();

            $this->assertNotNull($resultado);

            foreach ($resultado as $usuario) {
                $this->assertNotSame($a->correo, $usuario->correo);
                $this->assertNotSame($b->correo, $usuario->correo);
            }
        });
    }

    public function testWhereNullYWhereNotNull(): void
    {
        $this->rollbackAfter(function () {
            $invitador = $this->crearUsuario();
            $invitado = $this->crearUsuario();

            //invitado_por no esta en $fillable: se asigna con UPDATE crudo
            Model::db()->statementC_U_D(
                'UPDATE usuarios SET invitado_por = ? WHERE id = ?',
                [$invitador->id, $invitado->id]
            );

            $sinInvitador = Usuario::whereNull('invitado_por')->where('correo', $invitador->correo)->first();
            $this->assertNotNull($sinInvitador);

            $conInvitador = Usuario::whereNotNull('invitado_por')->where('correo', $invitado->correo)->first();
            $this->assertNotNull($conInvitador);
            $this->assertEquals($invitador->id, $conInvitador->invitado_por);
        });
    }

    public function testCount(): void
    {
        $this->rollbackAfter(function () {
            $a = $this->crearUsuario();
            $b = $this->crearUsuario();

            $this->assertSame(2, Usuario::whereIn('correo', [$a->correo, $b->correo])->count());
            $this->assertSame(0, Usuario::where('correo', 'nadie-con-este-correo@test.com')->count());
        });
    }

    public function testOffset(): void
    {
        $this->rollbackAfter(function () {
            $a = $this->crearUsuario();
            $b = $this->crearUsuario();
            $c = $this->crearUsuario();

            $todos = Usuario::whereIn('correo', [$a->correo, $b->correo, $c->correo])
                ->orderBy('id')
                ->get()
                ->pluck('id');

            $this->assertCount(3, $todos);

            $pagina2 = Usuario::whereIn('correo', [$a->correo, $b->correo, $c->correo])
                ->orderBy('id')
                ->limit(2)
                ->offset(1)
                ->get()
                ->pluck('id');

            $this->assertCount(2, $pagina2);
            $this->assertEquals($todos[1], $pagina2[0]);
            $this->assertEquals($todos[2], $pagina2[1]);
        });
    }

    public function testOffsetNegativoLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('El offset no puede ser negativo');

        Usuario::offset(-1)->get();
    }

    public function testUpdateEncadenable(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $filas = Usuario::where('correo', $usuario->correo)->update(['nombre' => 'Nombre Actualizado']);

            $this->assertSame(1, $filas);

            $actualizado = Usuario::where('correo', $usuario->correo)->first();
            $this->assertSame('Nombre Actualizado', $actualizado->nombre);
        });
    }

    public function testUpdateEncadenableSinWhereLanzaError(): void
    {
        //el guard corre en el builder (no alcanza al modelo por la API con id):
        //se valida usando newQuery() directamente
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('update() requiere al menos una condicion where()');

        Usuario::find(1)?->newQuery()->update(['nombre' => 'Masivo']);
    }

    public function testUpdateEncadenableValidaFillable(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no está permitido en el modelo');

        Usuario::where('id', 1)->update(['campo_inexistente' => 1]);
    }

    public function testDeleteEncadenable(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $filas = Usuario::where('correo', $usuario->correo)->delete();

            $this->assertSame(1, $filas);
            $this->assertNull(Usuario::where('correo', $usuario->correo)->first());
        });
    }

    public function testDeleteEncadenableSinWhereLanzaError(): void
    {
        //el guard corre en el builder (no alcanza al modelo por la API con id):
        //se valida usando newQuery() directamente
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('delete() requiere al menos una condicion where()');

        Usuario::find(1)?->newQuery()->delete();
    }

    public function testTransaccionHaceCommit(): void
    {
        $this->rollbackAfter(function () {
            $correo = 'tx-commit-' . uniqid() . '@test.com';

            $resultado = Model::transaction(function () use ($correo) {
                return $this->crearUsuario(['correo' => $correo])->id;
            });

            $this->assertNotNull($resultado);
            $this->assertNotNull(Usuario::where('correo', $correo)->first());

            //limpieza manual (este rollbackAfter ya esta dentro de una transaccion)
            Usuario::where('correo', $correo)->delete();
        });
    }

    public function testTransaccionHaceRollback(): void
    {
        $correo = 'tx-rollback-' . uniqid() . '@test.com';

        try {
            Model::transaction(function () use ($correo): void {
                $this->crearUsuario(['correo' => $correo]);

                throw new \RuntimeException('error intencional');
            });
            $this->fail('Se esperaba una RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('error intencional', $e->getMessage());
        }

        $this->assertNull(Usuario::where('correo', $correo)->first());
    }

    public function testTransaccionAnidadaParticipaDeLaExterna(): void
    {
        //la transaccion anidada no hace commit por si sola: si la externa
        //hace rollback, todo el trabajo interno se descarta
        $correo = 'tx-anidada-' . uniqid() . '@test.com';

        try {
            Model::transaction(function () use ($correo): void {
                Model::transaction(function () use ($correo): void {
                    $this->crearUsuario(['correo' => $correo]);
                });

                throw new \RuntimeException('rollback externo');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('rollback externo', $e->getMessage());
        }

        $this->assertNull(Usuario::where('correo', $correo)->first());
    }

    public function testWithCargaRelacionesHasOneYHasMany(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            Perfil::create([
                'usuario_id' => $usuario->id,
                'biografia' => 'Bio eager',
                'telefono' => null,
                'fecha_nacimiento' => null,
                'sitio_web' => null,
            ]);

            for ($i = 1; $i <= 2; $i++) {
                Publicacion::create([
                    'usuario_id' => $usuario->id,
                    'titulo' => "Eager $i",
                    'slug' => "eager-$i-" . uniqid(),
                    'contenido' => "Contenido eager $i",
                ]);
            }

            $encontrado = Usuario::with('perfil', 'publicaciones')->where('correo', $usuario->correo)->first();

            $this->assertNotNull($encontrado);
            $this->assertTrue($encontrado->relationLoaded('perfil'));
            $this->assertTrue($encontrado->relationLoaded('publicaciones'));

            $this->assertInstanceOf(Perfil::class, $encontrado->getRelation('perfil'));
            $this->assertSame('Bio eager', $encontrado->getRelation('perfil')->biografia);

            $publicaciones = $encontrado->getRelation('publicaciones');
            $this->assertInstanceOf(ModelCollection::class, $publicaciones);
            $this->assertSame(2, $publicaciones->count());
        });
    }

    public function testWithAgrupaCorrectamenteVariosModelos(): void
    {
        $this->rollbackAfter(function () {
            $a = $this->crearUsuario();
            $b = $this->crearUsuario();

            Perfil::create([
                'usuario_id' => $a->id,
                'biografia' => 'Bio A',
                'telefono' => null,
                'fecha_nacimiento' => null,
                'sitio_web' => null,
            ]);

            //el usuario B no tiene perfil: debe quedar null, no el perfil de A

            $usuarios = Usuario::with('perfil')->whereIn('correo', [$a->correo, $b->correo])->orderBy('id')->get();

            $this->assertNotNull($usuarios);
            $this->assertSame(2, $usuarios->count());

            $esperados = [$a->correo, $b->correo];
            $correos = array_column($usuarios->toArray(), 'correo');
            $this->assertSame($esperados, $correos, 'orden por id');

            $items = array_values($usuarios->toArray());
            $perfilA = $items[0]['perfil'];
            $perfilB = $items[1]['perfil'];

            $this->assertIsArray($perfilA);
            $this->assertSame('Bio A', $perfilA['biografia']);
            $this->assertNull($perfilB);
        });
    }

    public function testWithCargaRelacionBelongsTo(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Con autor',
                'slug' => 'con-autor-' . uniqid(),
                'contenido' => 'Contenido con autor',
            ]);

            $encontrada = Publicacion::with('usuario')->where('slug', $publicacion->slug)->first();

            $this->assertNotNull($encontrada);
            $this->assertInstanceOf(Usuario::class, $encontrada->getRelation('usuario'));
            $this->assertSame($usuario->correo, $encontrada->getRelation('usuario')->correo);
        });
    }

    public function testWithCargaRelacionBelongsToMany(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $rol = Rol::create([
                'nombre' => 'RolEager' . uniqid(),
                'slug' => 'rol-eager-' . uniqid(),
                'descripcion' => null,
            ]);

            Model::db()->statementC_U_D(
                'INSERT INTO rol_usuario (usuario_id, rol_id) VALUES (?, ?)',
                [$usuario->id, $rol->id]
            );

            $encontrado = Usuario::with('roles')->where('correo', $usuario->correo)->first();

            $this->assertNotNull($encontrado);

            $roles = $encontrado->getRelation('roles');
            $this->assertInstanceOf(ModelCollection::class, $roles);
            $this->assertSame(1, $roles->count());
            $this->assertEquals($rol->id, $roles->first()->id);
        });
    }

    public function testWithRelacionInexistenteLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no existe en el modelo');

        Usuario::with('relacionQueNoExiste')->get();
    }

    public function testAccesoMagicoALaRelacion(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            Perfil::create([
                'usuario_id' => $usuario->id,
                'biografia' => 'Bio magica',
                'telefono' => null,
                'fecha_nacimiento' => null,
                'sitio_web' => null,
            ]);

            $encontrado = Usuario::where('correo', $usuario->correo)->first();

            $perfil = $encontrado->perfil;

            $this->assertInstanceOf(Perfil::class, $perfil);
            $this->assertSame('Bio magica', $perfil->biografia);

            //acceso repetido usa la cache de la instancia (misma instancia)
            $this->assertSame($perfil, $encontrado->perfil);
        });
    }

    public function testCastsConviertenTiposAlHidratar(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Con casts',
                'slug' => 'con-casts-' . uniqid(),
                'contenido' => 'Contenido con casts',
            ]);

            $encontrada = PublicacionCasteada::where('usuario_id', $usuario->id)->first();

            $this->assertNotNull($encontrada);
            $this->assertIsInt($encontrada->usuario_id);
            $this->assertIsInt($encontrada->vistas);
            $this->assertIsString($encontrada->titulo);

            //sin casts, el modelo original conserva el tipo que entregue PDO
            //(segun version/driver puede ser string o int nativo)
            $sinCasts = Publicacion::where('usuario_id', $usuario->id)->first();
            $this->assertSame((string) $encontrada->usuario_id, (string) $sinCasts->usuario_id);
        });
    }

    public function testSoftDeletesFiltraConsultasYBorrado(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = PublicacionBorrable::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Publicacion Borrable',
                'slug' => 'publicacion-borrable-' . uniqid(),
                'contenido' => 'Contenido de la publicacion borrable',
            ]);

            $id = $publicacion->id;

            //visible antes del borrado
            $this->assertNotNull(PublicacionBorrable::find($id));
            $this->assertFalse($publicacion->trashed());

            //delete() marca eliminado_en en lugar de borrar
            $this->assertTrue(PublicacionBorrable::delete($id));

            //oculto para el modelo con SoftDeletes
            $this->assertNull(PublicacionBorrable::find($id));
            $this->assertNull(PublicacionBorrable::where('id', $id)->first());
            $this->assertSame(0, PublicacionBorrable::where('id', $id)->count());

            //pero la fila sigue existiendo para un modelo sin SoftDeletes
            $crudo = Publicacion::find($id);
            $this->assertNotNull($crudo);
            $this->assertNotNull($crudo->eliminado_en);

            //restore() lo vuelve visible
            $this->assertTrue(PublicacionBorrable::restore($id));
            $this->assertNotNull(PublicacionBorrable::find($id));

            //forceDelete() borra fisicamente
            $this->assertTrue(PublicacionBorrable::forceDelete($id));
            $this->assertNull(Publicacion::find($id));
        });
    }

    public function testDeleteEncadenableRespetaSoftDeletes(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = PublicacionBorrable::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Borrable Cadena',
                'slug' => 'borrable-cadena-' . uniqid(),
                'contenido' => 'Contenido borrable cadena',
            ]);

            $id = $publicacion->id;

            $filas = PublicacionBorrable::where('id', $id)->delete();
            $this->assertSame(1, $filas);

            //borrado logico: la fila existe pero filtrada
            $this->assertNull(PublicacionBorrable::where('id', $id)->first());
            $this->assertNotNull(Publicacion::find($id));
        });
    }

    public function testUpdatePorIdNoActualizaRegistrosBorrados(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = PublicacionBorrable::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Update Borrado',
                'slug' => 'update-borrado-' . uniqid(),
                'contenido' => 'Contenido update borrado',
            ]);

            $id = $publicacion->id;
            PublicacionBorrable::delete($id);

            $this->assertNull(PublicacionBorrable::update($id, ['titulo' => 'No Debe Aplicar']));
        });
    }

    public function testRestoreSinTraitLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no usa el trait SoftDeletes');

        Usuario::restore(1);
    }

    public function testSanitizacionDeIdentificadores(): void
    {
        //inyeccion por nombre de columna
        try {
            Usuario::where('id; DROP TABLE usuarios', 1)->get();
            $this->fail('Se esperaba un Error por identificador invalido');
        } catch (\Error $e) {
            $this->assertStringContainsString('Identificador no valido', $e->getMessage());
        }

        //inyeccion en orderBy
        try {
            Usuario::orderBy('id; DROP TABLE usuarios')->get();
            $this->fail('Se esperaba un Error por identificador invalido');
        } catch (\Error $e) {
            $this->assertStringContainsString('Identificador no valido', $e->getMessage());
        }

        //inyeccion en join
        try {
            Usuario::join('usuarios; DROP TABLE usuarios', 'usuarios.id', '=', 'usuarios.id')->get();
            $this->fail('Se esperaba un Error por identificador invalido');
        } catch (\Error $e) {
            $this->assertStringContainsString('Identificador no valido', $e->getMessage());
        }

        //columna con fragmento sospechoso en select
        try {
            Usuario::select('contrasena FROM usuarios--')->get();
            $this->fail('Se esperaba un Error por columna de seleccion invalida');
        } catch (\Error $e) {
            $this->assertStringContainsString('Columna de seleccion no valida', $e->getMessage());
        }

        //la tabla usuarios sigue existiendo: nada se ejecuto
        $this->assertNotNull(Usuario::find(1));
    }

    public function testOperadorInvalidoLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Operador no permitido');

        Usuario::where('id', 'DROP', 1)->get();
    }

    public function testToJsonRespetaHidden(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $json = Usuario::where('correo', $usuario->correo)->get()->toJson();
            $data = json_decode($json, true);

            $this->assertIsArray($data);
            $this->assertCount(1, $data);

            //regresion: antes toJson serializaba attributes crudos exponiendo hidden
            $this->assertArrayNotHasKey('contrasena', $data[0]);
            $this->assertArrayNotHasKey('token_recordar', $data[0]);
            $this->assertSame('Usuario ORM Test', $data[0]['nombre']);
        });
    }
}
