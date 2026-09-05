<?php

declare(strict_types=1);

namespace Cronos\Http;

use ArrayAccess;
use JsonSerializable;

class JsonResource implements JsonSerializable, ArrayAccess
{
    /**
     * El recurso subyacente (modelo, array u objeto).
     */
    protected mixed $resource;

    /**
     * Claves de wrapping por defecto.
     */
    public static ?string $wrap = 'data';

    /**
     * Datos adicionales que deben incluirse con el recurso.
     */
    protected array $additional = [];

    public function __construct(mixed $resource = null)
    {
        $this->resource = $resource;
    }

    /**
     * Crea una nueva instancia del recurso.
     */
    public static function make(mixed $resource = null): static
    {
        return new static($resource);
    }

    /**
     * Crea una colección de recursos.
     */
    public static function collection(mixed $resource): ResourceCollection
    {
        return new ResourceCollection($resource, static::class);
    }

    /**
     * Transforma el recurso a un array.
     */
    public function toArray(): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        if (is_array($this->resource)) {
            return $this->resource;
        }

        if (is_object($this->resource) && method_exists($this->resource, 'toArray')) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }

    /**
     * Agrega datos adicionales al recurso.
     */
    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional, $data);
        return $this;
    }

    /**
     * Convierte el recurso en una respuesta JSON de Cronos.
     */
    public function toResponse(int $statusCode = 200): Response
    {
        return json($this->resolve(), $statusCode);
    }

    /**
     * Resuelve el recurso aplicando el wrapping y los datos adicionales.
     */
    public function resolve(): array
    {
        $data = $this->toArray();

        if (!is_null(static::$wrap)) {
            $data = [static::$wrap => $data];
        }

        if (!empty($this->additional)) {
            $data = array_merge($data, $this->additional);
        }

        return $data;
    }

    /**
     * Retorna un valor condicionalmente si la condición es verdadera.
     */
    protected function when(bool $condition, mixed $value, mixed $default = null): mixed
    {
        if ($condition) {
            return is_callable($value) ? $value() : $value;
        }

        return is_callable($default) ? $default() : $default;
    }

    /**
     * Retorna una relación cargada condicionalmente.
     */
    protected function whenLoaded(string $relationship, mixed $value = null, mixed $default = null): mixed
    {
        if (is_null($this->resource) || !is_object($this->resource)) {
            return is_callable($default) ? $default() : $default;
        }

        $isLoaded = false;

        // Comprueba si la propiedad o relación está disponible en el modelo
        if (property_exists($this->resource, $relationship) && !is_null($this->resource->{$relationship})) {
            $isLoaded = true;
        } elseif (method_exists($this->resource, '__get')) {
            $val = $this->resource->{$relationship};
            if (!is_null($val)) {
                $isLoaded = true;
            }
        }

        if ($isLoaded) {
            if (func_num_args() === 1) {
                return $this->resource->{$relationship};
            }
            return is_callable($value) ? $value() : $value;
        }

        return is_callable($default) ? $default() : $default;
    }

    public function jsonSerialize(): mixed
    {
        return $this->resolve();
    }

    /**
     * Acceso dinámico a las propiedades del recurso subyacente.
     */
    public function __get(string $key): mixed
    {
        if (is_null($this->resource)) {
            return null;
        }

        if (is_array($this->resource)) {
            return $this->resource[$key] ?? null;
        }

        if (is_object($this->resource)) {
            return $this->resource->{$key} ?? null;
        }

        return null;
    }

    public function __isset(string $key): bool
    {
        if (is_null($this->resource)) {
            return false;
        }

        if (is_array($this->resource)) {
            return isset($this->resource[$key]);
        }

        if (is_object($this->resource)) {
            return isset($this->resource->{$key});
        }

        return false;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_array($this->resource)) {
            $this->resource[$offset] = $value;
        } elseif (is_object($this->resource)) {
            $this->resource->{$offset} = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_array($this->resource)) {
            unset($this->resource[$offset]);
        } elseif (is_object($this->resource)) {
            unset($this->resource->{$offset});
        }
    }
}
