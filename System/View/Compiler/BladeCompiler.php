<?php

namespace Cronos\View\Compiler;

use Cronos\View\Exceptions\ViewCompileException;

class BladeCompiler
{
    protected const PLACEHOLDER_VERBATIM = '__CRONOS_VERBATIM_%d__';
    protected const PLACEHOLDER_ESCAPED = '__CRONOS_ESCAPED_%d__';

    /** @var array<string, callable> directivas personalizadas registradas */
    protected static array $customDirectives = [];

    /**
     * registra una directiva personalizada.
     * el handler recibe la expresion entre parentesis (o null si no tiene)
     * y retorna el codigo PHP de reemplazo (o null para dejar la directiva como texto).
     */
    public static function directive(string $name, callable $handler): void
    {
        static::$customDirectives[$name] = $handler;
    }

    public static function getCustomDirectives(): array
    {
        return static::$customDirectives;
    }

    /**
     * exclusion de variables internas del scope al pasar el scope actual
     * a una sub-vista (evita que extract() pise el render en curso)
     */
    protected const INTERNAL_VIEW_VARS_FILTER = "['__viewPath' => 1, '__viewData' => 1, '__viewEnv' => 1]";

    protected array $verbatimSegments = [];
    protected array $escapedSegments = [];
    protected string $extendsFooter = '';
    protected int $componentCounter = 0;

    /** @var list<string> nombres de vistas referenciadas (extends, includes, componentes) */
    protected array $viewDependencies = [];

    /**
     * compila una plantilla blade a codigo PHP.
     *
     * @param string $viewName nombre de la vista para claves de @once/@pushOnce
     */
    public function compileString(string $value, string $viewName = ''): string
    {
        $this->verbatimSegments = [];
        $this->escapedSegments = [];
        $this->extendsFooter = '';
        $this->componentCounter = 0;
        $this->viewDependencies = [];

        $value = $this->compileEscapedDirectives($value);
        $value = $this->compileVerbatim($value);
        $value = $this->compileComments($value);
        $value = $this->compileExtends($value);
        $value = $this->compileSections($value);
        $value = $this->compileYield($value);
        $value = $this->compileStacks($value);
        $value = $this->compileIncludes($value);
        $value = $this->compileOnce($value, $viewName);
        $value = $this->compileProps($value);
        $value = $this->compileConditionalAttributes($value);
        $value = $this->compileFormSecurity($value);
        $value = $this->compileAsset($value);
        $value = $this->compileDebug($value);
        $value = $this->compileCustomDirectives($value);
        $value = $this->compileXComponents($value);
        $value = $this->compilePhpBlock($value);
        $value = $this->compileRawEcho($value);
        $value = $this->compileBreakContinue($value);
        $value = $this->compileSwitch($value);
        $value = $this->compileForelse($value);
        $value = $this->compileForeach($value);
        $value = $this->compileFor($value);
        $value = $this->compileWhile($value);
        $value = $this->compileConditionals($value);
        $value = $this->compileInlinePhp($value);
        $value = $this->compileJson($value);
        $value = $this->compileUnset($value);
        $value = $this->compileEcho($value);

        $value = $this->restoreEscaped($value);

        //el layout se renderiza al final de la vista hija, cuando las
        //secciones ya fueron capturadas (mecanismo de @extends de Blade)
        $value .= $this->extendsFooter;

        return $this->restoreVerbatim($value);
    }

    /**
     * nombres de vistas referenciadas por la ultima compilacion,
     * usados por el motor para la invalidacion del cache por dependencias
     *
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return $this->viewDependencies;
    }

    /**
     * registra una vista referenciada si el argumento es un literal entre comillas
     */
    protected function recordDependency(?string $viewExpression): void
    {
        if ($viewExpression !== null && preg_match('/^([\'"])(.+)\1$/', $viewExpression, $match)) {
            $this->viewDependencies[] = $match[2];
        }
    }

    /**
     * reemplaza un bloque delimitado por directivas usando el BlockMatcher
     *
     * @param callable(string, string): string $make recibe (expresion, cuerpo) y retorna el reemplazo
     */
    protected function compileBlock(string $value, string $open, string $close, bool $requireExpression, callable $make): string
    {
        while (($block = BlockMatcher::find($value, $open, $close)) !== null) {
            $extract = $this->extractParenExpression($block['body']);

            if ($extract === null) {
                if ($requireExpression) {
                    throw ViewCompileException::forView('', "La directiva @{$open} requiere una expresion entre parentesis");
                }

                $expression = null;
                $body = $block['body'];
            } else {
                $expression = $extract['expr'];
                $body = $extract['rest'];
            }

            $replacement = $make($expression, $body);

            $value = substr($value, 0, $block['start']) . $replacement . substr($value, $block['end']);
        }

        return $value;
    }

    /**
     * reemplaza directivas sueltas (token) por codigo PHP.
     * extrae la expresion parentizada por balance de parens/comillas,
     * por lo que soporta condiciones con llamadas anidadas o strings.
     *
     * @param callable(?string): ?string $make recibe la expresion (o null) y retorna el reemplazo;
     *                                          si retorna null, el texto original se conserva
     */
    protected function compileTokenDirective(string $value, string $name, callable $make, bool $requireExpression = false): string
    {
        $pattern = '/@' . preg_quote($name, '/') . '(?![\w-])/';
        $result = '';
        $offset = 0;
        $searchFrom = 0;

        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE, $searchFrom)) {
            $matchStart = $match[0][1];
            $matchEnd = $matchStart + strlen($match[0][0]);

            $extract = $this->extractParenExpression(substr($value, $matchEnd));

            if ($extract === null) {
                if ($requireExpression) {
                    throw ViewCompileException::forView('', "La directiva @{$name} requiere una expresion entre parentesis");
                }

                $replacement = $make(null);
                $consumed = $matchEnd;
            } else {
                $replacement = $make($extract['expr']);
                $consumed = $matchEnd + $extract['consumed'];
            }

            if ($replacement === null) {
                //no reemplazar: conservar el texto original y seguir buscando
                $searchFrom = $matchEnd;
                continue;
            }

            $result .= substr($value, $offset, $matchStart - $offset) . $replacement;
            $offset = $consumed;
            $searchFrom = $consumed;
        }

        return $result . substr($value, $offset);
    }

    /**
     * extraer una expresion parentizada completa por balance de parentesis,
     * respetando strings entre comillas simples y dobles.
     *
     * @return array{expr:string, rest:string, consumed:int}|null
     *         consumed: caracteres consumidos desde el inicio de $text (hasta el ')' inclusive)
     */
    protected function extractParenExpression(string $text): ?array
    {
        $length = strlen($text);
        $i = 0;

        while ($i < $length && str_contains(" \t\r\n", $text[$i])) {
            $i++;
        }

        if ($i >= $length || $text[$i] !== '(') {
            return null;
        }

        $openParen = $i;
        $depth = 0;
        $inString = null;

        for (; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString !== null) {
                if ($char === '\\') {
                    $i++;
                    continue;
                }

                if ($char === $inString) {
                    $inString = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $inString = $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return [
                        'expr' => trim(substr($text, $openParen + 1, $i - $openParen - 1)),
                        'rest' => substr($text, $i + 1),
                        'consumed' => $i + 1,
                    ];
                }
            }
        }

        return null;
    }

    protected function compileEscapedDirectives(string $value): string
    {
        return preg_replace_callback('/@@/', function () {
            $placeholder = sprintf(self::PLACEHOLDER_ESCAPED, count($this->escapedSegments));
            $this->escapedSegments[] = '@';

            return $placeholder;
        }, $value) ?? $value;
    }

    protected function restoreEscaped(string $value): string
    {
        foreach ($this->escapedSegments as $i => $segment) {
            $value = str_replace(sprintf(self::PLACEHOLDER_ESCAPED, $i), $segment, $value);
        }

        return $value;
    }

    protected function compileVerbatim(string $value): string
    {
        while (($block = BlockMatcher::find($value, 'verbatim', 'endverbatim')) !== null) {
            $placeholder = sprintf(self::PLACEHOLDER_VERBATIM, count($this->verbatimSegments));
            $this->verbatimSegments[] = $block['body'];

            $value = substr($value, 0, $block['start']) . $placeholder . substr($value, $block['end']);
        }

        return $value;
    }

    protected function restoreVerbatim(string $value): string
    {
        foreach ($this->verbatimSegments as $i => $segment) {
            $value = str_replace(sprintf(self::PLACEHOLDER_VERBATIM, $i), $segment, $value);
        }

        return $value;
    }

    protected function compileComments(string $value): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $value) ?? $value;
    }

    /**
     * captura @extends('layout') y lo elimina del flujo.
     * la llamada de renderizado del layout se anexa al footer de la vista,
     * de modo que el padre se renderiza al final, cuando las secciones
     * de la vista hija ya fueron capturadas. si hay varios @extends, gana el ultimo.
     *
     * el footer excluye las variables internas del scope de renderizado
     * (__viewPath, __viewData, __viewEnv) para que el extract() del layout
     * no pueda colisionar con ellas.
     */
    protected function compileExtends(string $value): string
    {
        $this->extendsFooter = '';

        return preg_replace_callback(
            '/@extends\s*\(\s*([\'"])([^\'"]+)\1\s*\)/',
            function (array $match): string {
                $view = str_replace(['\\', '\''], ['\\\\', '\\\''], $match[2]);
                $this->viewDependencies[] = $match[2];
                $this->extendsFooter = "<?php echo \$__env->makeView('{$view}', "
                    . 'array_diff_key(get_defined_vars(), ' . self::INTERNAL_VIEW_VARS_FILTER . ')); ?>';

                return '';
            },
            $value
        ) ?? $value;
    }

    /**
     * compila la directiva de seccion en sus dos formas y sus cierres:
     * forma corta `section('nombre', contenido)` (expresion evaluada al renderizar)
     * y bloque `section('nombre')` cerrado por endsection, stop, show u overwrite.
     */
    protected function compileSections(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'section', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @section requiere una expresion entre parentesis');
            }

            [$name, $content] = $this->splitTopLevelArguments($expr, 'section');

            return $content === null
                ? "<?php \$__env->startSection({$name}); ?>"
                : "<?php \$__env->startSection({$name}, {$content}); ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'endsection', fn () => '<?php $__env->stopSection(); ?>');
        $value = $this->compileTokenDirective($value, 'stop', fn () => '<?php $__env->stopSection(); ?>');
        $value = $this->compileTokenDirective($value, 'show', fn () => '<?php $__env->showSection(); ?>');
        $value = $this->compileTokenDirective($value, 'overwrite', fn () => '<?php $__env->stopSection(true); ?>');

        return $value;
    }

    /**
     * compila @yield con default, @hasSection y @sectionMissing
     */
    protected function compileYield(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'yield', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @yield requiere una expresion entre parentesis');
            }

            [$name, $default] = $this->splitTopLevelArguments($expr, 'yield');

            $defaultArgument = $default ?? "''";

            return "<?php echo \$__env->yieldSection({$name}, {$defaultArgument}); ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'hasSection', fn (?string $expr) => "<?php if (\$__env->hasSection({$expr})): ?>", true);
        $value = $this->compileTokenDirective($value, 'sectionMissing', fn (?string $expr) => "<?php if (\$__env->missingSection({$expr})): ?>", true);

        return $value;
    }

    /**
     * compila los stacks: push y prepend en forma corta o bloque,
     * y stack para imprimir el contenido acumulado en ese punto del render.
     */
    protected function compileStacks(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'push', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @push requiere una expresion entre parentesis');
            }

            [$name, $content] = $this->splitTopLevelArguments($expr, 'push');

            return $content === null
                ? "<?php \$__env->startPush({$name}); ?>"
                : "<?php \$__env->startPush({$name}, {$content}); ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'endpush', fn () => '<?php $__env->stopPush(); ?>');

        $value = $this->compileTokenDirective($value, 'prepend', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @prepend requiere una expresion entre parentesis');
            }

            [$name, $content] = $this->splitTopLevelArguments($expr, 'prepend');

            return $content === null
                ? "<?php \$__env->startPrepend({$name}); ?>"
                : "<?php \$__env->startPrepend({$name}, {$content}); ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'endprepend', fn () => '<?php $__env->stopPrepend(); ?>');

        $value = $this->compileTokenDirective($value, 'stack', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @stack requiere una expresion entre parentesis');
            }

            return "<?php echo \$__env->yieldStack({$expr}); ?>";
        }, true);

        return $value;
    }

    /**
     * compila los includes como llamadas en runtime (no inlining), lo que
     * soporta includes anidados, nombres dinamicos y cache por dependencias:
     * include, includeIf, includeWhen, includeUnless y each.
     *
     * precedencia de variables estilo Laravel: los datos pasados pisan
     * a las variables del scope actual.
     */
    protected function compileIncludes(string $value): string
    {
        $scopeFilter = 'array_diff_key(get_defined_vars(), ' . self::INTERNAL_VIEW_VARS_FILTER . ')';

        $value = $this->compileTokenDirective($value, 'include', function (?string $expr) use ($scopeFilter) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @include requiere una expresion entre parentesis');
            }

            [$view, $data] = $this->splitTopLevelArguments($expr, 'include');

            $this->recordDependency($view);
            $dataArgument = $data !== null ? "({$data}) + {$scopeFilter}" : $scopeFilter;

            return "<?php echo \$__env->makeView({$view}, {$dataArgument}); ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'includeIf', function (?string $expr) use ($scopeFilter) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @includeIf requiere una expresion entre parentesis');
            }

            [$view, $data] = $this->splitTopLevelArguments($expr, 'includeIf');

            $this->recordDependency($view);
            $dataArgument = $data !== null ? "({$data}) + {$scopeFilter}" : $scopeFilter;

            return "<?php if (\$__env->viewExists({$view})) { echo \$__env->makeView({$view}, {$dataArgument}); } ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'includeWhen', fn (?string $expr) => $this->compileConditionalInclude($expr, 'includeWhen', false, $scopeFilter), true);

        $value = $this->compileTokenDirective($value, 'includeUnless', fn (?string $expr) => $this->compileConditionalInclude($expr, 'includeUnless', true, $scopeFilter), true);

        $value = $this->compileTokenDirective($value, 'each', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @each requiere una expresion entre parentesis');
            }

            $parts = $this->splitAllTopLevelArguments($expr, 'each');

            if (count($parts) < 3) {
                throw ViewCompileException::forView('', 'La directiva @each requiere vista, coleccion y nombre de variable');
            }

            $view = $parts[0];
            $iterable = $parts[1];
            $variable = $parts[2];
            $emptyView = $parts[3] ?? 'null';
            $extraData = $parts[4] ?? '[]';

            $this->recordDependency($view);
            $this->recordDependency($emptyView !== 'null' ? $emptyView : null);

            return "<?php echo \$__env->renderEach({$view}, {$iterable}, {$variable}, {$emptyView}, {$extraData}); ?>";
        }, true);

        return $value;
    }

    /**
     * genera el codigo de includeWhen (condicion positiva) e includeUnless (negada)
     */
    protected function compileConditionalInclude(?string $expr, string $directive, bool $negate, string $scopeFilter): string
    {
        if ($expr === null) {
            throw ViewCompileException::forView('', "La directiva @{$directive} requiere una expresion entre parentesis");
        }

        $parts = $this->splitAllTopLevelArguments($expr, $directive);

        if (count($parts) < 2) {
            throw ViewCompileException::forView('', "La directiva @{$directive} requiere condicion y vista");
        }

        [$condition, $view] = $parts;
        $data = $parts[2] ?? null;

        $this->recordDependency($view);
        $dataArgument = $data !== null ? "({$data}) + {$scopeFilter}" : $scopeFilter;
        $operator = $negate ? '!' : '';

        return "<?php if ({$operator}({$condition})) { echo \$__env->makeView({$view}, {$dataArgument}); } ?>";
    }

    /**
     * compila @once y @pushOnce: el contenido se renderiza una sola vez por
     * render (clave = nombre de vista, y nombre de stack para pushOnce)
     */
    protected function compileOnce(string $value, string $viewName): string
    {
        //cada bloque @once de la misma vista necesita una clave propia
        $blockIndex = 0;

        while (($block = BlockMatcher::find($value, 'once', 'endonce')) !== null) {
            $key = var_export($viewName . '#' . (++$blockIndex), true);
            $replacement = "<?php if (\$__env->beginOnce({$key})): ?>"
                . $block['body']
                . '<?php $__env->endOnce(); endif; ?>';

            $value = substr($value, 0, $block['start']) . $replacement . substr($value, $block['end']);
        }

        while (($block = BlockMatcher::find($value, 'pushOnce', 'endPushOnce')) !== null) {
            $extract = $this->extractParenExpression($block['body']);

            if ($extract === null) {
                throw ViewCompileException::forView('', 'La directiva @pushOnce requiere el nombre del stack entre parentesis');
            }

            $stack = trim($extract['expr']);
            $key = var_export($viewName . ':', true) . " . {$stack}";

            $replacement = "<?php if (\$__env->beginPushOnce({$key}, {$stack})): ?>"
                . $extract['rest']
                . '<?php $__env->endPushOnce(); endif; ?>';

            $value = substr($value, 0, $block['start']) . $replacement . substr($value, $block['end']);
        }

        return $value;
    }

    /**
     * compila @csrf, @method y @error/@enderror sobre la sesion del proyecto
     */
    protected function compileFormSecurity(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'csrf', fn () => '<?php echo \'<input type="hidden" name="_token" value="\' . e(csrf_token()) . \'">\'; ?>');

        $value = $this->compileTokenDirective($value, 'method', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @method requiere el verbo HTTP entre parentesis');
            }

            $verb = strtoupper(trim($expr, '\'" '));

            if (!in_array($verb, ['PUT', 'PATCH', 'DELETE'], true)) {
                throw ViewCompileException::forView('', "Verbo HTTP no soportado por @method: [{$verb}]");
            }

            return "<?php echo '<input type=\"hidden\" name=\"_method\" value=\"{$verb}\">'; ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'error', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @error requiere el nombre del campo entre parentesis');
            }

            return "<?php if ((\$message = session()->error({$expr})) !== null): ?>";
        }, true);

        $value = $this->compileTokenDirective($value, 'enderror', fn () => '<?php unset($message); endif; ?>');

        return $value;
    }

    /**
     * compila @asset('ruta') al helper asset() con versionado
     */
    protected function compileAsset(string $value): string
    {
        return $this->compileTokenDirective($value, 'asset', fn (?string $expr) => "<?php echo asset({$expr}); ?>", true);
    }

    /**
     * compila @dump y @dd para debug en plantillas
     */
    protected function compileDebug(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'dump', fn (?string $expr) => "<?php var_dump({$expr}); ?>", true);
        $value = $this->compileTokenDirective($value, 'dd', fn (?string $expr) => "<?php var_dump({$expr}); exit(1); ?>", true);

        return $value;
    }

    /**
     * compila las directivas personalizadas registradas con directive()
     */
    protected function compileCustomDirectives(string $value): string
    {
        foreach (static::$customDirectives as $name => $handler) {
            $value = $this->compileTokenDirective($value, $name, fn (?string $expr) => $handler($expr), false);
        }

        return $value;
    }

    /**
     * compila la declaracion de props de un componente: extrae las props
     * declaradas (con defaults) al scope y el resto queda en $attributes
     */
    protected function compileProps(string $value): string
    {
        return $this->compileTokenDirective($value, 'props', function (?string $expr) {
            if ($expr === null) {
                throw ViewCompileException::forView('', 'La directiva @props requiere una expresion entre parentesis');
            }

            return '<?php $__resolved = $__env->resolveProps(array_diff_key(get_defined_vars(), '
                . "['__viewPath' => 1, '__viewData' => 1, '__viewEnv' => 1, '__env' => 1, '__slots' => 1, 'slot' => 1, '__resolved' => 1]), "
                . "{$expr}); "
                . "extract(\$__resolved['props'], EXTR_OVERWRITE); "
                . '$attributes = $__resolved[\'attributes\']; '
                . 'unset($__resolved); ?>';
        }, true);
    }

    /**
     * atributos condicionales: @class, @style, @checked, @selected,
     * @disabled, @readonly y @required
     */
    protected function compileConditionalAttributes(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'class', fn (?string $expr) => "<?php echo \$__env->classList({$expr}); ?>", true);
        $value = $this->compileTokenDirective($value, 'style', fn (?string $expr) => "<?php echo \$__env->styleList({$expr}); ?>", true);

        foreach (['checked', 'selected', 'disabled', 'readonly', 'required'] as $attribute) {
            $value = $this->compileTokenDirective(
                $value,
                $attribute,
                fn (?string $expr) => "<?php if ({$expr}) { echo '{$attribute}'; } ?>",
                true
            );
        }

        return $value;
    }

    // ── componentes x-* ───────────────────────────────────

    /**
     * compila etiquetas de componentes anonimos <x-nombre> (con contenido o
     * auto-cerradas) y <x-dynamic-component>. las etiquetas se emparejan por
     * balance, por lo que los componentes del mismo nombre pueden anidarse.
     *
     * los slots nombrados (<x-slot:nombre>) se capturan por buffer y el
     * contenido permanece en el stream, por lo que las directivas y los
     * echo del slot se compilan en pasadas posteriores con el scope del padre.
     */
    protected function compileXComponents(string $value): string
    {
        $result = '';
        $offset = 0;
        $pattern = '/<x-([\w.-]+)\b([^>]*?)(\/?)>/';

        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $name = $match[1][0];
            $attrs = $match[2][0];
            $selfClosing = $match[3][0] === '/';
            $tagStart = $match[0][1];
            $tagEnd = $tagStart + strlen($match[0][0]);

            if (!$selfClosing) {
                $close = $this->findXTagClose($value, $name, $tagEnd);

                if ($close !== null) {
                    $content = substr($value, $tagEnd, $close['start'] - $tagEnd);

                    $result .= substr($value, $offset, $tagStart - $offset)
                        . $this->buildComponent($name, $attrs, $content);
                    $offset = $close['end'];

                    continue;
                }
            }

            //auto-cerrada o sin cierre conocido: self-closing emite componente,
            //sin cierre se deja como texto (posible web component nativo)
            $replacement = $selfClosing
                ? $this->buildComponent($name, $attrs, '')
                : substr($value, $tagStart, $tagEnd - $tagStart);

            $result .= substr($value, $offset, $tagStart - $offset) . $replacement;
            $offset = $tagEnd;
        }

        return $result . substr($value, $offset);
    }

    /**
     * localiza el cierre </x-name> correspondiente contando aperturas anidadas
     *
     * @return array{start:int, end:int}|null
     */
    protected function findXTagClose(string $value, string $name, int $from): ?array
    {
        $pattern = '/<(\/?)x-' . preg_quote($name, '/') . '\b([^>]*?)(\/?)>/';
        $depth = 1;
        $pos = $from;

        while (preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE, $pos)) {
            $isClose = $match[1][0] === '/';
            $isSelfClosing = $match[3][0] === '/';
            $start = $match[0][1];
            $end = $start + strlen($match[0][0]);

            if ($isClose) {
                $depth--;

                if ($depth === 0) {
                    return ['start' => $start, 'end' => $end];
                }
            } elseif (!$isSelfClosing) {
                $depth++;
            }

            $pos = $end;
        }

        return null;
    }

    /**
     * genera el codigo PHP de un componente: captura de slots + llamada a makeComponent.
     *
     * la captura usa ob_start/ob_get_clean SECUENCIALES en el scope del include
     * (no dentro de closures) para que el contenido del slot vea las variables
     * del padre; cada slot se guarda en una variable temporal con sufijo unico
     * para que los componentes anidados no se pisen. el contenido de cada slot
     * se compila recursivamente para soportar componentes dentro de slots.
     */
    protected function buildComponent(string $name, string $attrs, string $content): string
    {
        $slots = [];
        $defaultContent = preg_replace_callback(
            '/<x-slot:([\w.-]+)\s*>(.*?)<\/x-slot:\1>/s',
            function (array $match) use (&$slots): string {
                $slots[$match[1]] = $match[2];

                return '';
            },
            $content
        ) ?? $content;

        $slots['default'] = $defaultContent;

        $view = 'components/' . str_replace('.', '/', $name);
        $this->viewDependencies[] = $view;
        $props = $this->exportXProps($this->parseXAttributes($attrs));
        $suffix = '_' . (++$this->componentCounter);

        $code = '<?php ob_start(); ?>';
        $slotVars = [];
        $index = 0;

        foreach ($slots as $slotName => $slotContent) {
            if ($slotName === 'default') {
                continue;
            }

            //los slots nombrados se compilan recursivamente (componentes anidados)
            //y se capturan en orden, dejando el buffer listo para el siguiente
            $code .= $this->compileXComponents($slotContent);

            $tempVar = '$__cs' . $suffix . '_' . (++$index);
            $code .= '<?php ' . $tempVar . ' = ob_get_clean(); ob_start(); ?>';
            $slotVars[$slotName] = $tempVar;
        }

        $defaultVar = '$__cs' . $suffix . '_0';
        $code .= $this->compileXComponents($slots['default']);
        $code .= '<?php ' . $defaultVar . ' = ob_get_clean(); ?>';
        $slotVars['default'] = $defaultVar;

        $slotArray = '[';
        $first = true;

        foreach ($slotVars as $slotName => $tempVar) {
            $slotArray .= ($first ? '' : ', ') . var_export($slotName, true) . ' => ' . $tempVar;
            $first = false;
        }

        $slotArray .= ']';

        if ($name === 'dynamic-component') {
            $code .= '<?php $__cProps' . $suffix . ' = ' . $props . '; ';
            $code .= "if (isset(\$__cProps{$suffix}['component'])) { ";
            $code .= '$__cName' . $suffix . " = \$__cProps{$suffix}['component']; ";
            $code .= 'if (!str_contains($__cName' . $suffix . ", '/')) { ";
            $code .= '$__cName' . $suffix . " = 'components/' . \$__cName{$suffix}; } ";
            $code .= 'echo $__env->makeComponent($__cName' . $suffix . ', $__cProps' . $suffix . ', ' . $slotArray . '); } ?>';

            return $code;
        }

        $code .= '<?php echo $__env->makeComponent(' . var_export($view, true) . ', ' . $props . ', ' . $slotArray . '); ?>';

        return $code;
    }

    /**
     * parsea los atributos de un tag x- a un array nombre => expresion PHP.
     *   :attr="expresion"   → expresion evaluada en el scope del padre
     *   attr="valor"        → string literal ({{ expr }} completa se compila a e())
     *   attr                → true
     *
     * @return array<string, string> nombre => expresion PHP
     */
    protected function parseXAttributes(string $attrs): array
    {
        $props = [];
        $remaining = trim($attrs);

        if ($remaining === '') {
            return $props;
        }

        // bindings :attr="expr" y :attr='expr' (se procesan primero)
        foreach (['"', '\''] as $quote) {
            $pattern = '/:([\w.-]+)\s*=\s*' . $quote . '([^' . $quote . ']*)' . $quote . '/';

            if (preg_match_all($pattern, $remaining, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $props[$m[1]] = trim($m[2]);
                    $remaining = str_replace($m[0], '', $remaining);
                }
            }
        }

        // atributos con valor, dobles y simples
        foreach (['"', '\''] as $quote) {
            $pattern = '/([\w.-]+)\s*=\s*' . $quote . '([^' . $quote . ']*)' . $quote . '/';

            if (preg_match_all($pattern, $remaining, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $props[$m[1]] = $this->compileAttrValue($m[2]);
                    $remaining = str_replace($m[0], '', $remaining);
                }
            }
        }

        // atributos booleanos restantes
        if (preg_match_all('/[\w.-]+/', $remaining, $matches)) {
            foreach ($matches[0] as $attr) {
                if ($attr !== '' && !isset($props[$attr])) {
                    $props[$attr] = 'true';
                }
            }
        }

        return $props;
    }

    /**
     * convierte el valor de un atributo a expresion PHP:
     * {{ expr }} completa → e(expr); cualquier otra cosa → string literal
     */
    protected function compileAttrValue(string $value): string
    {
        if (preg_match('/^\{\{\s*(.*?)\s*\}\}$/', $value, $match)) {
            return "e({$match[1]})";
        }

        return var_export($value, true);
    }

    /**
     * exporta el array de props (nombre => expresion PHP) a codigo PHP
     */
    protected function exportXProps(array $props): string
    {
        if ($props === []) {
            return '[]';
        }

        $parts = [];

        foreach ($props as $name => $expression) {
            $parts[] = var_export($name, true) . ' => ' . $expression;
        }

        return '[' . implode(', ', $parts) . ']';
    }

    /**
     * separa los argumentos de una directiva por comas al nivel superior
     * (respetando strings y parentesis/corchetes anidados)
     *
     * @return array{0:string, 1:?string} [primerArgumento, resto|null]
     */
    protected function splitTopLevelArguments(string $expression, string $directive): array
    {
        $parts = $this->splitAllTopLevelArguments($expression, $directive);

        return [$parts[0], $parts[1] ?? null];
    }

    /**
     * separa TODOS los argumentos de una directiva por comas al nivel superior
     * (respetando strings y parentesis/corchetes anidados)
     *
     * @return list<string>
     */
    protected function splitAllTopLevelArguments(string $expression, string $directive): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = null;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($inString !== null) {
                $current .= $char;

                if ($char === '\\') {
                    $current .= $expression[++$i] ?? '';
                    continue;
                }

                if ($char === $inString) {
                    $inString = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $inString = $char;
                $current .= $char;
                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $parts[] = trim($current);

        if ($parts[0] === '') {
            throw ViewCompileException::forView('', "La directiva @{$directive} requiere un nombre de seccion");
        }

        return $parts;
    }

    protected function compilePhpBlock(string $value): string
    {
        return $this->compileBlock($value, 'php', 'endphp', false, fn (?string $expr, string $body) => "<?php {$body} ?>");
    }

    protected function compileInlinePhp(string $value): string
    {
        return $this->compileTokenDirective($value, 'php', fn ($expr) => $expr === null ? null : "<?php {$expr}; ?>", false);
    }

    protected function compileJson(string $value): string
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

        return $this->compileTokenDirective(
            $value,
            'json',
            fn ($expr) => $expr === null ? null : "<?php echo json_encode({$expr}, {$flags}); ?>",
            true
        );
    }

    protected function compileUnset(string $value): string
    {
        return $this->compileTokenDirective($value, 'unset', fn ($expr) => "<?php unset({$expr}); ?>", true);
    }

    protected function compileRawEcho(string $value): string
    {
        return preg_replace('/\{!!\s*(.*?)\s*!!\}/s', '<?php echo $1; ?>', $value) ?? $value;
    }

    protected function compileEcho(string $value): string
    {
        return preg_replace('/\{\{\s*(.*?)\s*\}\}/s', '<?php echo e($1); ?>', $value) ?? $value;
    }

    protected function compileBreakContinue(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'break', fn ($expr) => $expr === null ? '<?php break; ?>' : "<?php if ({$expr}) break; ?>");
        $value = $this->compileTokenDirective($value, 'continue', fn ($expr) => $expr === null ? '<?php continue; ?>' : "<?php if ({$expr}) continue; ?>");

        return $value;
    }

    protected function compileSwitch(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'switch', fn ($expr) => "<?php switch ({$expr}): ?>", true);
        $value = $this->compileTokenDirective($value, 'case', fn ($expr) => "<?php case {$expr}: ?>", true);
        $value = $this->compileTokenDirective($value, 'default', fn ($expr) => '<?php default: ?>');
        $value = $this->compileTokenDirective($value, 'endswitch', fn ($expr) => '<?php endswitch; ?>');

        return $value;
    }

    protected function compileConditionals(string $value): string
    {
        $value = $this->compileTokenDirective($value, 'elseif', fn ($expr) => "<?php elseif ({$expr}): ?>", true);
        $value = $this->compileTokenDirective($value, 'if', fn ($expr) => "<?php if ({$expr}): ?>", true);
        $value = $this->compileTokenDirective($value, 'else', fn ($expr) => '<?php else: ?>');
        $value = $this->compileTokenDirective($value, 'endif', fn ($expr) => '<?php endif; ?>');

        $value = $this->compileTokenDirective($value, 'unless', fn ($expr) => "<?php if (!({$expr})): ?>", true);
        $value = $this->compileTokenDirective($value, 'endunless', fn ($expr) => '<?php endif; ?>');

        $value = $this->compileTokenDirective($value, 'isset', fn ($expr) => "<?php if (isset({$expr})): ?>", true);
        $value = $this->compileTokenDirective($value, 'endisset', fn ($expr) => '<?php endif; ?>');

        $value = $this->compileTokenDirective($value, 'empty', fn ($expr) => "<?php if (empty({$expr})): ?>", true);
        $value = $this->compileTokenDirective($value, 'endempty', fn ($expr) => '<?php endif; ?>');

        $value = $this->compileTokenDirective($value, 'auth', fn ($expr) => '<?php if (session()->hasUser()): ?>');
        $value = $this->compileTokenDirective($value, 'endauth', fn ($expr) => '<?php endif; ?>');
        $value = $this->compileTokenDirective($value, 'guest', fn ($expr) => '<?php if (!session()->hasUser()): ?>');
        $value = $this->compileTokenDirective($value, 'endguest', fn ($expr) => '<?php endif; ?>');

        return $value;
    }

    protected function compileFor(string $value): string
    {
        return $this->compileBlock(
            $value,
            'for',
            'endfor',
            true,
            fn (string $expr, string $body) => "<?php for ({$expr}): ?>" . $body . '<?php endfor; ?>'
        );
    }

    protected function compileWhile(string $value): string
    {
        return $this->compileBlock(
            $value,
            'while',
            'endwhile',
            true,
            fn (string $expr, string $body) => "<?php while ({$expr}): ?>" . $body . '<?php endwhile; ?>'
        );
    }

    /**
     * compilar @foreach con variable $loop completa y soporte de anidamiento.
     *
     * cada nivel de profundidad genera variables con sufijo unico
     * ($__dataF0, $__dataF1...) para que los loops anidados no se pisen.
     */
    protected function compileForeach(string $value, int $depth = 0): string
    {
        while (($block = BlockMatcher::find($value, 'foreach', 'endforeach')) !== null) {
            $extract = $this->extractParenExpression($block['body']);

            if ($extract === null) {
                throw ViewCompileException::forView('', 'La directiva @foreach requiere una expresion entre parentesis');
            }

            [$iterable, $keyVar, $valueVar] = $this->parseLoopExpression($extract['expr'], 'foreach');

            //compilar primero los @foreach anidados del cuerpo
            $body = $this->compileForeach($extract['rest'], $depth + 1);

            $replacement = $this->makeLoopWrapper($depth, 'F', $iterable, $keyVar, $valueVar, $body);

            $value = substr($value, 0, $block['start']) . $replacement . substr($value, $block['end']);
        }

        return $value;
    }

    /**
     * compilar @forelse ... @empty ... @endforelse
     * la iteracion usa la familia de variables "E" para no colisionar con los @foreach ("F").
     */
    protected function compileForelse(string $value): string
    {
        while (($block = BlockMatcher::find($value, 'forelse', 'endforelse')) !== null) {
            $extract = $this->extractParenExpression($block['body']);

            if ($extract === null) {
                throw ViewCompileException::forView('', 'La directiva @forelse requiere una expresion entre parentesis');
            }

            [$iterable, $keyVar, $valueVar] = $this->parseLoopExpression($extract['expr'], 'forelse');

            $empty = $this->findForelseEmpty($extract['rest']);

            if ($empty === null) {
                throw ViewCompileException::forView('', 'La directiva @forelse requiere un @empty');
            }

            $loopBody = $this->compileForeach(substr($extract['rest'], 0, $empty['start']), 0);
            $emptyBody = substr($extract['rest'], $empty['end']);

            $loopWrapper = $this->makeLoopWrapper(0, 'E', '$__dataE0', $keyVar, $valueVar, $loopBody);

            $replacement = "<?php \$__dataE0 = {$iterable}; ?>"
                . '<?php if (empty($__dataE0)): ?>'
                . $emptyBody
                . '<?php else: ?>'
                . $loopWrapper
                . '<?php endif; ?>';

            $value = substr($value, 0, $block['start']) . $replacement . substr($value, $block['end']);
        }

        return $value;
    }

    /**
     * localizar el @empty que pertenece al @forelse actual
     * (el primero que no este dentro de otro @forelse anidado)
     *
     * @return array{start:int, end:int}|null
     */
    protected function findForelseEmpty(string $body): ?array
    {
        $pattern = '/@(forelse|endforelse|empty)(?![\w-])/';
        $depth = 0;
        $pos = 0;

        while (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE, $pos)) {
            $name = $match[1][0];
            $tokenStart = $match[0][1];
            $tokenEnd = $tokenStart + strlen($match[0][0]);

            if ($name === 'forelse') {
                $depth++;
                $pos = $tokenEnd;
                continue;
            }

            if ($name === 'endforelse') {
                $depth--;
                $pos = $tokenEnd;
                continue;
            }

            if ($depth === 0) {
                return ['start' => $tokenStart, 'end' => $tokenEnd];
            }

            $pos = $tokenEnd;
        }

        return null;
    }

    /**
     * separar "coleccion as $clave => $valor" en sus partes
     *
     * @return array{0:string, 1:?string, 2:string} [iterable, clave|null, valor]
     */
    protected function parseLoopExpression(string $expression, string $directive): array
    {
        $parts = preg_split('/\s+as\s+/i', $expression, 2);

        if ($parts === false || count($parts) !== 2) {
            throw ViewCompileException::forView('', "La expresion @{$directive}({$expression}) debe tener la forma 'coleccion as variable'");
        }

        $iterable = trim($parts[0]);
        $target = trim($parts[1]);

        if (preg_match('/^(&?)\$\w+\s*=>\s*(&?)\$\w+$/', $target, $match)) {
            $arrowPos = strpos($target, '=>');
            $key = trim(substr($target, 0, $arrowPos));
            $value = trim(substr($target, $arrowPos + 2));

            return [$iterable, $key, $value];
        }

        if (!preg_match('/^(&?)\$\w+$/', $target)) {
            throw ViewCompileException::forView('', "La variable de iteracion [{$target}] no es valida");
        }

        return [$iterable, null, $target];
    }

    /**
     * generar el codigo PHP del loop con soporte de $loop
     *
     * @param string $family familia de variables para no colisionar entre tipos de loop ('F' foreach, 'E' forelse)
     */
    protected function makeLoopWrapper(int $depth, string $family, string $iterable, ?string $keyVar, string $valueVar, string $body): string
    {
        $d = $depth . $family;
        $keyPart = $keyVar !== null ? "{$keyVar} => " : '';

        return '<?php '
            . "\$__parentLoop{$d} = \$loop ?? null; "
            . "\$__data{$d} = {$iterable}; "
            . "\$__count{$d} = is_countable(\$__data{$d}) ? count(\$__data{$d}) : 0; "
            . "\$__i{$d} = -1; "
            . "foreach (\$__data{$d} as {$keyPart}{$valueVar}): "
            . "\$__i{$d}++; "
            . "\$loop = (object) ["
            . "'index' => \$__i{$d}, "
            . "'iteration' => \$__i{$d} + 1, "
            . "'count' => \$__count{$d}, "
            . "'remaining' => \$__count{$d} - (\$__i{$d} + 1), "
            . "'first' => \$__i{$d} === 0, "
            . "'last' => \$__i{$d} === \$__count{$d} - 1, "
            . "'parent' => \$__parentLoop{$d}, "
            . ']; ?>'
            . $body
            . "<?php endforeach; \$loop = \$__parentLoop{$d}; ?>";
    }
}
