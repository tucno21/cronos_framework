<?php

namespace Cronos\View\Compiler;

use Cronos\View\Exceptions\ViewCompileException;

class BladeCompiler
{
    protected const PLACEHOLDER_VERBATIM = '__CRONOS_VERBATIM_%d__';
    protected const PLACEHOLDER_ESCAPED = '__CRONOS_ESCAPED_%d__';

    protected array $verbatimSegments = [];
    protected array $escapedSegments = [];

    public function compileString(string $value): string
    {
        $this->verbatimSegments = [];
        $this->escapedSegments = [];

        $value = $this->compileEscapedDirectives($value);
        $value = $this->compileVerbatim($value);
        $value = $this->compileComments($value);
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

        return $this->restoreVerbatim($value);
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
