<?php

namespace Tests\Integration\Fixtures;

/**
 * Observer para PublicacionConEventos: apila los eventos que recibe.
 */
class PublicacionObserver
{
    /**
     * @var string[]
     */
    public static array $registro = [];

    public static function reset(): void
    {
        self::$registro = [];
    }

    public function created($modelo): void
    {
        self::$registro[] = 'observer-created:' . $modelo->slug;
    }

    public function updated($modelo): void
    {
        self::$registro[] = 'observer-updated:' . $modelo->slug;
    }

    public function deleted($modelo): void
    {
        self::$registro[] = 'observer-deleted:' . $modelo->slug;
    }
}
