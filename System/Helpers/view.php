<?php

use Cronos\App;
use Cronos\Session\Session;

if (!function_exists('e')) {
    /**
     * escapar un valor para salida HTML segura
     */
    function e(mixed $value, bool $doubleEncode = false): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);

            return htmlspecialchars($json === false ? '' : $json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * obtener el token csrf de la sesion, generandolo si no existe
     */
    function csrf_token(): string
    {
        try {
            $session = session();
        } catch (\Throwable) {
            $session = null;
        }

        if ($session instanceof Session) {
            $token = $session->get(Session::SESSION_CSRF_TOKEN);

            if (!is_string($token) || $token === '') {
                $token = bin2hex(random_bytes(20));
                $session->put(Session::SESSION_CSRF_TOKEN, $token);
            }

            return $token;
        }

        //sin sesion disponible (cli/tests): token estable por proceso
        static $fallback = null;

        return $fallback ??= bin2hex(random_bytes(20));
    }
}

if (!function_exists('asset')) {
    /**
     * url de un asset de public/ con versionado por fecha de modificacion
     */
    function asset(string $path): string
    {
        $base = defined('base_url') ? base_url : (function_exists('base_url') ? base_url() : '');

        $url = rtrim($base, '/') . '/' . ltrim($path, '/');
        $file = isset(App::$root) ? App::$root . '/public/' . ltrim($path, '/') : null;

        return $url . (($file !== null && is_file($file)) ? '?v=' . filemtime($file) : '');
    }
}
