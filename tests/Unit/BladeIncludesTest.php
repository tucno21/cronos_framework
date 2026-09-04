<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\Compiler\BladeCompiler;
use Cronos\View\SectionManager;
use Tests\TestCase\CronosTestCase;

/**
 * entorno de prueba para includes con registro de plantillas y
 * verificacion de existencia, emulando el motor completo.
 */
class IncludeTestEnvironment extends SectionManager
{
    public ?\Closure $renderer = null;

    /** @var array<string, string> */
    public array $views = [];

    public function viewExists(string $view): bool
    {
        return isset($this->views[$view]);
    }

    public function makeView(string $view, array $vars): string
    {
        if (!isset($this->views[$view])) {
            throw new \RuntimeException("La vista [{$view}] no existe");
        }

        return $this->renderTemplate($this->views[$view], $vars);
    }

    public function renderTemplate(string $template, array $vars): string
    {
        $file = sys_get_temp_dir() . '/cronos_include_view_' . uniqid('', true) . '.php';
        file_put_contents($file, (new BladeCompiler())->compileString($template));

        ob_start();

        try {
            (static function (string $__viewPath, array $__viewData, IncludeTestEnvironment $__viewEnv): void {
                extract($__viewData, EXTR_OVERWRITE);
                $__env = $__viewEnv;
                include $__viewPath;
            })($file, $vars, $this);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_get_clean();

            throw $e;
        } finally {
            @unlink($file);
        }
    }
}

class BladeIncludesTest extends CronosTestCase
{
    private IncludeTestEnvironment $env;

    protected function setUp(): void
    {
        parent::setUp();

        $this->env = new IncludeTestEnvironment();
    }

    private function render(string $template, array $data = []): string
    {
        return $this->env->renderTemplate($template, $data);
    }

    // ── @include ──────────────────────────────────────────

    public function test_include_basico(): void
    {
        $this->env->views['partials.saludo'] = 'HOLA-MUNDO';

        $this->assertSame('X HOLA-MUNDO Y', $this->render('X @include(\'partials.saludo\') Y'));
    }

    public function test_include_con_datos_que_pisan_scope(): void
    {
        $this->env->views['partials.dato'] = '[{{ $nombre }}]';

        $html = $this->render("@include('partials.dato', ['nombre' => 'PASADO'])", ['nombre' => 'SCOPE']);

        $this->assertSame('[PASADO]', $html);
    }

    public function test_include_hereda_variables_del_scope(): void
    {
        $this->env->views['partials.dato'] = '[{{ $nombre }}]';

        $html = $this->render("@include('partials.dato')", ['nombre' => 'DEL-SCOPE']);

        $this->assertSame('[DEL-SCOPE]', $html);
    }

    public function test_include_con_nombre_dinamico(): void
    {
        $this->env->views['partials.alfa'] = 'ALFA';
        $this->env->views['partials.beta'] = 'BETA';

        $html = $this->render("@include(\$parcial)", ['parcial' => 'partials.beta']);

        $this->assertSame('BETA', $html);
    }

    public function test_include_anidado(): void
    {
        $this->env->views['partials.externo'] = 'EXT[@include(\'partials.interno\')]';
        $this->env->views['partials.interno'] = 'INT';

        $this->assertSame('EXT[INT]', $this->render("@include('partials.externo')"));
    }

    public function test_include_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->render("@include('partials.nada')");
    }

    public function test_include_dentro_de_foreach(): void
    {
        $this->env->views['partials.item'] = '<i>{{ $i }}</i>';

        $html = $this->render(
            "@foreach(\$items as \$i)@include('partials.item')@endforeach",
            ['items' => [1, 2]]
        );

        $this->assertSame('<i>1</i><i>2</i>', $html);
    }

    // ── @includeIf / @includeWhen / @includeUnless ────────

    public function test_include_if_cuando_existe(): void
    {
        $this->env->views['partials.ok'] = 'EXISTE';

        $this->assertSame('EXISTE', $this->render("@includeIf('partials.ok')"));
    }

    public function test_include_if_cuando_no_existe_no_hace_nada(): void
    {
        $this->assertSame('SIN-MAS', $this->render("@includeIf('partials.nada')SIN-MAS"));
    }

    public function test_include_when_con_condicion_verdadera(): void
    {
        $this->env->views['partials.mensaje'] = 'MOSTRADO';

        $this->assertSame('MOSTRADO', $this->render("@includeWhen(\$activo, 'partials.mensaje')", ['activo' => true]));
    }

    public function test_include_when_con_condicion_falsa(): void
    {
        $this->env->views['partials.mensaje'] = 'MOSTRADO';

        $this->assertSame('', $this->render("@includeWhen(\$activo, 'partials.mensaje')", ['activo' => false]));
    }

    public function test_include_unless(): void
    {
        $this->env->views['partials.mensaje'] = 'AVISADO';

        $html = $this->render("@includeUnless(\$activo, 'partials.mensaje')", ['activo' => true]);
        $this->assertSame('', $html);

        $html = $this->render("@includeUnless(\$activo, 'partials.mensaje')", ['activo' => false]);
        $this->assertSame('AVISADO', $html);
    }

    // ── @each ─────────────────────────────────────────────

    public function test_each_renderiza_por_elemento(): void
    {
        $this->env->views['partials.item'] = '<li>{{ $job }}</li>';

        $html = $this->render(
            "<ul>@each('partials.item', \$jobs, 'job')</ul>",
            ['jobs' => ['dev', 'ops']]
        );

        $this->assertSame('<ul><li>dev</li><li>ops</li></ul>', $html);
    }

    public function test_each_con_vista_de_vacio(): void
    {
        $this->env->views['partials.item'] = '<li>{{ $job }}</li>';
        $this->env->views['partials.vacio'] = 'SIN TRABAJOS';

        $html = $this->render(
            "@each('partials.item', \$jobs, 'job', 'partials.vacio')",
            ['jobs' => []]
        );

        $this->assertSame('SIN TRABAJOS', $html);
    }

    public function test_each_sin_vista_de_vacio_y_coleccion_vacia(): void
    {
        $this->env->views['partials.item'] = '<li>{{ $job }}</li>';

        $html = $this->render(
            "@each('partials.item', \$jobs, 'job')",
            ['jobs' => []]
        );

        $this->assertSame('', $html);
    }

    public function test_each_expone_clave(): void
    {
        $this->env->views['partials.item'] = '{{ $key }}={{ $valor }};';

        $html = $this->render(
            "@each('partials.item', \$mapa, 'valor')",
            ['mapa' => ['a' => 1]]
        );

        $this->assertSame('a=1;', $html);
    }

    // ── integracion con herencia ──────────────────────────

    public function test_include_desde_layout_en_cadena_extends(): void
    {
        $this->env->views['partials.footer'] = 'PIE-DE-PAGINA';
        $this->env->views['layouts.base'] = "<main>@yield('content')</main>@include('partials.footer')";
        $this->env->views['layouts.media'] = "@extends('layouts.base')@section('content')CONTENIDO@endsection";

        $html = $this->render("@extends('layouts.media')");

        $this->assertSame('<main>CONTENIDO</main>PIE-DE-PAGINA', $html);
    }

    public function test_compilado_no_contiene_directivas_de_include(): void
    {
        $compilado = (new BladeCompiler())->compileString(
            "@include('a')@includeIf('b')@includeWhen(\$c, 'd')@includeUnless(\$e, 'f')@each('g', \$h, 'i')"
        );

        $this->assertStringNotContainsString('@include', $compilado);
        $this->assertStringNotContainsString('@each', $compilado);
    }
}
