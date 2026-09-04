<?php

namespace Tests\Integration\Fixtures;

use App\Models\Publicacion;

/**
 * Variante de Publicacion con eventos registrados que apilan en $registro.
 * El registro se limpia con PublicacionConEventos::reset().
 */
class PublicacionConEventos extends Publicacion
{
    /**
     * @var string[]
     */
    public static array $registro = [];

    protected static function booted(): void
    {
        static::saving(function ($modelo) {
            self::$registro[] = 'saving:' . $modelo->slug;

            return true;
        });

        static::creating(function ($modelo) {
            self::$registro[] = 'creating:' . $modelo->slug;

            return true;
        });

        static::created(function ($modelo) {
            self::$registro[] = 'created:' . $modelo->slug;
        });

        static::updating(function ($modelo) {
            self::$registro[] = 'updating:' . $modelo->slug;

            return true;
        });

        static::updated(function ($modelo) {
            self::$registro[] = 'updated:' . $modelo->slug;
        });

        static::saved(function ($modelo) {
            self::$registro[] = 'saved:' . $modelo->slug;
        });

        static::deleting(function ($modelo) {
            self::$registro[] = 'deleting:' . $modelo->slug;

            return true;
        });

        static::deleted(function ($modelo) {
            self::$registro[] = 'deleted:' . $modelo->slug;
        });

        static::retrieved(function ($modelo) {
            self::$registro[] = 'retrieved:' . $modelo->slug;
        });
    }

    public static function reset(): void
    {
        self::$registro = [];
    }
}
