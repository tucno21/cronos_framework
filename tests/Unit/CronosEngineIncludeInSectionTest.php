<?php

namespace Tests\Unit;

use Cronos\View\CronosEngine;
use PHPUnit\Framework\TestCase;

class CronosEngineIncludeInSectionTest extends TestCase
{
    private CronosEngine $engine;
    private string $viewDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        $base           = sys_get_temp_dir() . '/cronos_test_' . uniqid();
        $this->viewDir  = $base . '/views';
        $this->cacheDir = $base . '/cache';

        mkdir($this->viewDir, 0777, true);
        mkdir($this->cacheDir, 0777, true);

        // Subcarpetas necesarias
        mkdir($this->viewDir . '/layouts', 0777, true);
        mkdir($this->viewDir . '/components', 0777, true);

        $this->engine = new CronosEngine($this->viewDir, $this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->viewDir);
        $this->deleteDirectory($this->cacheDir);
        rmdir(dirname($this->viewDir));
    }

    private function makeView(string $name, string $content): string
    {
        $relativePath = str_replace('.', DIRECTORY_SEPARATOR, $name) . '.php';
        $fullPath     = $this->viewDir . DIRECTORY_SEPARATOR . $relativePath;
        $dir          = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($fullPath, $content);
        return $name;
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }

    private function render(string $view, array $params = []): string
    {
        return trim($this->engine->render($view, $params));
    }

    // ── TEST 1: @include simple fuera de @section (baseline) ─────
    public function test_include_outside_section_works(): void
    {
        // Componente
        file_put_contents(
            $this->viewDir . '/components/simple.php',
            '<p>SOY EL COMPONENTE</p>'
        );

        // Vista con @include fuera de @section (patrón home/)
        $this->makeView(
            'page',
            '@include(\'components.simple\')'
        );

        $result = $this->render('page');

        $this->assertStringContainsString(
            'SOY EL COMPONENTE',
            $result,
            'FALLO: @include fuera de @section no funciona (caso base)'
        );
    }

    // ── TEST 2: @include dentro de @section (el caso que falla) ──
    public function test_include_inside_section_works(): void
    {
        // Layout con @yield
        $this->makeView(
            'layouts.app',
            '<!DOCTYPE html><body>@yield(\'content\')</body>'
        );

        // Componente a incluir
        $this->makeView(
            'components.card',
            '<div class="card">SOY LA CARD</div>'
        );

        // Vista hija que extiende el layout e incluye el componente dentro del @section
        $this->makeView(
            'dashboard',
            implode("\n", [
                "@extends('layouts.app')",
                "@section('content')",
                "@include('components.card')",
                "@endsection",
            ])
        );

        $result = $this->render('dashboard');

        $this->assertStringContainsString(
            'SOY LA CARD',
            $result,
            'FALLO: @include dentro de @section no se procesa'
        );
        $this->assertStringContainsString(
            '<!DOCTYPE html>',
            $result,
            'FALLO: el layout no se fusionó correctamente'
        );
        $this->assertStringNotContainsString(
            "@include('components.card')",
            $result,
            'FALLO: @include apareció como texto literal en la salida'
        );
    }

    // ── TEST 3: @include con variables dentro de @section ────────
    public function test_include_with_variables_inside_section(): void
    {
        $this->makeView(
            'layouts.app',
            '<body>@yield(\'content\')</body>'
        );

        $this->makeView(
            'components.data-table',
            implode("\n", [
                '<?php $title = $title ?? "Default"; ?>',
                '<div id="{{ $tableId }}">{{ $title }}</div>',
            ])
        );

        $this->makeView(
            'index',
            implode("\n", [
                "@extends('layouts.app')",
                "@section('content')",
                "@include('components.data-table', ['tableId' => 'miTabla', 'title' => 'Mi Título'])",
                "@endsection",
            ])
        );

        $result = $this->render('index');

        $this->assertStringContainsString(
            'id="miTabla"',
            $result,
            'FALLO: la variable $tableId no se pasó al componente'
        );
        $this->assertStringContainsString(
            'Mi Título',
            $result,
            'FALLO: la variable $title no se pasó al componente'
        );
    }

    // ── TEST 4: @push dentro de @include dentro de @section ──────
    public function test_push_inside_included_component_inside_section(): void
    {
        $this->makeView(
            'layouts.app',
            implode("\n", [
                '<body>',
                '@yield(\'content\')',
                '@stack(\'scripts\')',
                '</body>',
            ])
        );

        // Componente que tiene su propio @push
        $this->makeView(
            'components.table',
            implode("\n", [
                '<div id="tabla">TABLA</div>',
                '@push(\'scripts\')',
                '<script>console.log("tabla lista")</script>',
                '@endpush',
            ])
        );

        $this->makeView(
            'page',
            implode("\n", [
                "@extends('layouts.app')",
                "@section('content')",
                "@include('components.table')",
                "@endsection",
            ])
        );

        $result = $this->render('page');

        $this->assertStringContainsString(
            'TABLA',
            $result,
            'FALLO: el componente no se incluyó'
        );
        $this->assertStringContainsString(
            'console.log("tabla lista")',
            $result,
            'FALLO: @push dentro del componente incluido en @section no se procesó'
        );
        // El script debe aparecer ANTES del </body>
        $posScript = strpos($result, 'console.log');
        $posBody   = strpos($result, '</body>');
        $this->assertLessThan(
            $posBody,
            $posScript,
            'FALLO: el script no está antes del </body>'
        );
    }
}
