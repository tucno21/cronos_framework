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
        return new static(array_filter($this->items, $callback));
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

    public function toJson(): string
    {
        return json_encode($this->items);
    }

    public function pluck(string $key): array
    {
        return array_column($this->items, $key);
    }
}
