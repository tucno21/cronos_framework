<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\BladeEngine;
use Cronos\View\Exceptions\ViewCompileException;
use Cronos\View\Exceptions\ViewNotFoundException;
use Tests\TestCase\CronosTestCase;

class BladeEngineTest extends CronosTestCase
{
    private BladeEngine $engine;
    private string $viewsDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $base = sys_get_temp_dir() . '/cronos_engine_test_' . uniqid();
        $this->viewsDir = $base . '/views';
        $this->cacheDir = $base . '/cache';

        mkdir($this->viewsDir, 0777, true);
        mkdir($this->cacheDir, 0777, true);

        $this->engine = new BladeEngine($this->viewsDir, $this->cacheDir);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir . '/..', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $path = $file->getRealPath();

            if (str_starts_with($path, $this->cacheDir . '/..') || str_starts_with($path, $this->viewsDir)) {
                $file->isDir() ? @rmdir($path) : @unlink($path);
            }
        }

        parent::tearDown();
    }

    private function makeView(string $name, string $content): void
    {
        $path = $this->viewsDir . '/' . str_replace('.', '/', $name) . '.php';
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $content);
    }

    private function compiledFileOf(string $name): string
    {
        //mismo formato de ruta que usa el engine (DIRECTORY_SEPARATOR)
        $sourcePath = $this->viewsDir . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $name) . '.php';

        return $this->cacheDir . '/views/' . md5($sourcePath) . '.php';
    }

    // ── render basico ─────────────────────────────────────

    public function test_render_basico_con_params_y_escape(): void
    {
        $this->makeView('saludo', '<p>{{ $nombre }}</p>');

        $this->assertSame('<p>Juan &lt;b&gt;</p>', $this->engine->render('saludo', ['nombre' => 'Juan <b>']));
    }

    public function test_vista_inexistente_lanza_view_not_found(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $this->engine->render('que.no.existe');
    }

    public function test_exists(): void
    {
        $this->makeView('hay', 'x');

        $this->assertTrue($this->engine->exists('hay'));
        $this->assertFalse($this->engine->exists('no-hay'));
    }

    // ── scope aislado ─────────────────────────────────────

    public function test_params_no_pueden_pisar_variables_internas(): void
    {
        $this->makeView('victima', '<p>VISTA-LEGITIMA</p>');

        //intentar incluir otro archivo via colision de nombres debe ser ignorado
        $html = $this->engine->render('victima', ['__viewPath' => $this->viewsDir . '/victima.php']);

        $this->assertSame('<p>VISTA-LEGITIMA</p>', $html);
    }

    // ── errores con contexto ──────────────────────────────

    public function test_error_de_compilacion_incluye_nombre_de_vista(): void
    {
        $this->makeView('rota', '@foreach $x as $y @endforeach');

        try {
            $this->engine->render('rota');
            $this->fail('Se esperaba ViewCompileException');
        } catch (ViewCompileException $e) {
            $this->assertStringContainsString('[rota]', $e->getMessage());
        }
    }

    public function test_error_de_runtime_se_envuelve_con_contexto(): void
    {
        $this->makeView('fatal', '{{ funcion_que_no_existe() }}');

        try {
            $this->engine->render('fatal');
            $this->fail('Se esperaba ViewCompileException');
        } catch (ViewCompileException $e) {
            $this->assertStringContainsString('Error renderizando la vista [fatal]', $e->getMessage());
        }
    }

    // ── cache ─────────────────────────────────────────────

    public function test_cache_hit_no_recompila(): void
    {
        $this->makeView('cacheable', '<p>ORIGINAL</p>');

        $this->assertSame('<p>ORIGINAL</p>', $this->engine->render('cacheable'));

        //adulterar el archivo compilado: si el cache sirve, la salida cambia
        file_put_contents($this->compiledFileOf('cacheable'), '<?php echo "FAKE-CACHE"; ?>');

        $this->assertSame('FAKE-CACHE', $this->engine->render('cacheable'));
    }

    public function test_invalidacion_por_mtime_de_la_vista(): void
    {
        $this->makeView('cambia', '<p>V1</p>');

        $this->assertSame('<p>V1</p>', $this->engine->render('cambia'));

        sleep(1);
        $this->makeView('cambia', '<p>V2</p>');

        $this->assertSame('<p>V2</p>', $this->engine->render('cambia'));
    }

    public function test_invalidacion_por_dependencia_layout(): void
    {
        $this->makeView('layouts.base', '<html>@yield(\'content\')</html>');
        $this->makeView('hija', "@extends('layouts.base')@section('content')A@endsection");

        $this->assertSame('<html>A</html>', $this->engine->render('hija'));

        //modificar SOLO el layout: el cache de la hija debe invalidarse
        sleep(1);
        $this->makeView('layouts.base', '<html><b>@yield(\'content\')</b></html>');

        $this->assertSame('<html><b>A</b></html>', $this->engine->render('hija'));
    }

    public function test_invalidacion_por_dependencia_include(): void
    {
        $this->makeView('partials.pie', 'PIE-1');
        $this->makeView('pagina', "@include('partials.pie')");

        $this->assertSame('PIE-1', $this->engine->render('pagina'));

        sleep(1);
        $this->makeView('partials.pie', 'PIE-2');

        $this->assertSame('PIE-2', $this->engine->render('pagina'));
    }

    // ── integracion de features ───────────────────────────

    public function test_include_anidado_a_traves_del_motor(): void
    {
        $this->makeView('partials.interno', 'INT');
        $this->makeView('partials.externo', "EXT[@include('partials.interno')]");
        $this->makeView('pagina', "@include('partials.externo')");

        $this->assertSame('EXT[INT]', $this->engine->render('pagina'));
    }

    public function test_componente_a_traves_del_motor(): void
    {
        $this->makeView('components/alerta', "@props(['type' => 'info'])<i class=\"{{ \$type }}\">{{ \$slot }}</i>");
        $this->makeView('pagina', '<x-alerta type="error">BOOM {{ $x }}</x-alerta>');

        $html = $this->engine->render('pagina', ['x' => '!']);

        $this->assertSame('<i class="error">BOOM !</i>', $html);
    }

    public function test_once_deduplica_en_el_mismo_render(): void
    {
        $this->makeView('partials.unico', "@once<script>una-vez</script>@endonce");
        $this->makeView('pagina', "@include('partials.unico')@include('partials.unico')");

        $this->assertSame('<script>una-vez</script>', $this->engine->render('pagina'));
    }

    public function test_push_once_deduplica_el_stack(): void
    {
        $this->makeView('layouts.base', '<html>@stack(\'scripts\')</html>');
        $this->makeView('partials.script', "@pushOnce('scripts')<script>push-unico</script>@endPushOnce");
        $this->makeView('pagina', "@extends('layouts.base')@include('partials.script')@include('partials.script')");

        $this->assertSame('<html><script>push-unico</script></html>', $this->engine->render('pagina'));
    }

    // ── formularios con sesion ────────────────────────────

    public function test_csrf_genera_input_con_token_de_sesion(): void
    {
        $this->makeView('form', '<form>@csrf</form>');

        $html = $this->engine->render('form');

        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString(csrf_token(), $html);
    }

    public function test_method_genera_input_hidden(): void
    {
        $this->makeView('form', '<form>@method(\'DELETE\')</form>');

        $html = $this->engine->render('form');

        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="DELETE"', $html);
    }

    public function test_error_directive_con_sesion(): void
    {
        $this->makeView('form', '@error(\'email\')<em>{{ $message }}</em>@enderror');

        $this->session->setErrorsInputs(['email' => 'a@b.c'], ['email' => 'EMAIL INVALIDO']);

        $html = $this->engine->render('form');

        $this->assertSame('<em>EMAIL INVALIDO</em>', $html);
    }

    public function test_error_directive_sin_error_no_muestra_nada(): void
    {
        $this->makeView('form', '<input>@error(\'email\')<em>{{ $message }}</em>@enderror');

        $html = $this->engine->render('form');

        $this->assertSame('<input>', trim($html));
    }
}
