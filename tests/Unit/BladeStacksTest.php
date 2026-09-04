<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\Compiler\BladeCompiler;
use Cronos\View\SectionManager;
use Tests\TestCase\CronosTestCase;

/**
 * entorno de prueba para stacks con render de layouts via registro de plantillas.
 */
class StackTestEnvironment extends SectionManager
{
    public ?\Closure $renderer = null;

    public function renderLayout(string $view, array $vars): void
    {
        if ($this->renderer === null) {
            throw new \LogicException('renderer no configurado');
        }

        echo ($this->renderer)($view, $vars);
    }
}

class BladeStacksTest extends CronosTestCase
{
    private string $cacheDir;

    private array $templates = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/cronos_stacks_test_' . uniqid();

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
        $env = new StackTestEnvironment();
        $templates = $this->templates;

        $env->renderer = function (string $view, array $vars) use ($env, $templates): string {
            if (!isset($templates[$view])) {
                throw new \RuntimeException("El layout [{$view}] no existe");
            }

            return $this->executeCompiled($env, $templates[$view], $vars);
        };

        return $this->executeCompiled($env, $template, $data);
    }

    private function executeCompiled(StackTestEnvironment $env, string $template, array $data): string
    {
        $file = $this->cacheDir . '/view_' . uniqid('', true) . '.php';
        file_put_contents($file, (new BladeCompiler())->compileString($template));

        ob_start();

        try {
            //los nombres con prefijo __ evitan que extract() pise el path del include
            (static function (string $__viewPath, array $__viewData, StackTestEnvironment $__viewEnv): void {
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

    // ── push y stack en el mismo template ─────────────────

    public function test_push_bloque_y_stack(): void
    {
        $html = $this->render("@push('scripts')<script>a.js</script>@endpush<head>@stack('scripts')</head>");

        $this->assertSame('<head><script>a.js</script></head>', $html);
    }

    public function test_push_corto(): void
    {
        $html = $this->render("@push('css', '<link>a.css</link>')<head>@stack('css')</head>");

        $this->assertSame('<head><link>a.css</link></head>', $html);
    }

    public function test_pushes_acumulan_en_orden(): void
    {
        $html = $this->render(
            "@push('s')UNO@endpush@push('s')DOS@endpush@push('s')TRES@endpush[ @stack('s') ]"
        );

        $this->assertSame('[ UNODOSTRES ]', $html);
    }

    public function test_stack_vacio_vuelve_vacio(): void
    {
        $this->assertSame('[]', $this->render("[@stack('nada')]"));
    }

    // ── prepend ───────────────────────────────────────────

    public function test_prepend_pon_el_contenido_al_frente(): void
    {
        $html = $this->render(
            "@push('s')SEGUNDO@endpush@prepend('s')PRIMERO@endprepend[ @stack('s') ]"
        );

        $this->assertSame('[ PRIMEROSEGUNDO ]', $html);
    }

    public function test_prepend_y_push_intercalados(): void
    {
        $html = $this->render(
            "@push('s')B@endpush@prepend('s')A@endprepend@push('s')C@endpush@stack('s')"
        );

        $this->assertSame('ABC', $html);
    }

    // ── layout + hija ─────────────────────────────────────

    public function test_push_en_hija_stack_en_layout(): void
    {
        $this->templates['layouts.app'] = '<html>@stack(\'scripts\')</html>';

        $html = $this->render(
            "@extends('layouts.app')@push('scripts')<script>app.js</script>@endpush"
        );

        $this->assertSame('<html><script>app.js</script></html>', $html);
    }

    public function test_pushes_de_hija_y_layout_se_combinan(): void
    {
        //la hija renderiza primero, por lo que sus pushes quedan antes que los del layout
        $this->templates['layouts.app'] = "@push('s')<base>@endpush<body>@stack('s')</body>";

        $html = $this->render(
            "@extends('layouts.app')@push('s')<app>@endpush"
        );

        $this->assertSame('<body><app><base></body>', $html);
    }

    // ── casos especiales ──────────────────────────────────

    public function test_push_dentro_de_loop_acumula_por_iteracion(): void
    {
        $html = $this->render(
            "@foreach(\$items as \$i)@push('s'){{ \$i }};@endpush@endforeach@stack('s')",
            ['items' => [1, 2, 3]]
        );

        $this->assertSame('1;2;3;', $html);
    }

    public function test_push_evalua_blade_en_captura(): void
    {
        $html = $this->render(
            "@push('s')url: {{ \$ruta }}@endpush@stack('s')",
            ['ruta' => '/home']
        );

        $this->assertSame('url: /home', $html);
    }

    public function test_stacks_no_filtran_entre_renders(): void
    {
        $primero = $this->render("@push('s')DE-A@endpush@stack('s')");
        $segundo = $this->render("@stack('s')");

        $this->assertSame('DE-A', $primero);
        $this->assertSame('', $segundo);
    }

    public function test_push_anidado_cierra_en_orden_lifo(): void
    {
        //el cierre interno agrega B primero; el externo agrega A al final
        $html = $this->render("@push('s')X@endpush@push('s')A@push('s')B@endpush@endpush@stack('s')");

        $this->assertSame('XBA', $html);
    }
}
