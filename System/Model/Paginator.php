<?php

namespace Cronos\Model;

/**
 * Resultado de paginate(): pagina de resultados + metadatos.
 *
 *   $pagina = Publicacion::paginate(15, 2);
 *   $pagina->items;        //ModelCollection
 *   $pagina->total;        //registros totales (con el filtro aplicado)
 *   $pagina->toArray();    //estructura lista para JSON
 */
final class Paginator implements \IteratorAggregate, \Countable
{
    public readonly ModelCollection $items;

    public readonly int $total;

    public readonly int $porPagina;

    public readonly int $paginaActual;

    public readonly int $ultimaPagina;

    /**
     * @param Model[] $items
     */
    public function __construct(array $items, int $total, int $porPagina, int $paginaActual)
    {
        $this->items = new ModelCollection($items);
        $this->total = $total;
        $this->porPagina = $porPagina;
        $this->paginaActual = $paginaActual;
        $this->ultimaPagina = max(1, (int) ceil($total / max(1, $porPagina)));
    }

    /**
     * Primer registro de la pagina (numeracion desde 1). Null si vacia.
     */
    public function desde(): ?int
    {
        if ($this->count() === 0) {
            return null;
        }

        return ($this->paginaActual - 1) * $this->porPagina + 1;
    }

    /**
     * Ultimo registro de la pagina (numeracion desde 1). Null si vacia.
     */
    public function hasta(): ?int
    {
        if ($this->count() === 0) {
            return null;
        }

        return ($this->paginaActual - 1) * $this->porPagina + $this->count();
    }

    public function toArray(): array
    {
        return [
            'data' => $this->items->toArray(),
            'total' => $this->total,
            'por_pagina' => $this->porPagina,
            'pagina_actual' => $this->paginaActual,
            'ultima_pagina' => $this->ultimaPagina,
            'desde' => $this->desde(),
            'hasta' => $this->hasta(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->items->getIterator();
    }

    public function count(): int
    {
        return $this->items->count();
    }
}
