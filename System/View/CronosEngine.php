<?php

namespace Cronos\View;

use Cronos\View\View;

class CronosEngine implements View
{

    //almacenamos la ruta de las vistas
    protected string $viewDirectory;

    //almacenamos el layout por defecto
    protected string $defaultLayout = 'main';

    //almacenamos la etiqueta de contenido que sera reemplazada por la vista
    protected string $contenTag = '@content';

    //inicializamos la ruta de las vistas
    public function __construct(string $viewsDirectory)
    {
        $this->viewDirectory = $viewsDirectory;
    }

    public function render(string $view, array $params = [], string $layout = null): string
    {
        $viewContent = $this->renderView($view, $params);
        $layoutContent = $this->renderLayout($layout ?? $this->defaultLayout, $params);

        //str_replace reemplaza todas las apariciones de la cadena de búsqueda con la cadena de reemplazo
        return str_replace($this->contenTag, $viewContent, $layoutContent);
    }

    protected function renderView(string $view, array $params = []): string
    {
        return $this->phpFileOutput("{$this->viewDirectory}/{$view}.php", $params);
    }

    protected function renderLayout(string $layout, array $params = []): string
    {
        return $this->phpFileOutput("{$this->viewDirectory}/layouts/{$layout}.php", $params);
    }

    protected function phpFileOutput(string $phpFile, array $params = []): string
    {
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        //ob_start() inicia el almacenamiento en búfer de la salida
        ob_start();

        //include_once incluye y evalúa el archivo especificado durante la ejecución del script
        include_once $phpFile;

        //ob_get_clean() devuelve el contenido del búfer de salida actual y lo elimina del mismo
        return ob_get_clean();
    }
}
