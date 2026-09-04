<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\Compiler\BladeCompiler;
use Cronos\View\Exceptions\ViewCompileException;
use Tests\TestCase\CronosTestCase;

class BladeCompilerTest extends CronosTestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/cronos_compiler_test_' . uniqid();

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

    private function compile(string $template): string
    {
        return (new BladeCompiler())->compileString($template);
    }

    private function render(string $template, array $data = []): string
    {
        $file = $this->cacheDir . '/view_' . uniqid() . '.php';
        file_put_contents($file, $this->compile($template));

        ob_start();

        (static function () use ($file, $data): void {
            extract($data);
            include $file;
        })();

        return (string) ob_get_clean();
    }

    // ── echo ──────────────────────────────────────────────

    public function test_echo_escapa_html(): void
    {
        $this->assertSame(
            '<p>Juan &lt;b&gt;Pérez&lt;/b&gt;</p>',
            $this->render('<p>{{ $nombre }}</p>', ['nombre' => 'Juan <b>Pérez</b>'])
        );
    }

    public function test_echo_valores_especiales(): void
    {
        $this->assertSame('', $this->render('{{ $x }}', ['x' => null]));
        $this->assertSame('1', $this->render('{{ $x }}', ['x' => true]));
        $this->assertSame('', $this->render('{{ $x }}', ['x' => false]));
        $this->assertSame('3', $this->render('{{ $x }}', ['x' => 3]));
    }

    public function test_echo_multilinea(): void
    {
        $this->assertSame('hola', $this->render("{{\n\$x\n}}", ['x' => 'hola']));
    }

    public function test_raw_echo_sin_escape(): void
    {
        $this->assertSame(
            '<div><b>negrita</b></div>',
            $this->render('<div>{!! $html !!}</div>', ['html' => '<b>negrita</b>'])
        );
    }

    // ── comentarios, verbatim y escape ────────────────────

    public function test_comentarios_eliminados_incluso_con_directivas_adentro(): void
    {
        $html = $this->render('A{{-- oculto @if(true) x @endif --}}B');

        $this->assertSame('AB', $html);
    }

    public function test_verbatim_protege_directivas(): void
    {
        $html = $this->render(
            '@verbatim {{ $x }} @if(true) NO-COMPILAR @endif @endverbatim',
            ['x' => 'VALOR']
        );

        $this->assertStringContainsString('{{ $x }}', $html);
        $this->assertStringContainsString('@if(true)', $html);
        $this->assertStringContainsString('NO-COMPILAR', $html);
        $this->assertStringNotContainsString('VALOR', $html, 'el contenido de verbatim no debe evaluarse');
    }

    public function test_doble_arroba_escapea_directiva(): void
    {
        $html = $this->render('@@if(true) texto @@endif');

        $this->assertSame('@if(true) texto @endif', $html);
    }

    // ── php y json ────────────────────────────────────────

    public function test_php_block(): void
    {
        $html = $this->render('@php $doble = $x * 2; @endphp{{ $doble }}', ['x' => 21]);

        $this->assertSame('42', $html);
    }

    public function test_php_inline(): void
    {
        $html = $this->render('@php($doble = $x * 2){{ $doble }}', ['x' => 5]);

        $this->assertSame('10', $html);
    }

    public function test_json(): void
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $esperado = json_encode(['a' => 1], $flags);

        $this->assertSame($esperado, $this->render('@json($datos)', ['datos' => ['a' => 1]]));
    }

    public function test_unset(): void
    {
        $html = $this->render('@php($x = 1)@unset($x)@if(!isset($x))ELIMINADA@endif');

        $this->assertSame('ELIMINADA', $html);
    }

    // ── condicionales ─────────────────────────────────────

    public function test_if_con_comillas_no_rompe(): void
    {
        $html = $this->render("@if(\$x == 'y')OK@endif", ['x' => 'y']);

        $this->assertSame('OK', $html);
    }

    public function test_if_con_funcion_y_parentesis_no_rompe(): void
    {
        $html = $this->render('@if(count($items) > 0)HAY@else NO@endif', ['items' => [1, 2]]);

        $this->assertSame('HAY', $html);
    }

    public function test_if_con_condicion_compleja(): void
    {
        $template = "@if(\$user->getName() !== 'x' && (\$a || \$b))VALIDO@endif";
        $data = ['user' => new class {
            public function getName(): string
            {
                return 'juan';
            }
        }, 'a' => false, 'b' => true];

        $this->assertSame('VALIDO', $this->render($template, $data));
    }

    public function test_if_elseif_else(): void
    {
        $template = '@if($n === 1)UNO@elseif($n === 2)DOS@else OTRO@endif';

        $this->assertSame('UNO', $this->render($template, ['n' => 1]));
        $this->assertSame('DOS', $this->render($template, ['n' => 2]));
        $this->assertSame(' OTRO', $this->render($template, ['n' => 9]));
    }

    public function test_if_anidado(): void
    {
        $template = '@if($a)A@if($b)B@else nob @endif@endif';

        $this->assertSame('AB', $this->render($template, ['a' => true, 'b' => true]));
        $this->assertSame('A nob ', $this->render($template, ['a' => true, 'b' => false]));
        $this->assertSame('', $this->render($template, ['a' => false, 'b' => true]));
    }

    public function test_unless(): void
    {
        $template = '@unless($activo)SIN ACTIVAR@endunless';

        $this->assertSame('SIN ACTIVAR', $this->render($template, ['activo' => false]));
        $this->assertSame('', $this->render($template, ['activo' => true]));
    }

    public function test_isset(): void
    {
        $template = '@isset($x)DEFINIDO@endisset';

        $this->assertSame('DEFINIDO', $this->render($template, ['x' => 0]));
        $this->assertSame('', $this->render($template, []));
    }

    public function test_empty_standalone(): void
    {
        $template = '@empty($x)VACIO@endempty';

        $this->assertSame('VACIO', $this->render($template, ['x' => []]));
        $this->assertSame('', $this->render($template, ['x' => [1]]));
    }

    public function test_auth_y_guest(): void
    {
        $this->session->attempt(['id' => 1]);

        $template = '@auth AUTENTICADO @endauth @guest INVITADO @endguest';
        $html = $this->render($template);

        $this->assertStringContainsString('AUTENTICADO', $html);
        $this->assertStringNotContainsString('INVITADO', $html);

        $this->session->logout();

        $html = $this->render($template);

        $this->assertStringNotContainsString('AUTENTICADO', $html);
        $this->assertStringContainsString('INVITADO', $html);
    }

    // ── loops ─────────────────────────────────────────────

    public function test_foreach_basico(): void
    {
        $html = $this->render(
            '@foreach($items as $item)<li>{{ $item }}</li>@endforeach',
            ['items' => ['alfa', 'beta']]
        );

        $this->assertSame('<li>alfa</li><li>beta</li>', $html);
    }

    public function test_foreach_clave_valor(): void
    {
        $html = $this->render(
            '@foreach($items as $clave => $valor){{ $clave }}={{ $valor }};@endforeach',
            ['items' => ['a' => 1, 'b' => 2]]
        );

        $this->assertSame('a=1;b=2;', $html);
    }

    public function test_variable_loop_completa(): void
    {
        $html = $this->render(
            '@foreach($items as $item)[{{ $loop->index }}|{{ $loop->iteration }}|{{ $loop->count }}|{{ $loop->remaining }}|{{ $loop->first ? "S" : "N" }}|{{ $loop->last ? "S" : "N" }}]@endforeach',
            ['items' => ['a', 'b', 'c']]
        );

        $this->assertSame('[0|1|3|2|S|N][1|2|3|1|N|N][2|3|3|0|N|S]', $html);
    }

    public function test_foreach_anidado_no_rompe(): void
    {
        $html = $this->render(
            '@foreach($grupos as $g)<div>@foreach($g as $i){{ $i }}@endforeach</div>@endforeach',
            ['grupos' => [['a', 'b'], ['c']]]
        );

        $this->assertSame('<div>ab</div><div>c</div>', $html);
    }

    public function test_foreach_anidado_con_loop_parent(): void
    {
        $html = $this->render(
            '@foreach($grupos as $g){{ $loop->index }}:@foreach($g as $i){{ $loop->parent->index }}.{{ $loop->index }} {{ $i }};@endforeach @endforeach',
            ['grupos' => [['a', 'b'], ['c']]]
        );

        $this->assertSame('0:0.0 a;0.1 b; 1:1.0 c; ', $html);
    }

    public function test_loop_se_restaura_tras_loop_anidado(): void
    {
        $html = $this->render(
            '@foreach($grupos as $g)@foreach($g as $i)x@endforeach[{{ $loop->index }}]@endforeach',
            ['grupos' => [['a'], ['b']]]
        );

        $this->assertSame('x[0]x[1]', $html);
    }

    public function test_foreach_anidado_tres_niveles(): void
    {
        $html = $this->render(
            '@foreach($n1 as $a){{ $loop->index }}(@foreach($n2 as $b){{ $loop->parent->index }}{{ $b }} @endforeach)@endforeach',
            ['n1' => ['X'], 'n2' => ['1', '2']]
        );

        $this->assertSame('0(01 02 )', $html);
    }

    public function test_foreach_sin_parentesis_lanza_excepcion(): void
    {
        $this->expectException(ViewCompileException::class);
        $this->expectExceptionMessage('@foreach requiere una expresion');

        $this->compile('@foreach $items as $i x@endforeach');
    }

    public function test_foreach_sin_as_lanza_excepcion(): void
    {
        $this->expectException(ViewCompileException::class);
        $this->expectExceptionMessage('coleccion as variable');

        $this->compile('@foreach($items)x@endforeach');
    }

    public function test_forelse_con_datos(): void
    {
        $html = $this->render(
            '@forelse($items as $i)<li>{{ $i }}</li>@empty SIN DATOS @endforelse',
            ['items' => ['x', 'y']]
        );

        $this->assertSame('<li>x</li><li>y</li>', $html);
    }

    public function test_forelse_vacio(): void
    {
        $html = $this->render(
            '@forelse($items as $i)<li>{{ $i }}</li>@empty SIN DATOS @endforelse',
            ['items' => []]
        );

        $this->assertSame(' SIN DATOS ', $html);
    }

    public function test_forelse_con_foreach_anidado(): void
    {
        $html = $this->render(
            '@forelse($grupos as $g)@foreach($g as $i){{ $i }}-@endforeach@empty NADA @endforelse',
            ['grupos' => [['a', 'b']]]
        );

        $this->assertSame('a-b-', $html);
    }

    public function test_for_basico(): void
    {
        $html = $this->render('@for($i = 0; $i < 3; $i++){{ $i }}@endfor');

        $this->assertSame('012', $html);
    }

    public function test_while_basico(): void
    {
        $html = $this->render('@php($i = 0)@while($i < 3){{ $i }}@php($i++)@endwhile');

        $this->assertSame('012', $html);
    }

    public function test_break_y_continue_con_condicion(): void
    {
        $html = $this->render(
            '@foreach($items as $i)@if($i === 2)@break@endif{{ $i }}@endforeach',
            ['items' => [1, 2, 3]]
        );

        $this->assertSame('1', $html);

        $html = $this->render(
            '@foreach($items as $i)@continue($i % 2 === 0){{ $i }}@endforeach',
            ['items' => [1, 2, 3, 4]]
        );

        $this->assertSame('13', $html);
    }

    public function test_switch_case_default(): void
    {
        $template = '@switch($n)@case(1)UNO@break@case(2)DOS@break@default OTRO@endswitch';

        $this->assertSame('UNO', $this->render($template, ['n' => 1]));
        $this->assertSame('DOS', $this->render($template, ['n' => 2]));
        $this->assertSame(' OTRO', $this->render($template, ['n' => 9]));
    }

    // ── integrado ─────────────────────────────────────────

    public function test_plantilla_combinada(): void
    {
        $template = '<ul>@foreach($usuarios as $u)@if($loop->first)<li class="primero">{{ $u }}</li>@elseif($loop->last)<li class="ultimo">{{ $u }}</li>@else<li>{{ $u }}</li>@endif@endforeach</ul>@empty($usuarios)SIN USUARIOS@endempty';

        $html = $this->render($template, ['usuarios' => ['ana', 'bob', 'cy']]);

        $esperado = '<ul><li class="primero">ana</li><li>bob</li><li class="ultimo">cy</li></ul>';

        $this->assertSame($esperado, $html);
    }

    public function test_salida_compilada_no_contiene_directivas(): void
    {
        $compilado = $this->compile('@if($a)@foreach($b as $c){{ $c }}@endforeach@endif');

        $this->assertStringNotContainsString('@if', $compilado);
        $this->assertStringNotContainsString('@foreach', $compilado);
        $this->assertStringNotContainsString('{{', $compilado);
    }
}
