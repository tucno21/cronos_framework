<?php

declare(strict_types=1);

namespace Cronos\Model;

use Cronos\Model\ModelCollection;

/**
 * Clase base para Factories de Modelos en Cronos Framework (estilo Laravel).
 */
abstract class Factory
{
    /**
     * Nombre de la clase del Modelo asociado.
     * Ejemplo: \App\Models\User::class
     */
    protected string $model;

    /**
     * Cantidad de instancias a generar.
     */
    protected int $count = 1;

    /**
     * Modificadores de estado adicionales aplicados sobre la definición base.
     *
     * @var array<callable>
     */
    protected array $states = [];

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    abstract public function definition(): array;

    /**
     * Define la cantidad de modelos a generar.
     */
    public function count(int $count): static
    {
        $clone = clone $this;
        $clone->count = max(1, $count);

        return $clone;
    }

    /**
     * Agrega una transformación o estado específico.
     */
    public function state(callable|array $state): static
    {
        $clone = clone $this;
        $clone->states[] = is_callable($state) ? $state : fn() => $state;

        return $clone;
    }

    /**
     * Genera instancias del modelo en memoria SIN persistir en base de datos.
     * Retorna una instancia de Model si count es 1 (sin count() explícito), o ModelCollection si count > 1.
     */
    public function make(array $attributes = []): mixed
    {
        $results = [];

        for ($i = 0; $i < $this->count; $i++) {
            $raw = $this->getRawAttributes($attributes);
            /** @var Model $model */
            $model = new $this->model();
            $model->setAttributes($raw);
            $results[] = $model;
        }

        if ($this->count === 1) {
            return $results[0];
        }

        return new ModelCollection($results);
    }

    /**
     * Genera e inserta las instancias del modelo en la base de datos.
     */
    public function create(array $attributes = []): mixed
    {
        $results = [];

        for ($i = 0; $i < $this->count; $i++) {
            $raw = $this->getRawAttributes($attributes);
            /** @var class-string<Model> $modelClass */
            $modelClass = $this->model;
            $created = $modelClass::create($raw);
            $results[] = $created;
        }

        if ($this->count === 1) {
            return $results[0];
        }

        return new ModelCollection($results);
    }

    /**
     * Resuelve los atributos combinando la definición base, estados y sobreescrituras.
     */
    public function raw(array $attributes = []): array
    {
        return $this->getRawAttributes($attributes);
    }

    /**
     * Combina y evalúa los atributos.
     */
    protected function getRawAttributes(array $override = []): array
    {
        $definition = $this->definition();

        foreach ($this->states as $state) {
            $stateAttributes = $state($definition);
            if (is_array($stateAttributes)) {
                $definition = array_merge($definition, $stateAttributes);
            }
        }

        $final = array_merge($definition, $override);

        // Resolver closures que se hayan pasado como valores de atributos
        foreach ($final as $key => $value) {
            if ($value instanceof \Closure) {
                $final[$key] = $value($final);
            }
        }

        return $final;
    }
}
