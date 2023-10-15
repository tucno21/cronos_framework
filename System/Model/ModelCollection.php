<?php

namespace Cronos\Model;

class ModelCollection
{
    protected array $items = [];

    //agregar un constructor
    public function __construct(array $items = [])
    {
        $this->items = $items;
        //retornamos esta clase
        return $this;
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

    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
