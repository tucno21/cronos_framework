<?php

namespace Cronos\View;

/**
 * estado de una renderizacion de nivel superior (secciones, stacks, once)
 * que delega la resolucion y compilacion de vistas al motor.
 *
 * el codigo compilado lo recibe como $__env.
 */
class RenderEnvironment extends SectionManager
{
    public function __construct(protected BladeEngine $engine)
    {
    }

    public function makeView(string $view, array $vars): string
    {
        return $this->engine->renderView($view, $vars, $this);
    }

    public function viewExists(string $view): bool
    {
        return $this->engine->exists($view);
    }
}
