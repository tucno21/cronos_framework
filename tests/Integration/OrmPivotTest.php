<?php

namespace Tests\Integration;

use App\Models\Etiqueta;
use App\Models\Publicacion;
use App\Models\Rol;
use App\Models\Usuario;
use Cronos\Model\Model;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de la Fase A.4 del ORM: escritura de pivotes en belongsToMany
 * con attach(), detach() y sync().
 */
class OrmPivotTest extends OrmTestCase
{
    private function idsDeRoles(Usuario $usuario): array
    {
        $filas = (array) Model::db()->statement(
            'SELECT rol_id FROM rol_usuario WHERE usuario_id = ? ORDER BY rol_id',
            [$usuario->id]
        );

        return array_map(fn ($f) => (int) ((array) $f)['rol_id'], $filas);
    }

    //******************************************************************
    // ATTACH
    //******************************************************************

    public function testAttachUnoYVarios(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $insertadas = $usuario->roles()->attach(Rol::first()->id);
            $this->assertSame(1, $insertadas);
            $this->assertSame([1], $this->idsDeRoles($usuario));

            $segundo = Rol::orderBy('id')->offset(1)->first();
            $tercero = Rol::orderBy('id')->offset(2)->first();

            $insertadas = $usuario->roles()->attach([$segundo->id, $tercero->id]);
            $this->assertSame(2, $insertadas);

            $roles = $this->idsDeRoles($usuario);
            $this->assertSame(3, count($roles));
            $this->assertContains((int) $segundo->id, $roles);
            $this->assertContains((int) $tercero->id, $roles);
        });
    }

    public function testAttachDuplicadoNoInsertaNiFalla(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $rolId = Rol::first()->id;

            $this->assertSame(1, $usuario->roles()->attach($rolId));
            $this->assertSame(0, $usuario->roles()->attach($rolId));

            $this->assertSame([1], $this->idsDeRoles($usuario));
        });
    }

    public function testAttachSinClavePrimariaLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no tiene clave primaria para escribir en el pivote');

        $usuario = new Usuario();
        $usuario->roles()->attach(1);
    }

    //******************************************************************
    // DETACH
    //******************************************************************

    public function testDetachUnoYVarios(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $usuario->roles()->attach([1, 2, 3]);

            $eliminadas = $usuario->roles()->detach(2);
            $this->assertSame(1, $eliminadas);
            $this->assertSame([1, 3], $this->idsDeRoles($usuario));

            $eliminadas = $usuario->roles()->detach([1, 3]);
            $this->assertSame(2, $eliminadas);
            $this->assertSame([], $this->idsDeRoles($usuario));
        });
    }

    public function testDetachSinArgumentosEliminaTodos(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $usuario->roles()->attach([1, 2, 3]);

            $eliminadas = $usuario->roles()->detach();
            $this->assertSame(3, $eliminadas);
            $this->assertSame([], $this->idsDeRoles($usuario));
        });
    }

    //******************************************************************
    // SYNC
    //******************************************************************

    public function testSyncAdjuntaYElimina(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $usuario->roles()->attach([1, 2]);

            $resultado = $usuario->roles()->sync([2, 3]);

            $this->assertSame([3], $resultado['attached']);
            $this->assertSame([1], $resultado['detached']);

            //el 2 permanecia, el 3 se agrego, el 1 se fue
            $this->assertSame([2, 3], $this->idsDeRoles($usuario));
        });
    }

    public function testSyncSinCambiosNoTocaElPivote(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $usuario->roles()->attach([1, 2]);

            $resultado = $usuario->roles()->sync([1, 2]);

            $this->assertSame([], $resultado['attached']);
            $this->assertSame([], $resultado['detached']);
            $this->assertSame([1, 2], $this->idsDeRoles($usuario));
        });
    }

    public function testSyncVacioEliminaTodo(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $usuario->roles()->attach([1, 2, 3]);

            $resultado = $usuario->roles()->sync([]);

            $this->assertSame([1, 2, 3], $resultado['detached']);
            $this->assertSame([], $this->idsDeRoles($usuario));
        });
    }

    //******************************************************************
    // OTRO PAR DE PIVOTE (publicacion_etiqueta)
    //******************************************************************

    public function testSyncEnPublicacionEtiquetas(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $publicacion = Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Con Etiquetas',
                'slug' => 'sync-etiquetas-' . uniqid(),
                'contenido' => 'contenido',
            ]);

            $etiqueta = Etiqueta::firstOrFail();
            $segunda = Etiqueta::orderBy('id')->offset(1)->firstOrFail();

            $resultado = $publicacion->etiquetas()->sync([$etiqueta->id, $segunda->id]);

            $this->assertSame([(int) $etiqueta->id, (int) $segunda->id], $resultado['attached']);

            //la relacion refleja el pivote
            $etiquetas = $publicacion->etiquetas()->get();
            $this->assertNotNull($etiquetas);
            $this->assertSame(2, $etiquetas->count());

            //sync a solo una: la otra se elimina
            $resultado = $publicacion->etiquetas()->sync([$etiqueta->id]);
            $this->assertSame([(int) $segunda->id], $resultado['detached']);
            $this->assertSame(1, $publicacion->etiquetas()->get()->count());
        });
    }
}
