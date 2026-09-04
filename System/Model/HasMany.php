<?php

namespace Cronos\Model;

class HasMany
{
    protected Model $related;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function get(): ?ModelCollection
    {
        return $this->related->newQuery()->where($this->foreignKey, $this->parent->{$this->localKey})->get();
    }

    /**
     * Crea un registro relacionado asignando automaticamente la clave foranea.
     *
     *   $usuario->publicaciones()->create(['titulo' => '...', ...]);
     *
     * Los campos deben cumplir las reglas de create() del modelo relacionado
     * (todos los $fillable presentes).
     */
    public function create(array|object $data): ?Model
    {
        $data = $this->conClaveForanea($data);

        $clase = get_class($this->related);

        return $clase::create($data);
    }

    /**
     * Guarda un modelo existente como relacionado asignandole la clave foranea.
     *
     *   $usuario->publicaciones()->save($publicacion);
     */
    public function save(Model $modelo): bool
    {
        $modelo->{$this->foreignKey} = $this->valorClavePadre();

        return $modelo->save();
    }

    /**
     * Agrega la clave foranea del padre a los datos.
     */
    private function conClaveForanea(array|object $data): array
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $data[$this->foreignKey] = $this->valorClavePadre();

        return $data;
    }

    private function valorClavePadre(): int|string
    {
        $valor = $this->parent->{$this->localKey} ?? null;

        if ($valor === null) {
            throw new \Error('El modelo ' . get_class($this->parent) . ' no tiene clave primaria para crear/guardar la relacion ' . $this->foreignKey);
        }

        return $valor;
    }

    public function getRelated(): Model
    {
        return $this->related;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }
}
