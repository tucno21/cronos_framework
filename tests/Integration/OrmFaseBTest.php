<?php

namespace Tests\Integration;

use App\Models\Publicacion;
use App\Models\Usuario;
use Cronos\Model\Model;
use Tests\Integration\Fixtures\PublicacionConAccesorios;
use Tests\Integration\Fixtures\PublicacionConEventos;
use Tests\Integration\Fixtures\PublicacionObserver;
use Tests\TestCase\OrmTestCase;

/**
 * Tests de la Fase B del ORM:
 * - firstOrNew / firstOrCreate / updateOrCreate
 * - Eventos de ciclo de vida (creating/created/...) + observers + halt con false
 * - Paginacion (paginate)
 * - Accessors, mutators y $appends
 */
class OrmFaseBTest extends OrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //los listeners de eventos son estaticos y persisten entre tests:
        //se limpian para que cada test arranque con el estado de booted()
        PublicacionConEventos::flushEventListeners();
        PublicacionConEventos::reset();
        PublicacionObserver::reset();
    }

    private function datosUsuario(string $sufijo): array
    {
        return [
            'nombre' => 'Usuario FaseB ' . $sufijo,
            'correo' => 'faseb-' . $sufijo . '-' . uniqid() . '@test.com',
            'contrasena' => password_hash('clave123', PASSWORD_BCRYPT),
            'rol' => 'usuario',
            'avatar' => null,
            'correo_verificado_en' => null,
            'token_recordar' => null,
        ];
    }

    //******************************************************************
    // B.1: FIRST OR NEW / FIRST OR CREATE / UPDATE OR CREATE
    //******************************************************************

    public function testFirstOrCreateCreaSiNoExiste(): void
    {
        $this->rollbackAfter(function () {
            $datos = $this->datosUsuario('crea');
            $datos['correo_verificado_en'] = null;

            $usuario = Usuario::firstOrCreate(
                ['correo' => $datos['correo']],
                $datos
            );

            $this->assertNotNull($usuario->id);

            $enBd = Usuario::where('correo', $datos['correo'])->first();
            $this->assertNotNull($enBd);
            $this->assertSame($datos['nombre'], $enBd->nombre);
        });
    }

    public function testFirstOrCreateEncuentraElExistente(): void
    {
        $this->rollbackAfter(function () {
            $datos = $this->datosUsuario('existe');
            $original = Usuario::create($datos);
            $this->assertNotNull($original);

            $encontrado = Usuario::firstOrCreate(
                ['correo' => $datos['correo']],
                ['nombre' => 'Nombre Distinto']
            );

            $this->assertSame((int) $original->id, (int) $encontrado->id);
            $this->assertSame($datos['nombre'], $encontrado->nombre); //no lo toco
        });
    }

    public function testFirstOrNewNoGuarda(): void
    {
        $this->rollbackAfter(function () {
            $datos = $this->datosUsuario('ornew');

            $nuevo = Usuario::firstOrNew(['correo' => $datos['correo']], $datos);

            $this->assertNull($nuevo->id);

            $total = Usuario::where('correo', $datos['correo'])->count();
            $this->assertSame(0, $total);
        });
    }

    public function testFirstOrNewRetornaElExistente(): void
    {
        $this->rollbackAfter(function () {
            $datos = $this->datosUsuario('ornew2');
            $original = Usuario::create($datos);

            $nuevo = Usuario::firstOrNew(['correo' => $datos['correo']], $datos);

            $this->assertSame((int) $original->id, (int) $nuevo->id);
        });
    }

    public function testUpdateOrCreateActualizaElExistente(): void
    {
        $this->rollbackAfter(function () {
            $datos = $this->datosUsuario('update');
            $original = Usuario::create($datos);

            $resultado = Usuario::updateOrCreate(
                ['correo' => $datos['correo']],
                ['nombre' => 'Nombre Actualizado']
            );

            $this->assertSame((int) $original->id, (int) $resultado->id);

            $enBd = Usuario::find($original->id);
            $this->assertNotNull($enBd);
            $this->assertSame('Nombre Actualizado', $enBd->nombre);
        });
    }

    public function testUpdateOrCreateCreaSiNoExiste(): void
    {
        $this->rollbackAfter(function () {
            $datos = $this->datosUsuario('update-crea');

            $resultado = Usuario::updateOrCreate(
                ['correo' => $datos['correo']],
                $datos
            );

            $this->assertNotNull($resultado->id);

            $enBd = Usuario::find($resultado->id);
            $this->assertNotNull($enBd);
            $this->assertSame($datos['nombre'], $enBd->nombre);
        });
    }

    //******************************************************************
    // B.2: EVENTOS DE MODELO Y OBSERVERS
    //******************************************************************

    public function testEventosDeCreacionEnOrden(): void
    {
        $this->rollbackAfter(function () {
            PublicacionConEventos::reset();
            $usuario = $this->crearUsuario();

            $slug = 'eventos-create-' . uniqid();
            PublicacionConEventos::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Con Eventos',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            //orden Laravel: saving -> creating -> created -> saved
            $this->assertSame(
                ["saving:{$slug}", "creating:{$slug}", "created:{$slug}", "saved:{$slug}"],
                PublicacionConEventos::$registro
            );
        });
    }

    public function testEventosDeActualizacionYBorrado(): void
    {
        $this->rollbackAfter(function () {
            PublicacionConEventos::reset();
            $usuario = $this->crearUsuario();

            $slug = 'eventos-update-' . uniqid();
            $publicacion = PublicacionConEventos::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Update Eventos',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            PublicacionConEventos::$registro = [];

            $publicacion->update(['titulo' => 'Actualizada']);

            $this->assertSame(
                ["saving:{$slug}", "updating:{$slug}", "updated:{$slug}", "saved:{$slug}"],
                PublicacionConEventos::$registro
            );

            PublicacionConEventos::$registro = [];

            $this->assertTrue($publicacion->delete());

            $this->assertSame(
                ["deleting:{$slug}", "deleted:{$slug}"],
                PublicacionConEventos::$registro
            );
        });
    }

    public function testEventoRetrievedAlHidratar(): void
    {
        $this->rollbackAfter(function () {
            PublicacionConEventos::reset();
            $usuario = $this->crearUsuario();

            $slug = 'eventos-retrieved-' . uniqid();
            PublicacionConEventos::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Retrieved',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            PublicacionConEventos::$registro = [];

            PublicacionConEventos::where('slug', $slug)->firstOrFail();

            $this->assertSame(["retrieved:{$slug}"], PublicacionConEventos::$registro);
        });
    }

    public function testSavingQueRetornaFalseHaltetLaCreacion(): void
    {
        $this->rollbackAfter(function () {
            PublicacionConEventos::reset();
            PublicacionConEventos::saving(fn () => false);

            $usuario = $this->crearUsuario();

            $slug = 'eventos-halt-' . uniqid();
            $resultado = PublicacionConEventos::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'No Debe Crearse',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            $this->assertNull($resultado);

            $total = PublicacionConEventos::where('slug', $slug)->count();
            $this->assertSame(0, $total);
        });
    }

    public function testObserverRecibeLosEventos(): void
    {
        $this->rollbackAfter(function () {
            PublicacionConEventos::reset();
            PublicacionObserver::reset();
            PublicacionConEventos::observe(PublicacionObserver::class);

            $usuario = $this->crearUsuario();

            $slug = 'eventos-observer-' . uniqid();
            $publicacion = PublicacionConEventos::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Con Observer',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            $this->assertSame(["observer-created:{$slug}"], PublicacionObserver::$registro);

            PublicacionObserver::$registro = [];
            $publicacion->update(['titulo' => 'Obs Actualizada']);
            $this->assertSame(["observer-updated:{$slug}"], PublicacionObserver::$registro);

            PublicacionObserver::$registro = [];
            $publicacion->delete();
            $this->assertSame(["observer-deleted:{$slug}"], PublicacionObserver::$registro);
        });
    }

    //******************************************************************
    // B.3: PAGINACION
    //******************************************************************

    private function crearNUsuarios(int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            Usuario::create($this->datosUsuario('pag' . $i . '-' . uniqid()));
        }
    }

    public function testPaginateBasico(): void
    {
        $this->rollbackAfter(function () {
            $this->crearNUsuarios(5);

            $marcador = 'pag-marker-' . uniqid();
            $pagina = Usuario::where('nombre', 'LIKE', 'Usuario FaseB pag%')->paginate(2, 1);

            $this->assertSame(5, $pagina->total);
            $this->assertSame(2, $pagina->count());
            $this->assertSame(3, $pagina->ultimaPagina);
            $this->assertSame(1, $pagina->paginaActual);
            $this->assertSame(1, $pagina->desde());
            $this->assertSame(2, $pagina->hasta());

            //pagina 3: solo 1 item
            $ultima = Usuario::where('nombre', 'LIKE', 'Usuario FaseB pag%')->paginate(2, 3);
            $this->assertSame(1, $ultima->count());
            $this->assertSame(5, $ultima->hasta());
        });
    }

    public function testPaginatePaginaFueraDeRangoQuedaVacia(): void
    {
        $this->rollbackAfter(function () {
            $this->crearNUsuarios(2);

            $pagina = Usuario::where('nombre', 'LIKE', 'Usuario FaseB pag%')->paginate(2, 10);

            $this->assertSame(2, $pagina->total);
            $this->assertSame(0, $pagina->count());
            $this->assertNull($pagina->desde());
            $this->assertNull($pagina->hasta());
        });
    }

    public function testPaginateSinResultados(): void
    {
        $this->rollbackAfter(function () {
            $pagina = Usuario::where('correo', 'nadie-con-este-correo@test.com')->paginate(15);

            $this->assertSame(0, $pagina->total);
            $this->assertSame(1, $pagina->ultimaPagina);
            $this->assertNull($pagina->desde());
        });
    }

    public function testPaginateIncluyeEagerYCount(): void
    {
        $this->rollbackAfter(function () {
            $this->crearNUsuarios(3);
            $usuario = $this->crearUsuario(['nombre' => 'Pag Con Relacion']);
            $publicacion = Publicacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Pag Eager',
                'slug' => 'pag-eager-' . uniqid(),
                'contenido' => 'contenido',
            ]);

            $pagina = Publicacion::with('usuario')
                ->withCount('comentarios')
                ->where('slug', $publicacion->slug)
                ->paginate(10);

            $this->assertSame(1, $pagina->total);

            $primera = $pagina->items->first();
            $this->assertTrue($primera->relationLoaded('usuario'));
            $this->assertSame(0, $primera->comentarios_count);

            //toArray con la meta completa
            $array = $pagina->toArray();
            $this->assertArrayHasKey('data', $array);
            $this->assertArrayHasKey('total', $array);
            $this->assertArrayHasKey('por_pagina', $array);
            $this->assertArrayHasKey('ultima_pagina', $array);
        });
    }

    public function testPaginatePorPaginaInvalidoLanzaError(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('mayor a 0');

        Usuario::paginate(0);
    }

    //******************************************************************
    // B.4: ACCESSORS, MUTATORS Y APPENDS
    //******************************************************************

    public function testAccessorTransformaLaLectura(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $slug = 'accesor-' . uniqid();

            $publicacion = PublicacionConAccesorios::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Titulo Normal',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            //la columna en BD refleja el mutator (minusculas al crear)
            $this->assertSame('titulo normal', $publicacion->titulo);

            //el accessor calcula en lectura
            $this->assertSame('TITULO NORMAL', $publicacion->titulo_mayuscula);
        });
    }

    public function testAppendsApareceEnToArray(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $slug = 'appends-' . uniqid();

            $publicacion = PublicacionConAccesorios::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Con Appends',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            $array = $publicacion->toArray();

            $this->assertArrayHasKey('titulo_mayuscula', $array);
            $this->assertSame('CON APPENDS', $array['titulo_mayuscula']);
            $this->assertSame('con appends', $array['titulo']); //mutator al crear
        });
    }

    public function testMutatorTransformaLaEscritura(): void
    {
        $this->rollbackAfter(function () {
            $usuario = $this->crearUsuario();
            $slug = 'mutator-' . uniqid();

            //el mutator de titulo fuerza minusculas y quita espacios
            $publicacion = PublicacionConAccesorios::create([
                'usuario_id' => $usuario->id,
                'titulo' => '  TITULO CON MAYUSCULAS  ',
                'slug' => $slug,
                'contenido' => 'contenido',
            ]);

            $this->assertSame('titulo con mayusculas', $publicacion->titulo);

            //tambien via set directo + save
            $fresca = PublicacionConAccesorios::findOrFail($publicacion->id);
            $fresca->titulo = '  OTRO TITULO  ';
            $this->assertTrue($fresca->save());

            $enBd = PublicacionConAccesorios::findOrFail($publicacion->id);
            $this->assertSame('otro titulo', $enBd->titulo);
        });
    }

    public function testAccessorSinColumnaRetornaNull(): void
    {
        $this->rollbackAfter(function () {
            //modelo base sin accessor para titulo_mayuscula: null como siempre
            $usuario = $this->crearUsuario();
            $publicacion = $usuario->publicaciones()->create([
                'titulo' => 'Normal',
                'slug' => 'sin-accesor-' . uniqid(),
                'contenido' => 'contenido',
            ]);

            $this->assertNull($publicacion->titulo_mayuscula);
        });
    }
}
