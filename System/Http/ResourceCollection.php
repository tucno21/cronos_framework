<?php

declare(strict_types=1);

namespace Cronos\Http;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;
use Traversable;

class ResourceCollection implements JsonSerializable, Countable, IteratorAggregate
{
    /**
     * La colección subyacente de recursos.
     */
    protected mixed $collection;

    /**
     * La clase de recurso individual que transforma cada elemento.
     */
    protected ?string $collects = null;

    /**
     * Claves de wrapping por defecto.
     */
    public static ?string $wrap = 'data';

    /**
     * Datos adicionales que deben incluirse con la colección.
     */
    protected array $additional = [];

    public function __construct(mixed $resource, ?string $collects = null)
    {
        $this->collects = $collects;
        $this->collection = $this->processCollection($resource);
    }

    /**
     * Procesa la colección mapeando cada elemento a su respectivo JsonResource.
     */
    protected function processCollection(mixed $resource): array
    {
        if (is_null($resource)) {
            return [];
        }

        $items = [];
        if (is_array($resource)) {
            $items = $resource;
        } elseif ($resource instanceof Traversable) {
            $items = iterator_to_array($resource);
        } elseif (is_object($resource) && method_exists($resource, 'toArray')) {
            $items = $resource->toArray();
        }

        if (empty($this->collects)) {
            return $items;
        }

        $collected = [];
        $resourceClass = $this->collects;
        foreach ($items as $key => $item) {
            $collected[$key] = new $resourceClass($item);
        }

        return $collected;
    }

    /**
     * Transforma la colección a un array de arrays/objetos.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->collection as $key => $item) {
            if ($item instanceof JsonResource) {
                $result[$key] = $item->toArray();
            } elseif (is_object($item) && method_exists($item, 'toArray')) {
                $result[$key] = $item->toArray();
            } else {
                $result[$key] = (array) $item;
            }
        }
        return $result;
    }

    /**
     * Agrega datos adicionales a la colección.
     */
    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional, $data);
        return $this;
    }

    /**
     * Resuelve la colección aplicando wrapping y datos adicionales.
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
     * Convierte la colección en una respuesta JSON de Cronos.
     */
    public function toResponse(int $statusCode = 200): Response
    {
        return json($this->resolve(), $statusCode);
    }

    public function jsonSerialize(): mixed
    {
        return $this->resolve();
    }

    public function count(): int
    {
        return count($this->collection);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collection);
    }
}
