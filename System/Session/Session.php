<?php

namespace Cronos\Session;

use Cronos\Session\PhpNativeSessionStorage;

class Session
{
    protected SessionStorage $storage;

    public const FLASH_KEY = '_flash';

    public function __construct()
    {
        $this->storage = new PhpNativeSessionStorage();
        $this->storage->start();

        if (!$this->storage->has(self::FLASH_KEY)) {
            $this->storage->set(self::FLASH_KEY, ['old' => [], 'new' => []]);
        }
    }

    public function __destruct()
    {
        foreach ($this->storage->get(self::FLASH_KEY)['old'] as $key) {
            $this->storage->remove($key);
        }

        $this->ageFlashData();
        $this->storage->save();
    }

    public function ageFlashData()
    {
        $flash = $this->storage->get(self::FLASH_KEY);
        $flash['old'] = $flash['new'];
        $flash['new'] = [];
        $this->storage->set(self::FLASH_KEY, $flash);
    }

    public function flash(string $key, mixed $value)
    {
        //almacenar en la sesion para que sea eliminada en el siguiente request
        $this->storage->set($key, $value);
        $flash = $this->storage->get(self::FLASH_KEY);
        $flash['new'][] = $key;
        $this->storage->set(self::FLASH_KEY, $flash);
    }

    public function set(string $key, array|object $value)
    {
        //almacenar en la sesion
        return $this->storage->set($key, $value);
    }

    public function put(string $key, array|object $value)
    {
        //almacenar en la sesion
        return $this->storage->set($key, $value);
    }

    //agrear un valor a una sesion que ya existe
    public function push(string $key, mixed $value)
    {
        //separar $key por el punto
        $keys = explode('.', $key);

        //obtener el primer elemento
        $defaulKey = array_shift($keys);

        //buscar en la sesion si existe la sesion
        $session = $this->storage->has($defaulKey);

        //si no existe la sesion
        if ($session) {
            //obtener el ultimo elemento
            $lastKey = array_pop($keys);

            //obtener el valor de la sesion
            $session = $this->storage->get($defaulKey);

            if (is_array($session)) {
                $session[$lastKey] = $value;
            }

            if (is_object($session)) {
                $session->$lastKey = $value;
            }

            //almacenar en la sesion
            return $this->storage->set($defaulKey, $session);
        }
    }

    //obtener un valor de una sesion
    public function get(string $key, $default = null)
    {
        return $this->storage->get($key, $default);
    }

    public function all(): array
    {
        //obtener todas las sesiones creadas
        return $_SESSION;
    }

    public function pull(string $key, $default = null)
    {
        //obtener el valor de una sesion
        $value = $this->storage->get($key, $default);

        //eliminar la sesion
        $this->storage->remove($key);

        return $value;
    }

    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    public function remove(string $key)
    {
        return $this->storage->remove($key);
    }

    public function destroy()
    {
        return $this->storage->destroy();
    }

    public function forget(string $key)
    {
        return $this->storage->remove($key);
    }

    public function flush()
    {
        //eliminar todo menos  ["_flash"]
        foreach ($this->all() as $key => $value) {
            if ($key !== self::FLASH_KEY) {
                $this->storage->remove($key);
            }
        }
    }

    public function id(): string
    {
        return $this->storage->id();
    }

    public function attempt(mixed $value)
    {
        //almacenar en la sesion
        return $this->storage->set('SESSION_CRONOS', $value);
    }

    public function user()
    {
        //obtener el valor de la sesion
        return $this->storage->get('SESSION_CRONOS');
    }
}
