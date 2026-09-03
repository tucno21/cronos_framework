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

    public function first(?callable $callback = null)
    {
        if ($callback === null) {
            return reset($this->items);
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function last(?callable $callback = null)
    {
        if ($callback === null) {
            return end($this->items);
        }

        foreach (array_reverse($this->items) as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Ejecuta el callback por cada item. Devuelve la misma coleccion.
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * Reindexa la coleccion (util despues de filtrar sin perder huecos).
     */
    public function values(): self
    {
        return new static(array_values($this->items));
    }

    /**
     * Indica si la coleccion contiene un item que cumpla la condicion.
     * Con callable: $posts->contains(fn($p) => $p->vistas > 100)
     * Con clave/valor: $posts->contains('slug', 'mi-post') (comparacion ==)
     */
    public function contains(string|callable $key, mixed $value = null): bool
    {
        if (is_callable($key)) {
            foreach ($this->items as $item) {
                if ($key($item)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($this->items as $item) {
            if ($item->{$key} == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Suma el valor de una columna de todos los items (los null cuentan 0).
     */
    public function sum(string $key): int|float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float) ($item->{$key} ?? 0);
        }

        return $total;
    }

    /**
     * Promedio del valor de una columna. Devuelve null si no hay items.
     */
    public function avg(string $key): int|float|null
    {
        if ($this->items === []) {
            return null;
        }

        return $this->sum($key) / count($this->items);
    }

    public function min(string $key): int|float|string|null
    {
        $valores = $this->valoresNoNulos($key);

        return $valores === [] ? null : min($valores);
    }

    public function max(string $key): int|float|string|null
    {
        $valores = $this->valoresNoNulos($key);

        return $valores === [] ? null : max($valores);
    }

    /**
     * Agrupa los items por el valor de una columna.
     * Devuelve array<string, ModelCollection>.
     *
     * @return array<string, static>
     */
    public function groupBy(string $key): array
    {
        $grupos = [];
        foreach ($this->items as $item) {
            $grupos[(string) $item->{$key}][] = $item;
        }

        return array_map(fn (array $items) => new static($items), $grupos);
    }

    /**
     * Ordena los items por el valor de una columna.
     */
    public function sortBy(string $key, bool $descending = false): self
    {
        $items = $this->items;
        usort($items, fn ($a, $b) => $a->{$key} <=> $b->{$key});

        if ($descending) {
            $items = array_reverse($items);
        }

        return new static($items);
    }

    /**
     * Ordena los items por una columna en orden descendente.
     */
    public function sortByDesc(string $key): self
    {
        return $this->sortBy($key, true);
    }

    /**
     * @return array<int, int|float|string>
     */
    private function valoresNoNulos(string $key): array
    {
        $valores = [];
        foreach ($this->items as $item) {
            if ($item->{$key} !== null) {
                $valores[] = $item->{$key};
            }
        }

        return $valores;
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
