<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\Debug\CliDumper;
use Cronos\Debug\Dumper;
use Cronos\Debug\HtmlDumper;
use Cronos\Model\ModelCollection;
use PHPUnit\Framework\TestCase;

class DumperTest extends TestCase
{
    public function test_cli_dumper_formats_scalars_and_arrays(): void
    {
        $dumper = new CliDumper();

        $outputNull = $dumper->dump(null);
        $this->assertStringContainsString('null', $outputNull);

        $outputBool = $dumper->dump(true);
        $this->assertStringContainsString('true', $outputBool);

        $outputInt = $dumper->dump(12345);
        $this->assertStringContainsString('12345', $outputInt);

        $outputStr = $dumper->dump('Hola Cronos');
        $this->assertStringContainsString('Hola Cronos', $outputStr);
        $this->assertStringContainsString('(11)', $outputStr);

        $outputArr = $dumper->dump(['nombre' => 'Cronos', 'activo' => true]);
        $this->assertStringContainsString('nombre', $outputArr);
        $this->assertStringContainsString('Cronos', $outputArr);
    }

    public function test_cli_dumper_formats_generic_objects(): void
    {
        $dumper = new CliDumper();
        $obj = new class {
            public string $name = 'Prueba';
            protected int $id = 42;
        };

        $output = $dumper->dump($obj);
        $this->assertStringContainsString('name', $output);
        $this->assertStringContainsString('Prueba', $output);
        $this->assertStringContainsString('id', $output);
        $this->assertStringContainsString('42', $output);
    }

    public function test_html_dumper_renders_dark_theme_container_and_details(): void
    {
        $dumper = new HtmlDumper();
        $vars = [
            'mensaje' => 'Bienvenido',
            'datos' => [1, 2, 3],
        ];

        $html = $dumper->render([$vars], 'App/Controllers/TestController.php:15');

        $this->assertStringContainsString('cronos-dump-container', $html);
        $this->assertStringContainsString('📍 App/Controllers/TestController.php:15', $html);
        $this->assertStringContainsString('<details class="c-tree"', $html);
        $this->assertStringContainsString('cronosToggleAll', $html);
        $this->assertStringContainsString('Bienvenido', $html);
    }

    public function test_dump_and_d_functions_capture_output(): void
    {
        ob_start();
        dump('valor_prueba_dump', 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('valor_prueba_dump', $output);
        $this->assertStringContainsString('999', $output);
        $this->assertStringContainsString('📍', $output);

        ob_start();
        d(['clave' => 'test_d']);
        $outputD = ob_get_clean();

        $this->assertStringContainsString('test_d', $outputD);
    }

    public function test_dumper_detects_json_requests(): void
    {
        $previousAccept = $_SERVER['HTTP_ACCEPT'] ?? null;
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $this->assertTrue(Dumper::isJsonRequest());

        if ($previousAccept !== null) {
            $_SERVER['HTTP_ACCEPT'] = $previousAccept;
        } else {
            unset($_SERVER['HTTP_ACCEPT']);
        }
    }
}
