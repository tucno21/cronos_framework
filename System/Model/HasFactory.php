<?php

declare(strict_types=1);

namespace Cronos\Model;

/**
 * Trait para habilitar Factories en Modelos de Cronos Framework.
 * Permite usar Usuario::factory()->create() o Usuario::factory(5)->make().
 */
trait HasFactory
{
    /**
     * Obtiene una nueva instancia de la Factory correspondiente a este Modelo.
     * Convención: App\Models\User -> App\Factories\UserFactory o Database\Factories\UserFactory
     */
    public static function factory(int|null $count = null, array $state = []): Factory
    {
        $factoryClass = static::newFactoryClass();

        if (!class_exists($factoryClass)) {
            throw new \RuntimeException(sprintf('Factory [%s] para el modelo [%s] no existe.', $factoryClass, static::class));
        }

        /** @var Factory $factory */
        $factory = new $factoryClass();

        if ($count !== null) {
            $factory = $factory->count($count);
        }

        if (!empty($state)) {
            $factory = $factory->state($state);
        }

        return $factory;
    }

    /**
     * Resuelve el nombre de clase de la Factory por convención.
     */
    protected static function newFactoryClass(): string
    {
        $modelName = basename(str_replace('\\', '/', static::class));
        
        $candidate1 = 'App\\Factories\\' . $modelName . 'Factory';
        if (class_exists($candidate1)) {
            return $candidate1;
        }

        $candidate2 = 'Database\\Factories\\' . $modelName . 'Factory';
        if (class_exists($candidate2)) {
            return $candidate2;
        }

        return $candidate1;
    }
}
