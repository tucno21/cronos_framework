<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\CronosEngine;
use PHPUnit\Framework\TestCase;

class CronosEngineTest extends TestCase
{
    private CronosEngine $engine;
    private string $viewsDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        $base            = sys_get_temp_dir() . '/cronos_test_' . uniqid();
        $this->viewsDir  = $base . '/views';
        $this->cacheDir  = $base . '/cache';

        mkdir($this->viewsDir, 0777, true);
        mkdir($this->cacheDir, 0777, true);

        $this->engine = new CronosEngine($this->viewsDir, $this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->viewsDir);
        $this->deleteDirectory($this->cacheDir);
        rmdir(dirname($this->viewsDir));
    }

    private function makeView(string $name, string $content): string
    {
        $relativePath = str_replace('.', DIRECTORY_SEPARATOR, $name) . '.php';
        $fullPath     = $this->viewsDir . DIRECTORY_SEPARATOR . $relativePath;
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

    public function test_variables_con_escape_html(): void
    {
        $this->makeView('test', '<p>{{ $nombre }}</p>');
        $html = $this->render('test', ['nombre' => 'Juan <b>Pérez</b>']);
        $this->assertStringContainsString('Juan &lt;b&gt;Pérez&lt;/b&gt;', $html);
    }

    public function test_variables_sin_escape_raw(): void
    {
        $this->makeView('test', '<div>{!! $html !!}</div>');
        $html = $this->render('test', ['html' => '<b>negrita</b>']);
        $this->assertStringContainsString('<b>negrita</b>', $html);
    }

    public function test_if_verdadero(): void
    {
        $this->makeView('test', '@if($activo)<span>sí</span>@endif');
        $html = $this->render('test', ['activo' => true]);
        $this->assertStringContainsString('<span>sí</span>', $html);
    }

    public function test_if_falso_no_renderiza(): void
    {
        $this->makeView('test', '@if($activo)<span>sí</span>@endif');
        $html = $this->render('test', ['activo' => false]);
        $this->assertStringNotContainsString('<span>sí</span>', $html);
    }

    public function test_foreach_itera_items(): void
    {
        $this->makeView('test', '@foreach($items as $item)<li>{{ $item }}</li>@endforeach');
        $html = $this->render('test', ['items' => ['alfa', 'beta', 'gamma']]);
        $this->assertStringContainsString('<li>alfa</li>', $html);
        $this->assertStringContainsString('<li>beta</li>', $html);
        $this->assertStringContainsString('<li>gamma</li>', $html);
    }

    public function test_csrf_genera_input_hidden(): void
    {
        $this->makeView('test', '<form>@csrf</form>');
        $html = $this->render('test');
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_method_put_genera_input_hidden(): void
    {
        $this->makeView('test', '<form>@method(\'PUT\')</form>');
        $html = $this->render('test');
        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="PUT"', $html);
    }

    public function test_forelse_con_coleccion_llena(): void
    {
        $this->makeView('test', '@forelse($items as $item)<li>{{ $item }}</li>@empty<p>vacío</p>@endforelse');
        $html = $this->render('test', ['items' => ['x', 'y']]);
        $this->assertStringContainsString('<li>x</li>', $html);
        $this->assertStringContainsString('<li>y</li>', $html);
        $this->assertStringNotContainsString('<p>vacío</p>', $html);
    }

    public function test_comentarios_blade_no_aparecen_en_html(): void
    {
        $this->makeView('test', '<p>visible</p>{{-- invisible --}}<p>visible</p>');
        $html = $this->render('test');
        $this->assertStringNotContainsString('invisible', $html);
    }

    public function test_componente_x_con_slot_por_defecto(): void
    {
        $componentsDir = $this->viewsDir . DIRECTORY_SEPARATOR . 'components';
        mkdir($componentsDir, 0777, true);
        file_put_contents($componentsDir . '/alert.php', '<div class="alert-<?= $type ?>"><?= $slot ?></div>');

        $this->makeView('test', '<x-alert type="success">Operación exitosa</x-alert>');
        $html = $this->render('test');
        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Operación exitosa', $html);
    }

    public function test_xss_escapeado_en_variables(): void
    {
        $this->makeView('test', '{{ $input }}');
        $html = $this->render('test', ['input' => '<script>alert("xss")</script>']);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_vista_inexistente_lanza_error(): void
    {
        $this->expectException(\Error::class);
        $this->engine->render('vista.que.no.existe');
    }

    public function test_cache_se_genera_en_disco(): void
    {
        $this->makeView('test', '<p>Hola</p>');
        $this->render('test');
        $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . md5('test') . '.php';
        $this->assertFileExists($cacheFile);
    }

    /**
     * Test que verifica que los comentarios en layouts incluidos son eliminados.
     * Este test cubre el bug donde compileComments se ejecutaba antes de compileExtends,
     * por lo que los comentarios en layouts no eran procesados.
     */
    public function test_comentarios_en_layout_incluido_son_eliminados(): void
    {
        // El comentario está en el LAYOUT, no en la vista hija
        // Simula el caso real donde head.php tiene {{-- comentarios --}}
        $layout = '<html>{{-- comentario en el layout --}}<body>@yield(\'content\')</body></html>';
        $this->makeView('layouts.head', $layout);

        $extends = '@extends(\'layouts.head\')';
        $section = '@section(\'content\')<p>Contenido</p>@endsection';
        $page = $extends . $section;
        $this->makeView('pagina', $page);

        $html = $this->render('pagina');

        $this->assertStringNotContainsString('comentario en el layout', $html);
        $this->assertStringContainsString('<p>Contenido</p>', $html);
    }

    /**
     * Test que verifica que @stack en layouts incluidos funciona correctamente
     * cuando el @push está en la vista hija.
     * Este test cubre el bug donde compilePushStack se ejecutaba antes de compileExtends.
     */
    public function test_stack_en_layout_incluido_funciona(): void
    {
        // El @stack está en el LAYOUT (incluido), el @push en la vista hija
        $layout = '<html><head>@stack(\'styles\')</head><body>@yield(\'content\')@stack(\'scripts\')</body></html>';
        $this->makeView('layouts.head', $layout);

        $extends = '@extends(\'layouts.head\')';
        $push = '@push(\'scripts\')<script src="/app.js"></script>@endpush';
        $section = '@section(\'content\')<main>ok</main>@endsection';
        $page = $extends . $push . $section;
        $this->makeView('pagina', $page);

        $html = $this->render('pagina');

        $this->assertStringContainsString('<script src="/app.js"></script>', $html);
        $this->assertStringContainsString('<main>ok</main>', $html);
    }
}
