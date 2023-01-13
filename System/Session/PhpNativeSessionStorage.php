<?php

namespace Cronos\Session;

use Cronos\Session\SessionStorage;

class PhpNativeSessionStorage implements SessionStorage
{
    public function start()
    {
        session_start();
    }

    public function save()
    {
        session_write_close();
    }

    public function id(): string
    {
        return session_id();
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value)
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key)
    {
        unset($_SESSION[$key]);
    }

    public function destroy()
    {
        session_destroy();
    }
}
