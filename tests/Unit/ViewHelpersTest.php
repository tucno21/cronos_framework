<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\App;
use Cronos\Session\Session;
use Tests\TestCase\CronosTestCase;

class ViewHelpersTest extends CronosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //baseline segura para asset() (App::$root puede no estar inicializado en unit tests)
        if (!isset(App::$root)) {
            App::$root = sys_get_temp_dir();
        }
    }

    public function test_e_escapa_html_y_comillas(): void
    {
        $this->assertSame('Juan &lt;b&gt; &quot;Pérez&quot; &#039;X&#039;', e('Juan <b> "Pérez" \'X\''));
    }

    public function test_e_valores_especiales(): void
    {
        $this->assertSame('', e(null));
        $this->assertSame('1', e(true));
        $this->assertSame('', e(false));
        $this->assertSame('42', e(42));
        $this->assertSame('{&quot;a&quot;:1}', e(['a' => 1]));
    }

    public function test_e_no_doble_escape_por_defecto(): void
    {
        $this->assertSame('&lt;b&gt;', e('&lt;b&gt;'));
        $this->assertSame('&amp;lt;b&amp;gt;', e('&lt;b&gt;', true));
    }

    public function test_csrf_token_genera_token_de_40_caracteres(): void
    {
        $token = csrf_token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $token);
    }

    public function test_csrf_token_es_estable_en_la_misma_sesion(): void
    {
        $first = csrf_token();
        $second = csrf_token();

        $this->assertSame($first, $second);
        $this->assertSame($first, $this->session->get(Session::SESSION_CSRF_TOKEN));
    }

    public function test_csrf_token_regenera_al_eliminarlo_de_la_sesion(): void
    {
        $first = csrf_token();

        $this->session->remove(Session::SESSION_CSRF_TOKEN);

        $second = csrf_token();

        $this->assertNotSame($first, $second);
    }

    public function test_csrf_token_fallback_sin_sesion(): void
    {
        //simular entorno sin sesion disponible (cli sin app bootstrapeada)
        unset($GLOBALS['test_session']);

        $first = csrf_token();
        $second = csrf_token();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $first);
        $this->assertSame($first, $second, 'el token fallback debe ser estable por proceso');
    }

    public function test_asset_genera_url_versionada_para_archivo_existente(): void
    {
        $root = sys_get_temp_dir() . '/cronos_asset_test_' . uniqid();
        mkdir($root . '/public/assets/css', 0777, true);
        file_put_contents($root . '/public/assets/css/app.css', 'body{}');

        $previousRoot = App::$root;
        App::$root = $root;

        try {
            $expectedBase = defined('base_url') ? base_url : 'http://localhost';
            $url = asset('assets/css/app.css');

            $this->assertSame(
                rtrim($expectedBase, '/') . '/assets/css/app.css?v=' . filemtime($root . '/public/assets/css/app.css'),
                $url
            );
        } finally {
            App::$root = $previousRoot;
            @unlink($root . '/public/assets/css/app.css');
            @rmdir($root . '/public/assets/css');
            @rmdir($root . '/public/assets');
            @rmdir($root . '/public');
            @rmdir($root);
        }
    }

    public function test_asset_sin_version_para_archivo_inexistente(): void
    {
        $previousRoot = App::$root;
        App::$root = sys_get_temp_dir();

        try {
            $expectedBase = defined('base_url') ? base_url : 'http://localhost';
            $url = asset('assets/css/no-existe.css');

            $this->assertSame(rtrim($expectedBase, '/') . '/assets/css/no-existe.css', $url);
        } finally {
            App::$root = $previousRoot;
        }
    }
}
