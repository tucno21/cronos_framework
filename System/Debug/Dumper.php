<?php

declare(strict_types=1);

namespace Cronos\Debug;

/**
 * Orquestador principal de depuración de Cronos Framework.
 * Detecta automáticamente si el entorno es CLI, Web o API/JSON.
 */
class Dumper
{
    private static ?CliDumper $cliDumper = null;
    private static ?HtmlDumper $htmlDumper = null;

    /**
     * Vuelca las variables y permite continuar la ejecución.
     */
    public static function dump(mixed ...$vars): void
    {
        $caller = self::detectCaller();

        if (self::isCli()) {
            $cli = self::getCliDumper();
            echo "\n\033[90m📍 " . $caller . "\033[0m\n";
            foreach ($vars as $var) {
                echo $cli->dump($var) . "\n";
            }
            return;
        }

        if (self::isJsonRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                '__cronos_debug' => true,
                'caller' => $caller,
                'dumps' => array_map(fn($v) => self::normalizeForJson($v), $vars),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            return;
        }

        $html = self::getHtmlDumper();
        echo $html->render($vars, $caller);
    }

    /**
     * Vuelca las variables y termina la ejecución (exit).
     */
    public static function dd(mixed ...$vars): never
    {
        if (!headers_sent() && !self::isCli()) {
            http_response_code(500);
        }

        self::dump(...$vars);
        exit(1);
    }

    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    public static function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || $isAjax;
    }

    private static function detectCaller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);

        foreach ($trace as $step) {
            $file = $step['file'] ?? '';
            $line = $step['line'] ?? 0;

            if (empty($file)) {
                continue;
            }

            // Ignorar los helpers y la propia clase Dumper
            if (str_contains($file, 'System/Helpers/debug.php') || str_contains($file, 'System\Helpers\debug.php')) {
                continue;
            }
            if (str_contains($file, 'System/Debug') || str_contains($file, 'System\Debug')) {
                continue;
            }

            // Normalizar ruta relativa si es posible
            $root = defined('CRONOS_ROOT') ? CRONOS_ROOT : dirname(__DIR__, 2);
            $relativePath = str_replace([$root . DIRECTORY_SEPARATOR, $root . '/'], '', $file);

            return $relativePath . ':' . $line;
        }

        return 'unknown';
    }

    private static function normalizeForJson(mixed $var, int $depth = 0): mixed
    {
        if ($depth >= 3) {
            return '/* Max Depth */';
        }

        if (is_null($var) || is_scalar($var)) {
            return $var;
        }

        if (is_array($var)) {
            $res = [];
            foreach ($var as $k => $v) {
                $res[$k] = self::normalizeForJson($v, $depth + 1);
            }
            return $res;
        }

        if (is_object($var)) {
            if (method_exists($var, 'toArray')) {
                return [
                    '__class' => get_class($var),
                    'data' => $var->toArray(),
                ];
            }

            return [
                '__class' => get_class($var),
                'properties' => get_object_vars($var),
            ];
        }

        return (string) $var;
    }

    private static function getCliDumper(): CliDumper
    {
        if (self::$cliDumper === null) {
            self::$cliDumper = new CliDumper();
        }
        return self::$cliDumper;
    }

    private static function getHtmlDumper(): HtmlDumper
    {
        if (self::$htmlDumper === null) {
            self::$htmlDumper = new HtmlDumper();
        }
        return self::$htmlDumper;
    }
}
