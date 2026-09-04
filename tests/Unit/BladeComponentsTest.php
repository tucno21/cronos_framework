<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\View\Compiler\BladeCompiler;
use Cronos\View\SectionManager;
use Tests\TestCase\CronosTestCase;

/**
 * entorno de prueba para componentes con registro de plantillas que
 * emula el resolucion de componentes/<ruta> del motor completo.
 */
class ComponentTestEnvironment extends SectionManager
{
    /** @var array<string, string> */
    public array $views = [];

    public function viewExists(string $view): bool
    {
        return isset($this->views[$view]);
    }

    public function makeView(string $view, array $vars): string
    {
        $view = str_replace('.', '/', $view);

        if (!isset($this->views[$view])) {
            throw new \RuntimeException("La vista [{$view}] no existe");
        }

        $file = sys_get_temp_dir() . '/cronos_comp_view_' . uniqid('', true) . '.php';
        file_put_contents($file, (new BladeCompiler())->compileString($this->views[$view]));

        ob_start();

        try {
            (static function (string $__viewPath, array $__viewData, ComponentTestEnvironment $__viewEnv): void {
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

class BladeComponentsTest extends CronosTestCase
{
    private ComponentTestEnvironment $env;

    protected function setUp(): void
    {
        parent::setUp();

        $this->env = new ComponentTestEnvironment();
    }

    private function renderX(string $template, array $data = []): string
    {
        $this->env->views['main'] = $template;

        return $this->env->makeView('main', $data);
    }

    // ── props basicos ─────────────────────────────────────

    public function test_componente_self_closing_con_props(): void
    {
        $this->env->views['components/badge'] = '<?php $type = $type ?? "info"; ?>[{{ $type }}]';

        $this->assertSame('[error]', $this->renderX('<x-badge type="error"/>'));
    }

    public function test_binding_expresion_con_dos_puntos(): void
    {
        $this->env->views['components/total'] = '[{{ $cantidad }}]';

        $html = $this->renderX('<x-total :cantidad="$n"/>', ['n' => 7]);

        $this->assertSame('[7]', $html);
    }

    public function test_atributo_booleano(): void
    {
        $this->env->views['components/input'] = '<?php $required = $required ?? false; ?><input{{ $required ? " R" : "" }}>';

        $this->assertSame('<input R>', $this->renderX('<x-input required/>'));
        $this->assertSame('<input>', $this->renderX('<x-input/>'));
    }

    public function test_attr_con_echo_se_escapa(): void
    {
        $this->env->views['components/link'] = '<a>{{ $title }}</a>';

        $html = $this->renderX('<x-link title="{{ $texto }}"/>', ['texto' => '<b>']);

        $this->assertSame('<a>&lt;b&gt;</a>', $html);
    }

    // ── slots ─────────────────────────────────────────────

    public function test_slot_default_evalua_en_scope_del_padre(): void
    {
        $this->env->views['components/card'] = 'C({{ $slot }})';

        $html = $this->renderX('<x-card>TXT {{ $x }}</x-card>', ['x' => 'VAL']);

        $this->assertSame('C(TXT VAL)', $html);
    }

    public function test_slots_nombrados(): void
    {
        $this->env->views['components/card'] = 'T({{ $slotTitulo }})B({{ $slot }})';

        $html = $this->renderX('<x-card><x-slot:slotTitulo>EL-TITULO</x-slot:slotTitulo>CUERPO</x-card>');

        $this->assertSame('T(EL-TITULO)B(CUERPO)', $html);
    }

    public function test_slot_con_directivas_se_compilan(): void
    {
        $this->env->views['components/box'] = '({{ $slot }})';

        $html = $this->renderX('<x-box>@if($ok)SI@else NO@endif</x-box>', ['ok' => true]);

        $this->assertSame('(SI)', $html);
    }

    public function test_slot_via_variable__slots_compatibilidad(): void
    {
        //componentes del motor anterior usan $__slots
        $this->env->views['components/legacy'] = 'D({{ $__slots["default"] }})F({{ $__slots["footer"] ?? "-" }})';

        $html = $this->renderX('<x-legacy><x-slot:footer>FOOT</x-slot:footer>CUERPO</x-legacy>');

        $this->assertSame('D(CUERPO)F(FOOT)', $html);
    }

    // ── @props y $attributes ──────────────────────────────

    public function test_props_con_defaults(): void
    {
        $this->env->views['components/badge'] = "@props(['type' => 'info'])<b>{{ \$type }}</b>";

        $this->assertSame('<b>info</b>', $this->renderX('<x-badge/>'));
        $this->assertSame('<b>error</b>', $this->renderX('<x-badge type="error"/>'));
    }

    public function test_atributos_extra_en_attributes(): void
    {
        $this->env->views['components/badge'] = "@props(['type' => 'info'])<span data-a=\"{{ \$attributes->get('data-a') }}\" rest=\"{{ \$attributes }}\"></span>";

        $html = $this->renderX('<x-badge type="error" data-a="1" id="x"/>');

        $this->assertStringContainsString('data-a="1"', $html);
        $this->assertStringContainsString('id="x"', $html);
        $this->assertStringNotContainsString('type=', $html);
    }

    public function test_attributes_merge_concatena_class(): void
    {
        $this->env->views['components/btn'] = "@props([])<button {{ \$attributes->merge(['class' => 'btn']) }}>OK</button>";

        $html = $this->renderX('<x-btn class="rojo" type="submit"/>');

        $this->assertSame('<button class="btn rojo" type="submit">OK</button>', $html);
    }

    public function test_attributes_only_y_except(): void
    {
        $this->env->views['components/btn'] = "@props([])[{{ \$attributes->only(['a'])->all() === ['a' => '1'] ? 'ONLY-OK' : 'ONLY-FAIL' }}][{{ \$attributes->except(['a'])->has('a') ? 'EXC-FAIL' : 'EXC-OK' }}]";

        $html = $this->renderX('<x-btn a="1" b="2"/>');

        $this->assertSame('[ONLY-OK][EXC-OK]', $html);
    }

    // ── anidamiento y dinamicos ───────────────────────────

    public function test_componente_anidado_dentro_de_slot(): void
    {
        $this->env->views['components/out'] = 'O({{ $slot }})';
        $this->env->views['components/in'] = 'I';

        $this->assertSame('O(I)', $this->renderX('<x-out><x-in/></x-out>'));
    }

    public function test_mismo_nombre_anidado(): void
    {
        $this->env->views['components/list'] = 'L({{ $slot }})';

        $this->assertSame('L(L())', $this->renderX('<x-list><x-list/></x-list>'));
    }

    public function test_dynamic_component(): void
    {
        $this->env->views['components/alert'] = 'A({{ $msg }})';

        $html = $this->renderX('<x-dynamic-component :component="$comp" :msg="\'HOLA\'"/>', ['comp' => 'alert']);

        $this->assertSame('A(HOLA)', $html);
    }

    public function test_notacion_punto_subcarpetas(): void
    {
        $this->env->views['components/form/input'] = '<input>';

        $this->assertSame('<input>', $this->renderX('<x-form.input/>'));
    }

    // ── atributos condicionales ───────────────────────────

    public function test_class_condicional(): void
    {
        $html = $this->renderX('<p class="@class([\'base\', \'on\' => $activo, \'off\' => !$activo])"></p>', ['activo' => true]);

        $this->assertSame('<p class="base on"></p>', $html);
    }

    public function test_checked_selected_disabled_readonly_required(): void
    {
        $tpl = '<input type="checkbox" @checked($v)>'
            . '<option @selected($v)>'
            . '<button @disabled($v)>'
            . '<input @readonly($v)>'
            . '<input @required($v)>';

        $html = $this->renderX($tpl, ['v' => true]);
        $this->assertSame('<input type="checkbox" checked><option selected><button disabled><input readonly><input required>', $html);

        $html = $this->renderX($tpl, ['v' => false]);
        $this->assertSame('<input type="checkbox" ><option ><button ><input ><input >', $html);
    }

    // ── robustez ──────────────────────────────────────────

    public function test_tag_desconocido_sin_cierre_queda_literal(): void
    {
        $html = $this->renderX('<x-nada><p>x</p>');

        $this->assertSame('<x-nada><p>x</p>', $html);
    }

    public function test_compilado_no_contiene_tags_x(): void
    {
        $this->env->views['components/badge'] = '[{{ $slot }}]';
        $compilado = (new BladeCompiler())->compileString('<x-badge>HOLA</x-badge>');

        $this->assertStringNotContainsString('<x-badge>', $compilado);
        $this->assertStringNotContainsString('</x-badge>', $compilado);
    }
}

