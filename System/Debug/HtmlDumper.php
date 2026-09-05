<?php

declare(strict_types=1);

namespace Cronos\Debug;

use Closure;
use ReflectionClass;
use Cronos\Model\Model;
use Cronos\Model\ModelCollection;

/**
 * Renderizador de variables interactivo para navegador web (tema oscuro tipo Laravel/Symfony).
 */
class HtmlDumper
{
    private static bool $assetsRendered = false;

    public function render(array $vars, ?string $caller = null): string
    {
        $out = '';
        if (!self::$assetsRendered) {
            $out .= $this->getStylesAndScripts();
            self::$assetsRendered = true;
        }

        $out .= '<div class="cronos-dump-container">';
        if ($caller) {
            $out .= '<div class="cronos-dump-header">';
            $out .= '<span class="cronos-dump-badge">📍 ' . htmlspecialchars($caller, ENT_QUOTES, 'UTF-8') . '</span>';
            $out .= '<div class="cronos-dump-actions">';
            $out .= '<button type="button" class="cronos-btn" onclick="cronosToggleAll(this, true)">Expandir</button>';
            $out .= '<button type="button" class="cronos-btn" onclick="cronosToggleAll(this, false)">Colapsar</button>';
            $out .= '</div>';
            $out .= '</div>';
        }

        foreach ($vars as $var) {
            $out .= '<div class="cronos-dump-body">' . $this->dump($var, 0) . '</div>';
        }
        $out .= '</div>';

        return $out;
    }

    public function dump(mixed $var, int $depth = 0, int $maxDepth = 6): string
    {
        if ($depth >= $maxDepth) {
            return '<span class="c-gray">/* Límite de profundidad */</span>';
        }

        if (is_null($var)) {
            return '<span class="c-null">null</span>';
        }

        if (is_bool($var)) {
            return '<span class="c-bool">' . ($var ? 'true' : 'false') . '</span>';
        }

        if (is_int($var)) {
            return '<span class="c-num">' . $var . '</span>';
        }

        if (is_float($var)) {
            return '<span class="c-num">' . $var . '</span>';
        }

        if (is_string($var)) {
            $len = strlen($var);
            return '<span class="c-str">"' . htmlspecialchars($var, ENT_QUOTES, 'UTF-8') . '"</span> <span class="c-len">(' . $len . ')</span>';
        }

        if (is_array($var)) {
            $count = count($var);
            if ($count === 0) {
                return '<span class="c-bracket">[]</span> <span class="c-gray">(vacío)</span>';
            }

            $open = $depth < 2 ? 'open' : '';
            $out = '<details class="c-tree" ' . $open . '>';
            $out .= '<summary class="c-summary"><span class="c-bracket">array:' . $count . '</span> [</summary>';
            $out .= '<div class="c-children">';

            foreach ($var as $key => $val) {
                $formattedKey = is_int($key)
                    ? '<span class="c-key-int">' . $key . '</span>'
                    : '<span class="c-key-str">"' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '"</span>';

                $out .= '<div class="c-line">' . $formattedKey . ' <span class="c-arrow">=&gt;</span> ' . $this->dump($val, $depth + 1, $maxDepth) . '</div>';
            }

            $out .= '</div>';
            $out .= '<span class="c-bracket">]</span>';
            $out .= '</details>';
            return $out;
        }

        if (is_object($var)) {
            return $this->dumpObject($var, $depth, $maxDepth);
        }

        if (is_resource($var)) {
            return '<span class="c-res">resource(' . get_resource_type($var) . ')</span>';
        }

        return htmlspecialchars(var_export($var, true), ENT_QUOTES, 'UTF-8');
    }

    private function dumpObject(object $object, int $depth, int $maxDepth): string
    {
        $class = get_class($object);
        $id = spl_object_id($object);

        if ($object instanceof Closure) {
            return '<span class="c-class">' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '</span> <span class="c-gray">#{' . $id . '}</span>';
        }

        // Modelo ORM Cronos
        if ($object instanceof Model) {
            $ref = new ReflectionClass($object);
            $getProp = function (string $name) use ($ref, $object) {
                if ($ref->hasProperty($name)) {
                    $p = $ref->getProperty($name);
                    $p->setAccessible(true);
                    return $p->getValue($object);
                }
                return [];
            };

            $attributes = $getProp('attributes') ?: [];
            $relations = $getProp('relations') ?: [];
            $original = $getProp('original') ?: [];

            $out = '<details class="c-tree" open>';
            $out .= '<summary class="c-summary"><span class="c-class">' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '</span> <span class="c-gray">#{' . $id . '}</span> {</summary>';
            $out .= '<div class="c-children">';

            $out .= '<div class="c-line"><span class="c-gray">#attributes:</span> ' . $this->dump($attributes, $depth + 1, $maxDepth) . '</div>';
            if (!empty($relations)) {
                $out .= '<div class="c-line"><span class="c-gray">#relations:</span> ' . $this->dump($relations, $depth + 1, $maxDepth) . '</div>';
            }
            if ($original !== $attributes && !empty($original)) {
                $out .= '<div class="c-line"><span class="c-gray">#original:</span> ' . $this->dump($original, $depth + 1, $maxDepth) . '</div>';
            }

            $out .= '</div>';
            $out .= '<span class="c-bracket">}</span>';
            $out .= '</details>';
            return $out;
        }

        // ModelCollection
        if ($object instanceof ModelCollection) {
            $ref = new ReflectionClass($object);
            $p = $ref->getProperty('items');
            $p->setAccessible(true);
            $items = $p->getValue($object);
            $count = count($items);

            $out = '<details class="c-tree" open>';
            $out .= '<summary class="c-summary"><span class="c-class">' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '</span> <span class="c-badge-count">count: ' . $count . '</span> {</summary>';
            $out .= '<div class="c-children">';
            $out .= '<div class="c-line"><span class="c-gray">#items:</span> ' . $this->dump($items, $depth + 1, $maxDepth) . '</div>';
            $out .= '</div>';
            $out .= '<span class="c-bracket">}</span>';
            $out .= '</details>';
            return $out;
        }

        // Objeto Genérico
        $ref = new ReflectionClass($object);
        $props = $ref->getProperties();
        $open = $depth < 2 ? 'open' : '';

        $out = '<details class="c-tree" ' . $open . '>';
        $out .= '<summary class="c-summary"><span class="c-class">' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '</span> <span class="c-gray">#{' . $id . '}</span> {</summary>';
        $out .= '<div class="c-children">';

        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $vis = $prop->isPublic() ? '+' : ($prop->isProtected() ? '#' : '-');
            $name = $prop->getName();
            $val = $prop->isInitialized($object) ? $prop->getValue($object) : '(uninitialized)';

            $out .= '<div class="c-line"><span class="c-gray">' . $vis . $name . ':</span> ' . $this->dump($val, $depth + 1, $maxDepth) . '</div>';
        }

        $out .= '</div>';
        $out .= '<span class="c-bracket">}</span>';
        $out .= '</details>';
        return $out;
    }

    private function getStylesAndScripts(): string
    {
        return <<<'HTML'
<style>
.cronos-dump-container {
    background-color: #18181b;
    color: #f4f4f5;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 13px;
    line-height: 1.5;
    margin: 12px 0;
    padding: 14px 18px;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
    border: 1px solid #27272a;
    text-align: left;
    z-index: 999999;
}
.cronos-dump-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #27272a;
    padding-bottom: 8px;
    margin-bottom: 12px;
}
.cronos-dump-badge {
    color: #a1a1aa;
    font-size: 12px;
    font-weight: 500;
}
.cronos-dump-actions .cronos-btn {
    background: #27272a;
    color: #d4d4d8;
    border: 1px solid #3f3f46;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 11px;
    cursor: pointer;
    margin-left: 6px;
    transition: all 0.15s ease;
}
.cronos-dump-actions .cronos-btn:hover {
    background: #3f3f46;
    color: #fff;
}
.cronos-dump-body {
    overflow-x: auto;
}
.c-tree {
    display: block;
    margin: 2px 0;
}
.c-summary {
    cursor: pointer;
    user-select: none;
    outline: none;
}
.c-summary::-webkit-details-marker {
    color: #71717a;
}
.c-children {
    padding-left: 18px;
    border-left: 1px dashed #3f3f46;
    margin-left: 6px;
}
.c-line {
    margin: 2px 0;
    white-space: pre-wrap;
    word-break: break-word;
}
.c-null { color: #f43f5e; font-weight: bold; }
.c-bool { color: #a855f7; font-weight: bold; }
.c-num { color: #38bdf8; font-weight: bold; }
.c-str { color: #4ade80; }
.c-len { color: #71717a; font-size: 11px; }
.c-key-int { color: #38bdf8; }
.c-key-str { color: #2dd4bf; }
.c-class { color: #facc15; font-weight: 600; }
.c-bracket { color: #e4e4e7; font-weight: 600; }
.c-arrow { color: #71717a; }
.c-gray { color: #71717a; }
.c-res { color: #fb923c; }
.c-badge-count { background: #27272a; color: #a1a1aa; border-radius: 4px; padding: 1px 5px; font-size: 11px; }
</style>
<script>
function cronosToggleAll(btn, expand) {
    var container = btn.closest('.cronos-dump-container');
    if (!container) return;
    var trees = container.querySelectorAll('details.c-tree');
    trees.forEach(function(el) {
        el.open = expand;
    });
}
</script>
HTML;
    }
}
