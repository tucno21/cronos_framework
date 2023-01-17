<?php

namespace Cronos\View;

use Cronos\View\View;

class CronosEngine implements View
{

    //almacenamos la ruta de las vistas
    protected string $viewDirectory; //\laragon\www\cronos_framework/resources/views

    //inicializamos la ruta de las vistas
    public function __construct(string $viewsDirectory)
    {
        $this->viewDirectory = $viewsDirectory;
    }

    public function render(string $view, array $params = [], string $layout = null): string
    {
        $viewContent = $this->renderView($view, $params);

        //obtener todos los @include('cccc') de $viewContent
        preg_match_all('/@include\((.*?)\)/', $viewContent, $matches);
        $directivaInclude = []; //['@include('layouts.head')', '@include('layouts.footer')']
        $contentInclude = [];

        if (count($matches[1]) > 0) {
            // enviar todos los @include('layouts.footer') a $directivaInclude
            foreach ($matches[0] as $key => $value) {
                //almacenamo solo el value en $directivaInclude
                array_push($directivaInclude, $value);
            }
            foreach ($matches[1] as $key => $value) {
                //realizamos limpiaza de los valores de '' y ""
                $value = trim($value, "'");
                $value = trim($value, '"');
                //cambiar el punto por /
                $value = str_replace('.', '/', $value);
                //agreagar la extension .php
                $value = $value . '.php';
                //preguntar si existe el los archivos 
                if (!file_exists($this->viewDirectory . '/' . $value)) {
                    throw new \Error("No existe el archivo {$value}");
                }
                //obtenemos la ruta del archivo
                $patchInclude = $this->viewDirectory . '/' . $value;
                //renderizamos el archivo
                $layoutContent = $this->renderLayout($patchInclude, $params);
                //almacenamos el contenido de $layoutContent en $contentInclude
                array_push($contentInclude, $layoutContent);
            }
        }

        //reemplazar las $directivaInclude por $contentInclude en $viewContent
        $viewContent = str_replace($directivaInclude, $contentInclude, $viewContent);

        //retornamos el contenido de $viewContent
        return $viewContent;
    }

    protected function renderView(string $view, array $params = []): string
    {
        return $this->phpFileOutput("{$this->viewDirectory}/{$view}.php", $params);
    }

    protected function renderLayout(string $layout, array $params = []): string
    {
        return $this->phpFileOutput($layout, $params);
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
