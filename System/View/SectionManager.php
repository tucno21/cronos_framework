<?php

namespace Cronos\View;

/**
 * estado de secciones de una renderizacion.
 *
 * una instancia nueva se crea por cada render de nivel superior,
 * por lo que las secciones nunca filtran entre vistas distintas.
 *
 * el compilador genera llamadas a esta API en el codigo PHP compilado
 * con la variable $__env. renderLayout() debe ser implementado por el
 * motor completo para resolver @extends en tiempo de ejecucion.
 */
class SectionManager
{
    protected array $sections = [];

    /** @var list<string> pila de secciones abiertas (soporta anidamiento) */
    protected array $sectionStack = [];

    /** @var array<string, string> contenido acumulado por stack */
    protected array $stacks = [];

    /** @var list<string> pila de pushes/prepends abiertos */
    protected array $pushStack = [];

    public function startSection(string $name, ?string $content = null): void
    {
        if ($content === null) {
            $this->sectionStack[] = $name;
            ob_start();

            return;
        }

        $this->extendSection($name, $content);
    }

    public function stopSection(bool $overwrite = false): void
    {
        $name = array_pop($this->sectionStack);

        if ($name === null) {
            //stopSection sin apertura previa: descartar el buffer para no romper el render
            ob_get_clean();

            return;
        }

        $content = (string) ob_get_clean();

        if ($overwrite) {
            $this->sections[$name] = $content;

            return;
        }

        $this->extendSection($name, $content);
    }

    /**
     * cierra la seccion abierta e imprime su contenido inmediatamente (@show)
     */
    public function showSection(): void
    {
        $name = end($this->sectionStack);

        if ($name === false) {
            ob_get_clean();

            return;
        }

        $this->stopSection();

        echo $this->sections[$name] ?? '';
    }

    /**
     * contenido de una seccion o el default si no fue definida (@yield)
     */
    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]) && trim($this->sections[$name]) !== '';
    }

    public function missingSection(string $name): bool
    {
        return !$this->hasSection($name);
    }

    /**
     * integra contenido nuevo a una seccion existente.
     * el marcador @parent en el contenido previo es reemplazado por el nuevo,
     * lo que permite a una vista hija anexar contenido al definido en el layout.
     */
    protected function extendSection(string $name, string $content): void
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = str_replace('@parent', $content, $this->sections[$name]);

            return;
        }

        $this->sections[$name] = $content;
    }

    /**
     * verifica si una vista existe, usado por la directiva includeIf.
     * el motor completo implementa este metodo.
     */
    public function viewExists(string $view): bool
    {
        throw new \LogicException('viewExists() debe ser implementado por el motor de vistas');
    }

    /**
     * renderiza una sub-vista (layout de @extends, parcial de @include o
     * vista por elemento de @each) y retorna su contenido.
     * el motor completo implementa este metodo.
     */
    public function makeView(string $view, array $vars): string
    {
        throw new \LogicException('makeView() debe ser implementado por el motor de vistas');
    }

    /**
     * renderiza la vista parcial por cada elemento de la coleccion (@each).
     * si la coleccion esta vacia y hay vista de vacio, la renderiza.
     */
    public function renderEach(string $view, iterable $items, string $variable, ?string $emptyView = null, array $extraData = []): string
    {
        $output = '';
        $hasItems = false;

        foreach ($items as $key => $item) {
            $hasItems = true;
            $output .= $this->makeView($view, [$variable => $item, 'key' => $key] + $extraData);
        }

        if (!$hasItems) {
            return $emptyView !== null ? $this->makeView($emptyView, $extraData) : '';
        }

        return $output;
    }

    // ── stacks (@push / @prepend / @stack) ────────────────

    public function startPush(string $stack, ?string $content = null): void
    {
        if ($content === null) {
            $this->pushStack[] = $stack;
            ob_start();

            return;
        }

        $this->extendStack($stack, $content, false);
    }

    public function stopPush(): void
    {
        $this->closePush(false);
    }

    public function startPrepend(string $stack, ?string $content = null): void
    {
        if ($content === null) {
            $this->pushStack[] = $stack;
            ob_start();

            return;
        }

        $this->extendStack($stack, $content, true);
    }

    public function stopPrepend(): void
    {
        $this->closePush(true);
    }

    /**
     * contenido acumulado de un stack en este punto del render (@stack)
     */
    public function yieldStack(string $stack): string
    {
        return $this->stacks[$stack] ?? '';
    }

    protected function closePush(bool $prepend): void
    {
        $stack = array_pop($this->pushStack);

        if ($stack === null) {
            //cierre sin apertura previa: descartar el buffer para no romper el render
            ob_get_clean();

            return;
        }

        $this->extendStack($stack, (string) ob_get_clean(), $prepend);
    }

    protected function extendStack(string $stack, string $content, bool $prepend): void
    {
        if ($prepend) {
            $this->stacks[$stack] = $content . ($this->stacks[$stack] ?? '');

            return;
        }

        $this->stacks[$stack] = ($this->stacks[$stack] ?? '') . $content;
    }
}
