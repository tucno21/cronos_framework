<?php

namespace Cronos\Model;

/**
 * @phpstan-consistent-constructor
 */
class ModelCollection implements \IteratorAggregate, \Countable
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->items as $item) {
            $data[] = $item->toArray();
        }
        return $data;
    }

    public function toObject(): array
    {
        $data = [];
        foreach ($this->items as $item) {
            $data[] = $item->toObject();
        }
        return $data;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function first()
    {
        return reset($this->items);
    }

    public function last()
    {
        return end($this->items);
    }

    /**
     * Serializa la coleccion respetando los campos $hidden de cada modelo.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function pluck(string $key): array
    {
        //usa el accessor magico del modelo (array_column no lee __get)
        return array_map(fn ($item) => $item->{$key}, $this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }

    /**
     * Carga perezosa de relaciones sobre todos los items de la coleccion.
     * Las relaciones quedan cacheadas en cada modelo y se incluyen en toArray().
     *
     * Ejemplo: $publicaciones->load('usuario', 'comentarios');
     */
    public function load(string ...$relations): self
    {
        foreach ($this->items as $item) {
            foreach ($relations as $relation) {
                //el acceso por __get carga la relacion y la deja cacheada en el modelo
                $loaded = $item->{$relation};
            }
        }

        return $this;
    }
}
