<?php

namespace Cronos\Model;

class HasOne
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

    public function get(): Model|null
    {
        return $this->related->newQuery()->where($this->foreignKey, $this->parent->{$this->localKey})->first();
    }

    /**
     * Crea el registro relacionado asignando automaticamente la clave foranea.
     *
     *   $usuario->perfil()->create(['biografia' => '...']);
     *
     * Los campos deben cumplir las reglas de create() del modelo relacionado
     * (todos los $fillable presentes).
     */
    public function create(array|object $data): ?Model
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $valor = $this->parent->{$this->localKey} ?? null;

        if ($valor === null) {
            throw new \Error('El modelo ' . get_class($this->parent) . ' no tiene clave primaria para crear la relacion ' . $this->foreignKey);
        }

        $data[$this->foreignKey] = $valor;

        $clase = get_class($this->related);

        return $clase::create($data);
    }

    /**
     * Guarda un modelo existente como relacionado asignandole la clave foranea.
     *
     *   $usuario->perfil()->save($perfil);
     */
    public function save(Model $modelo): bool
    {
        $valor = $this->parent->{$this->localKey} ?? null;

        if ($valor === null) {
            throw new \Error('El modelo ' . get_class($this->parent) . ' no tiene clave primaria para guardar la relacion ' . $this->foreignKey);
        }

        $modelo->{$this->foreignKey} = $valor;

        return $modelo->save();
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
