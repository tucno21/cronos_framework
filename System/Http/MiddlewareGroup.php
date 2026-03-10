<?php

namespace Cronos\Http;

use Cronos\Config\Config;

/**
 * MiddlewareGroup - Gestiona grupos de middlewares predefinidos
 * 
 * Esta clase permite registrar grupos de middlewares con nombres alias
 * que pueden ser usados fácilmente en las rutas. Esto facilita
 * la organización y reutilización de middlewares comunes.
 * 
 * Ejemplo de uso en config/app.php:
 * 'middleware_groups' => [
 *     'web' => [CorsMiddleware::class],
 *     'api' => [ThrottleMiddleware::class, CorsMiddleware::class],
 * ],
 */
class MiddlewareGroup
{
    /**
     * Array que almacena los grupos de middlewares registrados
     * Formato: ['nombre_grupo' => [MiddlewareClass1::class, MiddlewareClass2::class]]
     */
    protected static array $groups = [];

    /**
     * Array de middlewares globales que se ejecutan en TODAS las rutas
     */
    protected static array $globalMiddlewares = [];

    /**
     * Registra un grupo de middlewares con un nombre de alias
     * 
     * @param string $name El nombre del grupo (ej: 'web', 'api', 'admin')
     * @param array $middlewares Array de clases middleware o closures
     * @return void
     */
    public static function group(string $name, array $middlewares): void
    {
        static::$groups[$name] = $middlewares;
    }

    /**
     * Resuelve un nombre de grupo o middleware a su array de clases correspondientes
     * 
     * Este método es inteligente: puede recibir un string con el nombre de un grupo,
     * una clase middleware individual, o un array mezclado de ambos.
     * 
     * @param string|array $nameOrClass Puede ser:
     *                                - String con nombre de grupo: 'web', 'api'
     *                                - String con clase: 'App\Middlewares\AuthMiddleware::class'
     *                                - Array mezclado: ['web', 'App\Middlewares\Custom::class']
     * @return array Array de clases middleware resueltas
     * 
     * @throws \InvalidArgumentException Si el grupo no existe
     */
    public static function resolve(string|array $nameOrClass): array
    {
        // Si es un string, verificar si es un grupo o una clase individual
        if (is_string($nameOrClass)) {
            // Si es un grupo registrado, retornar sus middlewares
            if (isset(static::$groups[$nameOrClass])) {
                return static::$groups[$nameOrClass];
            }

            // Si no es un grupo, asumir que es una clase middleware individual
            if (class_exists($nameOrClass)) {
                return [$nameOrClass];
            }

            throw new \InvalidArgumentException("El grupo o middleware '{$nameOrClass}' no existe");
        }

        // Si es un array, resolver cada elemento recursivamente
        $resolved = [];
        foreach ($nameOrClass as $item) {
            $resolved = array_merge($resolved, static::resolve($item));
        }

        return $resolved;
    }

    /**
     * Obtiene todos los middlewares globales registrados
     * 
     * @return array Array de middlewares globales
     */
    public static function getGlobalMiddlewares(): array
    {
        return static::$globalMiddlewares;
    }

    /**
     * Agrega un middleware a la lista de middlewares globales
     * 
     * Los middlewares globales se ejecutan en TODAS las rutas,
     * antes que cualquier otro middleware de ruta o grupo.
     * 
     * @param string|array $middleware Clase middleware o array de middlewares
     * @return void
     */
    public static function pushGlobal(string|array $middleware): void
    {
        if (is_array($middleware)) {
            static::$globalMiddlewares = array_merge(static::$globalMiddlewares, $middleware);
        } else {
            static::$globalMiddlewares[] = $middleware;
        }
    }

    /**
     * Limpia todos los grupos de middlewares registrados
     * Útil principalmente para testing
     * 
     * @return void
     */
    public static function flush(): void
    {
        static::$groups = [];
        static::$globalMiddlewares = [];
    }

    /**
     * Carga los grupos de middlewares desde el archivo de configuración
     * 
     * Este método lee la configuración de 'middleware_groups' desde
     * config/app.php y registra los grupos automáticamente.
     * 
     * @return void
     */
    public static function loadFromConfig(): void
    {
        $groups = Config::get('middleware_groups', []);

        foreach ($groups as $name => $middlewares) {
            static::group($name, $middlewares);
        }

        // Cargar middlewares globales desde configuración
        $global = Config::get('global_middlewares', []);
        static::$globalMiddlewares = $global;
    }

    /**
     * Verifica si existe un grupo de middlewares
     * 
     * @param string $name Nombre del grupo a verificar
     * @return bool True si el grupo existe, false en caso contrario
     */
    public static function hasGroup(string $name): bool
    {
        return isset(static::$groups[$name]);
    }

    /**
     * Obtiene todos los nombres de grupos registrados
     * 
     * @return array Array con los nombres de los grupos
     */
    public static function getGroupNames(): array
    {
        return array_keys(static::$groups);
    }

    /**
     * Obtiene los middlewares de un grupo específico
     * 
     * @param string $name Nombre del grupo
     * @return array|null Array de middlewares o null si no existe el grupo
     */
    public static function getGroup(string $name): ?array
    {
        return static::$groups[$name] ?? null;
    }
}
