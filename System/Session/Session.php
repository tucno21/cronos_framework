<?php

namespace Cronos\Session;

use Cronos\Session\PhpNativeSessionStorage;

class Session
{
    protected SessionStorage $storage;

    public const FLASH_KEY = '_flash';
    public const SESSION_CRONOS_PREVIOUS_PATH = '_cronos_previous_path';

    public const SESSION_ERRORS_IMPUTS = '_errors_inputs';

    public function __construct()
    {
        $this->storage = new PhpNativeSessionStorage();
        $this->storage->start();

        if (!$this->storage->has(self::FLASH_KEY)) {
            $this->storage->set(self::FLASH_KEY, ['old' => [], 'new' => []]);
        }

        if (!$this->storage->has(self::SESSION_CRONOS_PREVIOUS_PATH)) {
            $this->storage->set(self::SESSION_CRONOS_PREVIOUS_PATH, ['old' => '', 'new' => '']);
        }

        if (!$this->storage->has(self::SESSION_ERRORS_IMPUTS)) {
            $this->storage->set(self::SESSION_ERRORS_IMPUTS, ['dataInput' => [], 'errors' => []]);
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
            if ($key !== self::FLASH_KEY && $key !== self::SESSION_CRONOS_PREVIOUS_PATH && $key !== self::SESSION_ERRORS_IMPUTS) {
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
        //almacenar en la sesion sin clave
        return $this->storage->set('SESSION_CRONOS', $value);
    }

    public function user()
    {
        //obtener el valor de la sesion sin clave
        return $this->storage->get('SESSION_CRONOS');
    }

    public function previousPath(string $path)
    {
        //funcion que guarda el path anterior para el uso de la funcion back()
        $previousPath = $this->storage->get(self::SESSION_CRONOS_PREVIOUS_PATH);
        $previousPath['old'] = $previousPath['new'];
        $previousPath['new'] = $path;
        $this->storage->set(self::SESSION_CRONOS_PREVIOUS_PATH, $previousPath);
    }

    public function setErrorsInputs(array|object $dataInput, array|object $errors)
    {
        //almacena los errores de los inputs y los datos que se enviaron
        $this->storage->set(self::SESSION_ERRORS_IMPUTS, ['dataInput' => $dataInput, 'errors' => $errors]);
    }

    public function error(string $key): ?string
    {
        //obtener los errores de los inputs mediante la clave
        $content = $this->storage->get(self::SESSION_ERRORS_IMPUTS);

        $errors = [];
        if (is_object($content["errors"])) {
            $errors = (array) $content["errors"];
        } else {
            $errors = $content["errors"];
        }

        return $errors[$key] ?? null;
    }

    public function old(string $key): ?string
    {
        //obtener los datos que se enviaron mediante la clave
        $content = $this->storage->get(self::SESSION_ERRORS_IMPUTS);

        $dataInput = [];
        if (is_object($content["dataInput"])) {
            $dataInput = (array) $content["dataInput"];
        } else {
            $dataInput = $content["dataInput"];
        }

        return $dataInput[$key] ?? null;
    }

    public function ifError(string $key): bool
    {
        //verificar si existe un error en un input
        $content = $this->storage->get(self::SESSION_ERRORS_IMPUTS);

        $errors = [];
        if (is_object($content["errors"])) {
            $errors = (array) $content["errors"];
        } else {
            $errors = $content["errors"];
        }

        return isset($errors[$key]);
    }

    public function deleteErrorsInputs()
    {
        //eliminar los errores de los inputs y los datos que se enviaron
        $content = $this->storage->get(self::SESSION_ERRORS_IMPUTS);
        $content['dataInput'] = [];
        $content['errors'] = [];
        $this->storage->set(self::SESSION_ERRORS_IMPUTS, $content);
    }
}
