<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\Compiler\BladeCompiler;
use Cronos\View\SectionManager;
use Tests\TestCase\CronosTestCase;

/**
 * entorno de prueba que resuelve makeView() con un registro de plantillas
 * del propio test, emulando lo que hara el motor completo en la fase 6.
 */
class SectionTestEnvironment extends SectionManager
{
    public ?\Closure $renderer = null;

    public function makeView(string $view, array $vars): string
    {
        if ($this->renderer === null) {
            throw new \LogicException('renderer no configurado');
        }

        return ($this->renderer)($view, $vars);
    }
}

class BladeSectionsTest extends CronosTestCase
{
    private string $cacheDir;

    private SectionTestEnvironment $env;

    /** @var array<string, string> registro de plantillas por nombre */
    private array $templates = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/cronos_sections_test_' . uniqid();

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            unlink($file->getRealPath());
        }

        rmdir($this->cacheDir);

        parent::tearDown();
    }

    private function render(string $template, array $data = []): string
    {
        $this->env = new SectionTestEnvironment();
        $templates = $this->templates;
        $env = $this->env;

        $env->renderer = function (string $view, array $vars) use ($env, $templates): string {
            if (!isset($templates[$view])) {
                throw new \RuntimeException("El layout [{$view}] no existe");
            }

            return $this->executeCompiled($env, $templates[$view], $vars);
        };

        return $this->executeCompiled($env, $template, $data);
    }

    private function executeCompiled(SectionTestEnvironment $env, string $template, array $data): string
    {
        $file = $this->cacheDir . '/view_' . uniqid('', true) . '.php';
        file_put_contents($file, (new BladeCompiler())->compileString($template));

        ob_start();

        try {
            //los nombres con prefijo __ evitan que extract() pise el path del include
            (static function (string $__viewPath, array $__viewData, SectionTestEnvironment $__viewEnv): void {
                extract($__viewData, EXTR_OVERWRITE);
                $__env = $__viewEnv;
                include $__viewPath;
            })($file, $data, $env);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_get_clean();

            throw $e;
        }
    }

    // ── @yield ────────────────────────────────────────────

    public function test_yield_sin_seccion_usa_default(): void
    {
        $this->assertSame('Fallback', $this->render("@yield('title', 'Fallback')"));
    }

    public function test_yield_sin_seccion_ni_default_vuelve_vacio(): void
    {
        $this->assertSame('', $this->render("@yield('title')"));
    }

    // ── @extends basico ───────────────────────────────────

    public function test_extends_mezcla_layout_con_secciones(): void
    {
        $this->templates['layouts.app'] = '<title>@yield(\'title\', \'App\')</title><body>@yield(\'content\')</body>';

        $html = $this->render(
            '@extends(\'layouts.app\')@section(\'title\', \'Mi Titulo\')@section(\'content\')HOLA{{ $x }}@endsection',
            ['x' => '!']
        );

        $this->assertSame('<title>Mi Titulo</title><body>HOLA!</body>', $html);
    }

    public function test_herencia_multinivel_nieto_media_abuela(): void
    {
        $this->templates['layouts.abuela'] = 'A[@yield(\'title\')|@yield(\'body\')]';
        $this->templates['layouts.media'] = "@extends('layouts.abuela')@section('title', 'T-MEDIA')";

        $html = $this->render("@extends('layouts.media')@section('body')B-NIETO@endsection");

        $this->assertSame('A[T-MEDIA|B-NIETO]', $html);
    }

    public function test_extends_layout_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no existe');

        $this->render("@extends('layouts.nada')");
    }

    // ── @section corto ────────────────────────────────────

    public function test_section_corto_en_mismo_archivo(): void
    {
        $html = $this->render("@section('title', 'Hola')@yield('title')");

        $this->assertSame('Hola', $html);
    }

    public function test_section_corto_con_expresion(): void
    {
        $html = $this->render("@section('title', \$p . ' App')@yield('title')", ['p' => 'Cronos']);

        $this->assertSame('Cronos App', $html);
    }

    // ── @parent, @show y @overwrite ───────────────────────

    public function test_parent_anexa_contenido_a_seccion_del_layout(): void
    {
        $this->templates['layouts.base'] = "@section('sidebar')MENU@show<content>@yield('content')</content>";

        $html = $this->render(
            "@extends('layouts.base')@section('sidebar')@parent + EXTRAS@endsection"
        );

        $this->assertSame('MENU + EXTRAS<content></content>', $html);
    }

    public function test_show_imprime_y_conserva_la_seccion(): void
    {
        $html = $this->render("@section('t')VALOR@show y sigue @yield('t')");

        $this->assertSame('VALOR y sigue VALOR', $html);
    }

    public function test_overwrite_reemplaza_seccion_del_layout(): void
    {
        $this->templates['layouts.base'] = "@section('s')VIEJO@show";

        $html = $this->render(
            "@extends('layouts.base')@section('s')NUEVO@overwrite"
        );

        $this->assertSame('NUEVO', $html);
    }

    // ── @hasSection y @sectionMissing ────────────────────

    public function test_has_section_y_section_missing(): void
    {
        $this->templates['layouts.base'] = "@hasSection('x')SI@endif@sectionMissing('y')NO HAY Y@endif";

        $html = $this->render("@extends('layouts.base')@section('x')TIENE X@endsection");

        $this->assertSame('SINO HAY Y', $html);
    }

    public function test_has_section_falso_con_seccion_vacia(): void
    {
        $html = $this->render("@section('x', '')@hasSection('x')SI@else NO @endif");

        $this->assertSame(' NO ', $html);
    }

    // ── aislamiento y casos especiales ────────────────────

    public function test_secciones_no_filtran_entre_renders(): void
    {
        $primero = $this->render("@section('content')DE-A@endsection@yield('content')");
        $segundo = $this->render("@yield('content', 'VACIO')");

        $this->assertSame('DE-A', $primero);
        $this->assertSame('VACIO', $segundo);
    }

    public function test_seccion_anidada_con_pila(): void
    {
        $html = $this->render("@section('a')A1@section('b')B1@endsection A2@endsection@yield('a')/@yield('b')");

        $this->assertSame('A1 A2/B1', $html);
    }

    public function test_stop_es_alias_de_endsection(): void
    {
        //el contenido de la seccion se captura, no se imprime
        $html = $this->render("@section('t')CON STOP@stop>@yield('t')");

        $this->assertSame('>CON STOP', $html);
    }

    public function test_seccion_evalua_blade_y_variables_en_captura(): void
    {
        $this->templates['layouts.app'] = '<c>@yield(\'content\')</c>';

        $html = $this->render(
            "@extends('layouts.app')@section('content')@foreach(\$items as \$i){{ \$i }}-@endforeach@endsection",
            ['items' => [1, 2]]
        );

        $this->assertSame('<c>1-2-</c>', $html);
    }

    public function test_compilado_no_contiene_directivas_de_herencia(): void
    {
        $compilado = (new BladeCompiler())->compileString(
            "@extends('layouts.app')@section('content', 'x')@yield('content')@hasSection('y')a@endif"
        );

        $this->assertStringNotContainsString('@extends', $compilado);
        $this->assertStringNotContainsString('@section', $compilado);
        $this->assertStringNotContainsString('@yield', $compilado);
        $this->assertStringNotContainsString('@hasSection', $compilado);
    }
}
