<?php

namespace Tests\Integration;

use App\Models\Comentario;
use App\Models\Perfil;
use App\Models\Publicacion;
use App\Models\Usuario;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de la Fase A.5 del ORM: crear/guardar a traves de relaciones
 * (hasOne/hasMany) y associate()/dissociate() en belongsTo.
 */
class OrmRelationCreateTest extends OrmTestCase
{
    //******************************************************************
    // CREATE / SAVE VIA RELACION (hasOne / hasMany)
    //******************************************************************

    public function testCreateViaHasManyAsignaLaClaveForanea(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $slug = 'via-hasmany-' . uniqid();

            $publicacion = $usuario->publicaciones()->create([
                'titulo' => 'Creada Via Relacion',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            $this->assertNotNull($publicacion);
            $this->assertSame((int) $usuario->id, (int) $publicacion->usuario_id);

            $enBd = Publicacion::where('slug', $slug)->first();
            $this->assertNotNull($enBd);
            $this->assertSame((int) $usuario->id, (int) $enBd->usuario_id);
        });
    }

    public function testCreateViaHasOne(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $perfil = $usuario->perfil()->create([
                'biografia' => 'Biografia via relacion',
                'telefono' => '555-0000',
                'fecha_nacimiento' => '1995-01-15',
                'sitio_web' => null,
            ]);

            $this->assertNotNull($perfil);
            $this->assertSame((int) $usuario->id, (int) $perfil->usuario_id);

            $enBd = Perfil::where('usuario_id', $usuario->id)->first();
            $this->assertNotNull($enBd);
            $this->assertSame('Biografia via relacion', $enBd->biografia);
        });
    }

    public function testSaveViaHasMany(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $slug = 'via-save-' . uniqid();

            $publicacion = new Publicacion();
            $publicacion->titulo = 'Guardada Via Relacion';
            $publicacion->slug = $slug;
            $publicacion->contenido = 'contenido';

            $this->assertTrue($usuario->publicaciones()->save($publicacion));
            $this->assertSame((int) $usuario->id, (int) $publicacion->usuario_id);

            $enBd = Publicacion::where('slug', $slug)->first();
            $this->assertNotNull($enBd);
        });
    }

    public function testCreateViaRelacionSinClavePadreLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no tiene clave primaria');

        $usuario = new Usuario();
        $usuario->nombre = 'Sin Id';
        $usuario->publicaciones()->create(['titulo' => 'X', 'slug' => 'x', 'contenido' => 'x']);
    }

    //******************************************************************
    // ASSOCIATE / DISSOCIATE (belongsTo)
    //******************************************************************

    public function testAssociateConModeloYGuardado(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Para Asociar',
                'slug' => 'associate-' . uniqid(),
                'contenido' => 'contenido',
            ]);

            //encadenado: associate retorna el padre para ->save()
            $comentario = new Comentario();
            $comentario->contenido = 'Comentario asociado';

            $this->assertInstanceOf(Comentario::class, $comentario->publicacion()->associate($publicacion));
            $comentario->usuario()->associate($usuario);

            $this->assertTrue($comentario->save());

            $enBd = Comentario::where('contenido', 'Comentario asociado')->first();
            $this->assertNotNull($enBd);
            $this->assertSame((int) $publicacion->id, (int) $enBd->publicacion_id);
            $this->assertSame((int) $usuario->id, (int) $enBd->usuario_id);
        });
    }

    public function testAssociateConId(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $comentario = new Comentario();
            $comentario->contenido = 'Comentario por id';

            $comentario->usuario()->associate((int) $usuario->id);

            $this->assertSame((int) $usuario->id, (int) $comentario->usuario_id);
        });
    }

    public function testDissociateLimpiaLaClaveForaneaEnMemoria(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();

            $publicacion = Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Para Disociar',
                'slug' => 'dissociate-' . uniqid(),
                'contenido' => 'contenido',
            ]);

            $comentario = Comentario::create([
                'publicacion_id' => $publicacion->id,
                'usuario_id' => $usuario->id,
                'contenido' => 'Para disociar',
            ]);

            $this->assertSame((int) $publicacion->id, (int) $comentario->publicacion_id);

            //dissociate retorna el padre y limpia el atributo (en memoria;
            //la columna es NOT NULL en esta tabla, como ya viene de Eloquent
            //el responsable es el esquema)
            $this->assertInstanceOf(Comentario::class, $comentario->publicacion()->dissociate());
            $this->assertNull($comentario->publicacion_id);
        });
    }

    public function testAssociateConModeloSinClaveLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('no tiene clave primaria');

        $comentario = new Comentario();
        $comentario->contenido = 'X';

        $comentario->usuario()->associate(new Usuario());
    }
}
