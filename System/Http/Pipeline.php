<?php

namespace Cronos\Http;

use Closure;
use Cronos\Http\Request;
use Cronos\Http\Response;

/**
 * Pipeline - Ejecuta middlewares en cadena usando el patrón "onion"
 * 
 * El patrón "onion" (o cebolla) significa que cada middleware envuelve al siguiente.
 * El request entra desde afuera hacia adentro, y la response retorna desde adentro hacia afuera.
 * 
 * Ejemplo visual:
 * ┌─────────────────────────────────────────────┐
 * │ Middleware 1 (executes first)              │
 * │   ┌───────────────────────────────────────┐ │
 * │   │ Middleware 2                          │ │
 * │   │   ┌─────────────────────────────────┐ │ │
 * │   │   │ Middleware 3                  │ │ │
 * │   │   │   ┌───────────────────────────┐│ │ │
 * │   │   │   │ Action/Controller       ││ │ │
 * │   │   │   └───────────────────────────┘│ │ │
 * │   │   │ Response travels back          │ │ │
 * │   │   └─────────────────────────────────┘ │ │
 * │   │ Response continues back              │ │
 * │   └───────────────────────────────────────┘ │
 * │ Response continues back                     │
 * └─────────────────────────────────────────────┘
 */
class Pipeline
{
    /**
     * La petición que será procesada por el pipeline
     */
    protected Request $request;

    /**
     * Array de middlewares que se ejecutarán
     * Puede contener clases middleware o closures
     */
    protected array $middlewares = [];

    /**
     * El destino final: la acción del controlador o closure
     */
    protected $destination;

    /**
     * Envía la petición al pipeline
     * 
     * @param Request $request La petición HTTP
     * @return self Instancia del pipeline para encadenamiento
     */
    public function send(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Define los middlewares que se ejecutarán
     * 
     * @param array $middlewares Array de clases middleware o closures
     * @return self Instancia del pipeline para encadenamiento
     */
    public function through(array $middlewares): self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * Define el destino final del pipeline (acción del controlador)
     * 
     * @param callable $destination La acción que se ejecutará después de los middlewares
     * @return Response La respuesta final después de procesar todo el pipeline
     */
    public function then(callable $destination): Response
    {
        $this->destination = $destination;

        // Si no hay middlewares, ejecuta directamente el destino
        if (count($this->middlewares) === 0) {
            return call_user_func($this->destination);
        }

        // Crea la cadena de middlewares usando el patrón "onion"
        // Cada middleware envuelve al siguiente, y el último envuelve el destino
        $pipeline = $this->createPipeline();

        return $pipeline($this->request);
    }

    /**
     * Crea el pipeline encadenado de middlewares
     * 
     * Este método construye recursivamente una cadena de closures donde cada
     * middleware envuelve al siguiente. El resultado es un closure que,
     * cuando se ejecuta, pasa el request a través de todos los middlewares.
     * 
     * @return Closure El pipeline completo listo para ejecutar
     */
    protected function createPipeline(): Closure
    {
        // Empezamos con el destino final
        $pipeline = $this->destination;

        // Iteramos los middlewares en orden inverso
        // De esta manera, el primer middleware de la lista se ejecuta primero
        // pero se construye de adentro hacia afuera
        foreach (array_reverse($this->middlewares) as $middleware) {
            $pipeline = $this->createMiddlewareHandler($middleware, $pipeline);
        }

        return $pipeline;
    }

    /**
     * Crea el handler para un middleware específico
     * 
     * @param mixed $middleware Clase middleware o closure
     * @param callable $next El siguiente middleware o destino en la cadena
     * @return Closure El handler del middleware
     */
    protected function createMiddlewareHandler($middleware, callable $next): Closure
    {
        return function ($request) use ($middleware, $next) {
            // Si el middleware es un closure, ejecutarlo directamente
            if ($middleware instanceof Closure) {
                return $middleware($request, $next);
            }

            // Si el middleware es una clase, instanciarlo y llamar a handle()
            // Verificamos si ya es una instancia o es un string con el nombre de la clase
            if (is_string($middleware)) {
                $middlewareInstance = new $middleware();
            } else {
                $middlewareInstance = $middleware;
            }

            // Ejecutar el middleware pasando el request y el closure $next
            // El middleware puede optar por llamar a $next($request) para continuar
            // o puede retornar una Response para detener la cadena
            return $middlewareInstance->handle($request, $next);
        };
    }

    /**
     * Ejecuta el pipeline de forma síncrona (alias de then)
     * 
     * Este método es útil cuando prefieres una sintaxis más explícita.
     * 
     * @param callable $destination La acción que se ejecutará después de los middlewares
     * @return Response La respuesta final
     */
    public function run(callable $destination): Response
    {
        return $this->then($destination);
    }

    /**
     * Permite ejecutar middlewares de forma condicional
     * 
     * @param bool $condition Condición para agregar middlewares
     * @param array $middlewares Array de middlewares a agregar si la condición es true
     * @return self
     */
    public function when(bool $condition, array $middlewares): self
    {
        if ($condition) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        }
        return $this;
    }

    /**
     * Permite ejecutar middlewares de forma condicional (versión negada)
     * 
     * @param bool $condition Condición para NO agregar middlewares
     * @param array $middlewares Array de middlewares a agregar si la condición es false
     * @return self
     */
    public function unless(bool $condition, array $middlewares): self
    {
        if (!$condition) {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        }
        return $this;
    }
}
