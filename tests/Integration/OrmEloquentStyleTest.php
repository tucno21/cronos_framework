<?php

namespace Tests\Integration;

use App\Models\Publicacion;
use App\Models\Usuario;
use Cronos\Model\Model;
use Cronos\Model\ModelNotFoundException;
use Tests\Integration\Fixtures\PublicacionBorrable;
use Tests\Integration\Fixtures\PublicacionConCastsRicos;
use Tests\Integration\Fixtures\PublicacionConScopes;
use Tests\Integration\Fixtures\PublicacionSinTimestamps;
use Tests\TestCase\OrmTestCase;

/**
 * Tests del endurecimiento del ORM alineado con Eloquent 13:
 * - CRUD por instancia: save(), $model->update([...]), $model->delete(),
 *   $model->forceDelete(), $model->restore(), $model->refresh()
 * - findOrFail() y firstOrFail() con ModelNotFoundException
 * - Soft deletes a nivel consulta: withTrashed(), onlyTrashed(), restore()
 * - Agregados con columna (sum/avg/min/max), value(), exists(), doesntExist(),
 *   latest()/oldest()
 * - Casts ricos: datetime, decimal:N, array/json
 * - Timestamps configurables ($timestamps = false, constantes CREATED_AT)
 * - ModelCollection completa: sum/avg/min/max/groupBy/sortBy/contains/first(cb)
 * - preventLazyLoading() (deteccion de N+1)
 * - toArray() no destructivo con $hidden
 * - Compatibilidad: los estaticos con id siguen funcionando
 */
class OrmEloquentStyleTest extends OrmTestCase
{
    private function crearPublicacion(int $usuarioId, string $slug, string $contenido = 'contenido de prueba'): Publicacion
    {
        $publicacion = Publicacion::create([
            'usuario_id' => $usuarioId,
            'titulo' => 'Titulo ' . $slug,
            'slug' => $slug,
            'contenido' => $contenido,
        ]);

        $this->assertNotNull($publicacion);

        return $publicacion;
    }

    private function crearPublicacionBorrable(int $usuarioId, string $slug): PublicacionBorrable
    {
        $publicacion = PublicacionBorrable::create([
            'usuario_id' => $usuarioId,
            'titulo' => 'Titulo ' . $slug,
            'slug' => $slug,
            'contenido' => 'contenido de prueba',
        ]);

        $this->assertNotNull($publicacion);

        return $publicacion;
    }

    private function fijarVistas(int $publicacionId, int $vistas): void
    {
        Model::db()->statementC_U_D(
            'UPDATE publicaciones SET vistas = ? WHERE id = ?',
            [$vistas, $publicacionId]
        );
    }

    //******************************************************************
    // PASO 2: CRUD POR INSTANCIA (ESTILO ELOQUENT)
    //******************************************************************

    public function testSaveInsertaCuandoNoHayClavePrimaria(): void
    {
        $this->rollbackAfter(function () {
            $usuario = new Usuario();
            $usuario->nombre = 'Usuario Save';
            $usuario->correo = 'save-' . uniqid() . '@test.com';
            $usuario->contrasena = password_hash('clave123', PASSWORD_BCRYPT);
            $usuario->rol = 'usuario';
            $usuario->avatar = null;
            $usuario->correo_verificado_en = null;
            $usuario->token_recordar = null;

            $this->assertTrue($usuario->save());
            $this->assertNotNull($usuario->id);

            $enBd = Usuario::find($usuario->id);
            $this->assertNotNull($enBd);
            $this->assertSame('Usuario Save', $enBd->nombre);
            $this->assertNotNull($enBd->created_at);
        });
    }

    public function testSaveActualizaSoloLosAtributosSucios(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $fresco = Usuario::find($usuario->id);
            $this->assertNotNull($fresco);

            $fresco->nombre = 'Nombre Editado';
            $this->assertTrue($fresco->save());

            $enBd = Usuario::find($usuario->id);
            $this->assertNotNull($enBd);
            $this->assertSame('Nombre Editado', $enBd->nombre);
            $this->assertSame($usuario->correo, $enBd->correo);
        });
    }

    public function testSaveSinCambiosNoEjecutaNadaYDevuelveTrue(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $fresco = Usuario::find($usuario->id);
            $this->assertNotNull($fresco);
            $this->assertTrue($fresco->save());
        });
    }

    public function testUpdateDeInstancia(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $fresco = Usuario::find($usuario->id);
            $this->assertNotNull($fresco);
            $this->assertTrue($fresco->update(['nombre' => 'Via Update Instancia']));

            $enBd = Usuario::find($usuario->id);
            $this->assertNotNull($enBd);
            $this->assertSame('Via Update Instancia', $enBd->nombre);
        });
    }

    public function testDeleteYRestoreDeInstanciaConSoftDeletes(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = $this->crearPublicacion((int) $usuario->id, 'instancia-borrable');

            $fresca = PublicacionBorrable::find($publicacion->id);
            $this->assertNotNull($fresca);

            $this->assertTrue($fresca->delete());
            $this->assertNull(PublicacionBorrable::find($publicacion->id));
            $this->assertTrue($fresca->trashed());

            $this->assertTrue($fresca->restore());
            $this->assertNotNull(PublicacionBorrable::find($publicacion->id));
            $this->assertFalse($fresca->trashed());
        });
    }

    public function testForceDeleteDeInstancia(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = $this->crearPublicacion((int) $usuario->id, 'instancia-force');

            $fresca = PublicacionBorrable::find($publicacion->id);
            $this->assertNotNull($fresca);

            $this->assertTrue($fresca->forceDelete());

            $resto = PublicacionBorrable::customQuery(
                'SELECT COUNT(*) AS total FROM publicaciones WHERE id = ?',
                [$publicacion->id]
            );
            $this->assertSame(0, (int) ((array) $resto[0])['total']);
        });
    }

    public function testRefresh(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $fresco = Usuario::find($usuario->id);
            $this->assertNotNull($fresco);
            $this->assertSame('Usuario ORM Test', $fresco->nombre);

            Model::db()->statementC_U_D(
                'UPDATE usuarios SET nombre = ? WHERE id = ?',
                ['Cambio Externo', $usuario->id]
            );

            //antes del refresh la instancia conserva el valor viejo
            $this->assertSame('Usuario ORM Test', $fresco->nombre);

            $fresco->refresh();
            $this->assertSame('Cambio Externo', $fresco->nombre);
        });
    }

    public function testFindOrFail(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $encontrado = Usuario::findOrFail($usuario->id);
            $this->assertSame((int) $usuario->id, (int) $encontrado->id);

            $this->expectException(ModelNotFoundException::class);
            $this->expectExceptionMessage('No hay resultados para el modelo');

            Usuario::findOrFail(999999999);
        });
    }

    public function testFirstOrFail(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $encontrado = Usuario::where('correo', $usuario->correo)->firstOrFail();
            $this->assertSame((int) $usuario->id, (int) $encontrado->id);

            $this->expectException(ModelNotFoundException::class);
            $this->expectExceptionMessage('No hay resultados de consulta');

            Usuario::where('correo', 'nadie-con-este-correo@test.com')->firstOrFail();
        });
    }

    public function testUpdateYDeletePorInstanciaReemplazanLosEstaticos(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            //estilo Eloquent: la instancia manda
            $fresco = Usuario::findOrFail($usuario->id);
            $this->assertTrue($fresco->update(['nombre' => 'Update Instancia']));
            $this->assertSame('Update Instancia', Usuario::find($usuario->id)->nombre);

            $this->assertTrue($fresco->delete());
            $this->assertNull(Usuario::find($usuario->id));
        });
    }

    //******************************************************************
    // PASO 1: SOFT DELETES A NIVEL CONSULTA
    //******************************************************************

    public function testOnlyTrashed(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = $this->crearPublicacionBorrable((int) $usuario->id, 'solo-trashed');

            $this->assertTrue($publicacion->delete());

            $borradas = PublicacionBorrable::onlyTrashed()->where('id', $publicacion->id)->get();
            $this->assertNotNull($borradas);
            $this->assertSame(1, $borradas->count());
            $this->assertTrue($borradas->first()->trashed());

            //la consulta normal no la ve
            $this->assertNull(PublicacionBorrable::where('id', $publicacion->id)->first());
        });
    }

    public function testWithTrashed(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $this->crearPublicacionBorrable((int) $usuario->id, 'con-trashed-viva');
            $borrada = $this->crearPublicacionBorrable((int) $usuario->id, 'con-trashed-borrada');

            $this->assertTrue($borrada->delete());

            $todas = PublicacionBorrable::withTrashed()
                ->whereIn('slug', ['con-trashed-viva', 'con-trashed-borrada'])
                ->get();

            $this->assertNotNull($todas);
            $this->assertSame(2, $todas->count());

            $slugs = $todas->pluck('slug');
            $this->assertContains('con-trashed-viva', $slugs);
            $this->assertContains('con-trashed-borrada', $slugs);
        });
    }

    public function testRestorePorQueryBuilder(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = $this->crearPublicacionBorrable((int) $usuario->id, 'restore-builder');

            $this->assertTrue($publicacion->delete());

            $restauradas = PublicacionBorrable::onlyTrashed()->where('id', $publicacion->id)->restore();
            $this->assertSame(1, $restauradas);

            $this->assertNotNull(PublicacionBorrable::find($publicacion->id));
        });
    }

    public function testWithTrashedDeleteBorraFisicamente(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = $this->crearPublicacion((int) $usuario->id, 'delete-fisico');

            $eliminadas = PublicacionBorrable::withTrashed()->where('id', $publicacion->id)->delete();
            $this->assertSame(1, $eliminadas);

            $resto = PublicacionBorrable::customQuery(
                'SELECT COUNT(*) AS total FROM publicaciones WHERE id = ?',
                [$publicacion->id]
            );
            $this->assertSame(0, (int) ((array) $resto[0])['total']);
        });
    }

    public function testWithTrashedEnModeloSinSoftDeletesLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no usa el trait SoftDeletes');

        Usuario::withTrashed()->get();
    }

    //******************************************************************
    // PASO 4: AGREGADOS, VALUE, EXISTS, LATEST
    //******************************************************************

    public function testSumAvgMinMaxPorColumna(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $a = $this->crearPublicacion((int) $usuario->id, 'agg-a');
            $b = $this->crearPublicacion((int) $usuario->id, 'agg-b');

            $this->fijarVistas((int) $a->id, 10);
            $this->fijarVistas((int) $b->id, 25);

            $query = ['agg-a', 'agg-b'];

            $suma = Publicacion::whereIn('slug', $query)->sum('vistas');
            $this->assertEquals(35, $suma);

            $promedio = Publicacion::whereIn('slug', $query)->avg('vistas');
            $this->assertEqualsWithDelta(17.5, (float) $promedio, 0.001);

            $maximo = Publicacion::whereIn('slug', $query)->max('vistas');
            $this->assertSame(25, (int) $maximo);

            $minimo = Publicacion::whereIn('slug', $query)->min('vistas');
            $this->assertSame(10, (int) $minimo);

            //el estilo legacy con select() sigue funcionando
            $sumaLegacy = Publicacion::select('vistas')->whereIn('slug', $query)->sum();
            $this->assertEquals(35, $sumaLegacy);
        });
    }

    public function testValue(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $this->crearPublicacion((int) $usuario->id, 'value-test');

            $titulo = Publicacion::where('slug', 'value-test')->value('titulo');
            $this->assertSame('Titulo value-test', $titulo);

            $nada = Publicacion::where('slug', 'no-existe')->value('titulo');
            $this->assertNull($nada);
        });
    }

    public function testExistsYDoesntExist(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $this->crearPublicacion((int) $usuario->id, 'exists-test');

            $this->assertTrue(Publicacion::where('slug', 'exists-test')->exists());
            $this->assertFalse(Publicacion::where('slug', 'exists-test')->doesntExist());

            $this->assertFalse(Publicacion::where('slug', 'no-existe-nunca')->exists());
            $this->assertTrue(Publicacion::where('slug', 'no-existe-nunca')->doesntExist());
        });
    }

    public function testLatestYOledest(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $vieja = $this->crearPublicacion((int) $usuario->id, 'orden-vieja');
            $nueva = $this->crearPublicacion((int) $usuario->id, 'orden-nueva');

            Model::db()->statementC_U_D(
                'UPDATE publicaciones SET created_at = ? WHERE id IN (?, ?)',
                [null, $vieja->id, $nueva->id]
            );
            Model::db()->statementC_U_D(
                'UPDATE publicaciones SET created_at = ? WHERE id = ?',
                ['2026-01-01 10:00:00', $vieja->id]
            );
            Model::db()->statementC_U_D(
                'UPDATE publicaciones SET created_at = ? WHERE id = ?',
                ['2026-02-01 10:00:00', $nueva->id]
            );

            $primera = Publicacion::whereIn('slug', ['orden-vieja', 'orden-nueva'])->latest()->first();
            $this->assertNotNull($primera);
            $this->assertSame('orden-nueva', $primera->slug);

            $primeraVieja = Publicacion::whereIn('slug', ['orden-vieja', 'orden-nueva'])->oldest()->first();
            $this->assertNotNull($primeraVieja);
            $this->assertSame('orden-vieja', $primeraVieja->slug);
        });
    }

    //******************************************************************
    // PASO 3: CASTS RICOS
    //******************************************************************

    public function testCastsDatetimeDecimalYJson(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = $this->crearPublicacion(
                (int) $usuario->id,
                'casts-ricos',
                '{"a":1,"b":[2,3]}'
            );
            $this->fijarVistas((int) $publicacion->id, 5);

            $hidratada = PublicacionConCastsRicos::find($publicacion->id);
            $this->assertNotNull($hidratada);

            $this->assertInstanceOf(\DateTimeImmutable::class, $hidratada->created_at);
            $this->assertSame('5.00', $hidratada->vistas);
            $this->assertSame(['a' => 1, 'b' => [2, 3]], $hidratada->contenido);
        });
    }

    public function testCastsBidireccionalesEscrituraYSerializacion(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            // 1. Create asignando array a campo con cast 'array'
            $publicacion = PublicacionConCastsRicos::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Post Cast Array',
                'slug' => 'post-cast-array-' . uniqid(),
                'contenido' => ['config' => ['tema' => 'oscuro', 'activo' => true]],
            ]);
            $this->assertNotNull($publicacion);
            $this->assertIsArray($publicacion->contenido);
            $this->assertSame('oscuro', $publicacion->contenido['config']['tema']);

            // Verificar en BD que se guardo como JSON string
            $raw = Model::db()->statement(
                'SELECT contenido FROM publicaciones WHERE id = ?',
                [$publicacion->id]
            );
            $this->assertSame('{"config":{"tema":"oscuro","activo":true}}', ((array) $raw[0])['contenido']);

            // 2. Modificacion via propiedad y save()
            $publicacion->contenido = ['config' => ['tema' => 'claro', 'activo' => false]];
            $this->assertTrue($publicacion->save());

            $recargado = PublicacionConCastsRicos::find($publicacion->id);
            $this->assertSame(['config' => ['tema' => 'claro', 'activo' => false]], $recargado->contenido);

            // 3. Modificacion via update()
            $this->assertTrue($recargado->update([
                'contenido' => ['foo' => 'bar'],
            ]));
            $recargado2 = PublicacionConCastsRicos::find($publicacion->id);
            $this->assertSame(['foo' => 'bar'], $recargado2->contenido);

            // 4. toArray() formatea created_at (DateTimeInterface) a string Y-m-d H:i:s
            $array = $recargado2->toArray();
            $this->assertIsString($array['created_at']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $array['created_at']);
            $this->assertSame(['foo' => 'bar'], $array['contenido']);
        });
    }

    //******************************************************************
    // PASO 5: TIMESTAMPS CONFIGURABLES
    //******************************************************************

    public function testTimestampsDesactivados(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = PublicacionSinTimestamps::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Sin Timestamps',
                'slug' => 'sin-timestamps',
                'contenido' => 'contenido',
            ]);

            $this->assertNotNull($publicacion);

            $enBd = PublicacionSinTimestamps::find($publicacion->id);
            $this->assertNotNull($enBd);
            $this->assertNull($enBd->created_at);
            $this->assertNull($enBd->updated_at);
        });
    }

    public function testConstantesDeTimestamps(): void
    {
        $this->assertSame('created_at', Model::CREATED_AT);
        $this->assertSame('updated_at', Model::UPDATED_AT);
        $this->assertSame('eliminado_en', PublicacionBorrable::DELETED_AT);
    }

    //******************************************************************
    // PASO 6: MODEL COLLECTION COMPLETA
    //******************************************************************

    public function testColeccionSumAvgMinMaxGroupBySortByContainsFirstEach(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $a = $this->crearPublicacion((int) $usuario->id, 'col-alfa');
            $b = $this->crearPublicacion((int) $usuario->id, 'col-beta');
            $c = $this->crearPublicacion((int) $usuario->id, 'col-gamma');

            $this->fijarVistas((int) $a->id, 10);
            $this->fijarVistas((int) $b->id, 20);
            $this->fijarVistas((int) $c->id, 60);

            $coleccion = Publicacion::whereIn('slug', ['col-alfa', 'col-beta', 'col-gamma'])->get();
            $this->assertNotNull($coleccion);
            $this->assertSame(3, $coleccion->count());

            $this->assertEquals(90, $coleccion->sum('vistas'));
            $this->assertEqualsWithDelta(30.0, (float) $coleccion->avg('vistas'), 0.001);
            $this->assertSame(10, (int) $coleccion->min('vistas'));
            $this->assertSame(60, (int) $coleccion->max('vistas'));

            $ordenada = $coleccion->sortBy('titulo');
            $this->assertSame('col-alfa', $ordenada->first()->slug);
            $this->assertSame('col-gamma', $ordenada->last()->slug);

            $descendente = $coleccion->sortByDesc('vistas');
            $this->assertSame('col-gamma', $descendente->first()->slug);

            $grupos = $coleccion->groupBy('estado');
            $this->assertArrayHasKey('borrador', $grupos);
            $this->assertSame(3, $grupos['borrador']->count());

            $this->assertTrue($coleccion->contains('slug', 'col-beta'));
            $this->assertTrue($coleccion->contains(fn ($p) => (int) $p->vistas > 50));
            $this->assertFalse($coleccion->contains('slug', 'no-existe'));

            $encontrada = $coleccion->first(fn ($p) => $p->slug === 'col-beta');
            $this->assertNotNull($encontrada);
            $this->assertSame('col-beta', $encontrada->slug);

            $vistos = [];
            $coleccion->each(function ($p) use (&$vistos) {
                $vistos[] = $p->slug;
            });
            $this->assertSame(['col-alfa', 'col-beta', 'col-gamma'], $vistos);

            $reindexada = $coleccion->filter(fn ($p) => $p->slug !== 'col-alfa')->values();
            $this->assertSame(2, $reindexada->count());
            $this->assertSame('col-beta', $reindexada->first()->slug);
        });
    }

    //******************************************************************
    // PASO 7: PREVENT LAZY LOADING
    //******************************************************************

    public function testPreventLazyLoadingBloqueaElAccesoPerezoso(): void
    {
        Model::preventLazyLoading();

        try {
            $this->rollbackAfter(function () {
                $usuario = $this->crearUsuario();
                $this->crearPublicacion((int) $usuario->id, 'lazy-block');

                $hidratada = Publicacion::where('slug', 'lazy-block')->first();
                $this->assertNotNull($hidratada);

                try {
                    $hidratada->usuario;
                    $this->fail('Se esperaba un Error de carga perezosa (N+1)');
                } catch (\Error $e) {
                    $this->assertStringContainsString('Carga perezosa (N+1) bloqueada', $e->getMessage());
                    $this->assertStringContainsString('preventLazyLoading(false)', $e->getMessage());
                }

                //eager loading sigue funcionando con la prevencion activa
                $conEager = Publicacion::with('usuario')->where('slug', 'lazy-block')->first();
                $this->assertNotNull($conEager);
                $this->assertSame($usuario->correo, $conEager->usuario->correo);
            });
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    //******************************************************************
    // PASO 8: TOARRAY NO DESTRUCTIVO
    //******************************************************************

    public function testToArrayNoEliminaLosAtributosDeLaInstancia(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $fresco = Usuario::find($usuario->id);
            $this->assertNotNull($fresco);

            $primeraVez = $fresco->toArray();
            $segundaVez = $fresco->toArray();

            //los campos hidden no aparecen en ninguna serializacion
            $this->assertArrayNotHasKey('contrasena', $primeraVez);
            $this->assertArrayNotHasKey('contrasena', $segundaVez);
            $this->assertSame($primeraVez, $segundaVez);

            //PERO el modelo conserva internamente sus valores
            $this->assertSame($usuario->contrasena, $fresco->contrasena);
            $this->assertNull($fresco->token_recordar); //oculto pero presente internamente

            //y el modelo sigue siendo utilizable despues de serializar
            $this->assertTrue($fresco->update(['nombre' => 'Post-Serializacion']));
        });
    }

    //******************************************************************
    // PASO 9: SCOPES LOCALES EN MODELOS (ESTILO ELOQUENT)
    //******************************************************************

    public function testScopesLocalesEncadenables(): void
    {
        $this->rollbackAfter(function () {
            $usuario1 = $this->crearUsuario();
            $usuario2 = $this->crearUsuario();

            // Publicaciones para usuario 1
            $p1 = PublicacionConScopes::create([
                'usuario_id' => $usuario1->id,
                'titulo' => 'P1 Publicada Popular',
                'slug' => 'p1-' . uniqid(),
                'contenido' => 'contenido 1',
            ]);
            $this->fijarVistas((int) $p1->id, 150);
            Model::db()->statementC_U_D('UPDATE publicaciones SET estado = ? WHERE id = ?', ['publicado', $p1->id]);

            $p2 = PublicacionConScopes::create([
                'usuario_id' => $usuario1->id,
                'titulo' => 'P2 Borrador Popular',
                'slug' => 'p2-' . uniqid(),
                'contenido' => 'contenido 2',
            ]);
            $this->fijarVistas((int) $p2->id, 200);
            Model::db()->statementC_U_D('UPDATE publicaciones SET estado = ? WHERE id = ?', ['borrador', $p2->id]);

            $p3 = PublicacionConScopes::create([
                'usuario_id' => $usuario1->id,
                'titulo' => 'P3 Publicada Pocas Vistas',
                'slug' => 'p3-' . uniqid(),
                'contenido' => 'contenido 3',
            ]);
            $this->fijarVistas((int) $p3->id, 20);
            Model::db()->statementC_U_D('UPDATE publicaciones SET estado = ? WHERE id = ?', ['publicado', $p3->id]);

            // Publicacion para usuario 2
            $p4 = PublicacionConScopes::create([
                'usuario_id' => $usuario2->id,
                'titulo' => 'P4 Usuario2 Publicada Popular',
                'slug' => 'p4-' . uniqid(),
                'contenido' => 'contenido 4',
            ]);
            $this->fijarVistas((int) $p4->id, 300);
            Model::db()->statementC_U_D('UPDATE publicaciones SET estado = ? WHERE id = ?', ['publicado', $p4->id]);

            // 1. Invocar scope estático inicial: PublicacionConScopes::publicadas()->get()
            $publicadas = PublicacionConScopes::publicadas()->get();
            $this->assertNotNull($publicadas);
            foreach ($publicadas as $pub) {
                $this->assertSame('publicado', $pub->estado);
            }
            $this->assertTrue($publicadas->contains('slug', $p1->slug));
            $this->assertFalse($publicadas->contains('slug', $p2->slug));
            $this->assertTrue($publicadas->contains('slug', $p3->slug));
            $this->assertTrue($publicadas->contains('slug', $p4->slug));

            // 2. Encadenar múltiples scopes: publicadas()->populares(100)
            $publicadasPopulares = PublicacionConScopes::publicadas()->populares(100)->get();
            $this->assertNotNull($publicadasPopulares);
            $this->assertTrue($publicadasPopulares->contains('slug', $p1->slug));
            $this->assertFalse($publicadasPopulares->contains('slug', $p2->slug));
            $this->assertFalse($publicadasPopulares->contains('slug', $p3->slug));
            $this->assertTrue($publicadasPopulares->contains('slug', $p4->slug));

            // 3. Encadenar scope con parámetros y filtrar por usuario: delUsuario($usuario1->id)
            $filtradasUsuario = PublicacionConScopes::delUsuario((int) $usuario1->id)->publicadas()->populares(100)->get();
            $this->assertNotNull($filtradasUsuario);
            $this->assertSame(1, $filtradasUsuario->count());
            $this->assertSame($p1->slug, $filtradasUsuario->first()->slug);

            // 4. Encadenar scope tras métodos del QueryBuilder estándar (ej: whereIn, orderBy)
            $resultado = PublicacionConScopes::whereIn('slug', [$p1->slug, $p2->slug, $p3->slug, $p4->slug])
                ->publicadas()
                ->populares(50)
                ->orderBy('vistas', 'DESC')
                ->get();
            $this->assertNotNull($resultado);
            $this->assertSame($p4->slug, $resultado->first()->slug);
        });
    }

    public function testScopeInexistenteLanzaBadMethodCallException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('El metodo [scopeInexistente] no existe');

        PublicacionConScopes::scopeInexistente()->get();
    }
}
