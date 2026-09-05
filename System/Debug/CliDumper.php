<?php

declare(strict_types=1);

namespace Cronos\Debug;

use Closure;
use ReflectionClass;
use Cronos\Model\Model;
use Cronos\Model\ModelCollection;

/**
 * Renderizador de variables para consola / CLI con colores ANSI y formato limpio.
 */
class CliDumper
{
    private const COLOR_RESET   = "\033[0m";
    private const COLOR_GRAY    = "\033[90m";
    private const COLOR_GREEN   = "\033[32m";
    private const COLOR_CYAN    = "\033[36m";
    private const COLOR_YELLOW  = "\033[33m";
    private const COLOR_BLUE    = "\033[34m";
    private const COLOR_MAGENTA = "\033[35m";
    private const COLOR_BOLD    = "\033[1m";

    public function dump(mixed $var, int $depth = 0, int $maxDepth = 6): string
    {
        $indent = str_repeat('  ', $depth);

        if ($depth >= $maxDepth) {
            return self::COLOR_GRAY . '/* Max depth */' . self::COLOR_RESET;
        }

        if (is_null($var)) {
            return self::COLOR_MAGENTA . 'null' . self::COLOR_RESET;
        }

        if (is_bool($var)) {
            return self::COLOR_MAGENTA . ($var ? 'true' : 'false') . self::COLOR_RESET;
        }

        if (is_int($var) || is_float($var)) {
            return self::COLOR_BLUE . (string) $var . self::COLOR_RESET;
        }

        if (is_string($var)) {
            $len = strlen($var);
            return self::COLOR_GREEN . '"' . addcslashes($var, "\"\n\r\t") . '"' . self::COLOR_RESET . self::COLOR_GRAY . " ($len)" . self::COLOR_RESET;
        }

        if (is_array($var)) {
            $count = count($var);
            if ($count === 0) {
                return '[]';
            }

            $out = "[\n";
            foreach ($var as $key => $val) {
                $formattedKey = is_int($key)
                    ? self::COLOR_BLUE . $key . self::COLOR_RESET
                    : self::COLOR_CYAN . '"' . $key . '"' . self::COLOR_RESET;

                $out .= $indent . '  ' . $formattedKey . ' => ' . $this->dump($val, $depth + 1, $maxDepth) . ",\n";
            }
            $out .= $indent . ']';
            return $out;
        }

        if (is_object($var)) {
            return $this->dumpObject($var, $depth, $maxDepth);
        }

        if (is_resource($var)) {
            return self::COLOR_YELLOW . 'resource(' . get_resource_type($var) . ')' . self::COLOR_RESET;
        }

        return var_export($var, true);
    }

    private function dumpObject(object $object, int $depth, int $maxDepth): string
    {
        $indent = str_repeat('  ', $depth);
        $class = get_class($object);

        if ($object instanceof Closure) {
            return self::COLOR_YELLOW . $class . ' {#closure}' . self::COLOR_RESET;
        }

        // Tratamiento especializado para Modelos del ORM de Cronos
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
            $original = $getProp('original') ?: [];
            $relations = $getProp('relations') ?: [];

            $out = self::COLOR_BOLD . self::COLOR_YELLOW . $class . self::COLOR_RESET . " {\n";
            $out .= $indent . "  " . self::COLOR_GRAY . "#attributes: " . self::COLOR_RESET . $this->dump($attributes, $depth + 1, $maxDepth) . ",\n";
            if (!empty($relations)) {
                $out .= $indent . "  " . self::COLOR_GRAY . "#relations: " . self::COLOR_RESET . $this->dump($relations, $depth + 1, $maxDepth) . ",\n";
            }
            if ($original !== $attributes && !empty($original)) {
                $out .= $indent . "  " . self::COLOR_GRAY . "#original: " . self::COLOR_RESET . $this->dump($original, $depth + 1, $maxDepth) . ",\n";
            }
            $out .= $indent . "}";
            return $out;
        }

        // Tratamiento especializado para ModelCollection
        if ($object instanceof ModelCollection) {
            $ref = new ReflectionClass($object);
            $p = $ref->getProperty('items');
            $p->setAccessible(true);
            $items = $p->getValue($object);

            $out = self::COLOR_BOLD . self::COLOR_YELLOW . $class . self::COLOR_RESET . " (count: " . count($items) . ") {\n";
            $out .= $indent . "  #items => " . $this->dump($items, $depth + 1, $maxDepth) . "\n";
            $out .= $indent . "}";
            return $out;
        }

        // Objeto genérico por reflexión
        $ref = new ReflectionClass($object);
        $props = $ref->getProperties();
        $out = self::COLOR_BOLD . self::COLOR_YELLOW . $class . self::COLOR_RESET . " {\n";

        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $vis = $prop->isPublic() ? '+' : ($prop->isProtected() ? '#' : '-');
            $name = $prop->getName();
            $val = $prop->isInitialized($object) ? $prop->getValue($object) : '(uninitialized)';

            $out .= $indent . '  ' . self::COLOR_GRAY . $vis . $name . ': ' . self::COLOR_RESET . $this->dump($val, $depth + 1, $maxDepth) . ",\n";
        }

        $out .= $indent . "}";
        return $out;
    }
}
