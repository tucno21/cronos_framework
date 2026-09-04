<?php

namespace Cronos\Model;

class BelongsToMany
{
    protected Model $related;
    protected Model $parent;
    protected string $pivotTable;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;

    public function __construct(Model $related, Model $parent, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    public function get(): ?ModelCollection
    {
        return $this->related->newQuery()
            ->join(
                $this->pivotTable,
                $this->related->getTable() . '.' . $this->related->getPrimaryKey(),
                '=',
                $this->pivotTable . '.' . $this->relatedPivotKey
            )
            ->where(
                $this->pivotTable . '.' . $this->foreignPivotKey,
                $this->parent->{$this->parent->getPrimaryKey()}
            )
            ->get();
    }

    //******************************************************************
    // ESCRITURA DEL PIVOTE (estilo Laravel)
    //******************************************************************

    /**
     * Asocia registros al pivote. Acepta un id o un array de ids.
     * Los duplicados se ignoran (INSERT IGNORE sobre la clave compuesta).
     *
     *   $publicacion->etiquetas()->attach(3);
     *   $publicacion->etiquetas()->attach([1, 2, 3]);
     *
     * @return int filas insertadas
     */
    public function attach(int|string|array $relatedKeys): int
    {
        $ids = $this->normalizarIds($relatedKeys);
        $parentKey = $this->claveDelPadre();

        $placeholders = implode(', ', array_fill(0, count($ids), '(?, ?)'));
        $values = [];
        foreach ($ids as $id) {
            $values[] = $parentKey;
            $values[] = $id;
        }

        $sql = "INSERT IGNORE INTO {$this->pivotTable} ({$this->foreignPivotKey}, {$this->relatedPivotKey}) VALUES {$placeholders}";

        return Model::db()->statementC_U_D($sql, $values);
    }

    /**
     * Desasocia registros del pivote. Sin argumentos desasocia TODOS.
     *
     *   $publicacion->etiquetas()->detach(3);       //solo la 3
     *   $publicacion->etiquetas()->detach([1, 2]);  //las 1 y 2
     *   $publicacion->etiquetas()->detach();        //todas
     *
     * @return int filas eliminadas
     */
    public function detach(int|string|array|null $relatedKeys = null): int
    {
        $parentKey = $this->claveDelPadre();

        if ($relatedKeys === null) {
            $sql = "DELETE FROM {$this->pivotTable} WHERE {$this->foreignPivotKey} = ?";

            return Model::db()->statementC_U_D($sql, [$parentKey]);
        }

        $ids = $this->normalizarIds($relatedKeys);

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->pivotTable} WHERE {$this->foreignPivotKey} = ? AND {$this->relatedPivotKey} IN ({$placeholders})";

        return Model::db()->statementC_U_D($sql, array_merge([$parentKey], $ids));
    }

    /**
     * Sincroniza el pivote con la lista dada: agrega los que faltan y
     * elimina los que sobran.
     *
     *   $usuario->roles()->sync([2, 3]);
     *
     * @return array{attached: int[], detached: int[], updated: int[]}
     */
    public function sync(int|string|array $relatedKeys): array
    {
        $objetivo = $this->normalizarIds($relatedKeys, true);
        $parentKey = $this->claveDelPadre();

        $filas = (array) Model::db()->statement(
            "SELECT {$this->relatedPivotKey} FROM {$this->pivotTable} WHERE {$this->foreignPivotKey} = ?",
            [$parentKey]
        );

        $actuales = [];
        foreach ($filas as $fila) {
            $fila = (array) $fila;
            $actuales[] = (string) $fila[$this->relatedPivotKey];
        }

        $aDesasociar = array_values(array_diff($actuales, array_map('strval', $objetivo)));
        $aAsociar = array_values(array_diff(array_map('strval', $objetivo), $actuales));

        $detached = [];
        if ($aDesasociar !== []) {
            $this->detach($aDesasociar);
            $detached = array_map('intval', $aDesasociar);
        }

        $attached = [];
        if ($aAsociar !== []) {
            $this->attach($aAsociar);
            $attached = array_map('intval', $aAsociar);
        }

        return ['attached' => $attached, 'detached' => $detached, 'updated' => []];
    }

    /**
     * Normaliza ids: acepta un escalar o array de escalares.
     *
     * @return int[]|string[]
     */
    private function normalizarIds(int|string|array $relatedKeys, bool $permitirVacio = false): array
    {
        $ids = is_array($relatedKeys) ? $relatedKeys : [$relatedKeys];

        if ($ids === [] && !$permitirVacio) {
            throw new \Error('La lista de ids no puede estar vacia. Use detach() sin argumentos para desasociar todos.');
        }

        foreach ($ids as $id) {
            if (!is_int($id) && !is_string($id)) {
                throw new \Error('attach()/detach()/sync() aceptan ids escalares (int o string)');
            }
        }

        return array_values($ids);
    }

    /**
     * Clave primaria del modelo padre, requerida para escribir el pivote.
     */
    private function claveDelPadre(): int|string
    {
        $parentKey = $this->parent->{$this->parent->getPrimaryKey()} ?? null;

        if ($parentKey === null) {
            throw new \Error('El modelo ' . get_class($this->parent) . ' no tiene clave primaria para escribir en el pivote ' . $this->pivotTable);
        }

        return $parentKey;
    }

    public function getRelated(): Model
    {
        return $this->related;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getPivotTable(): string
    {
        return $this->pivotTable;
    }

    public function getForeignPivotKey(): string
    {
        return $this->foreignPivotKey;
    }

    public function getRelatedPivotKey(): string
    {
        return $this->relatedPivotKey;
    }
}
