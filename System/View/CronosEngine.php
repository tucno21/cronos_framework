<?php

namespace Cronos\View;

use Cronos\View\View;

class CronosEngine implements View
{
    //almacenamos la ruta de carpetas de las vistas
    protected string $viewDirectory; //\laragon\www\cronos_framework/resources/views

    //inicializamos la ruta de las vistas
    public function __construct(string $viewsDirectory)
    {
        $this->viewDirectory = $viewsDirectory;
    }

    public function render(string $view, array $params = []): string
    {
        $viewContent = $this->renderView($view, $params);

        // Agregamos soporte para la directiva @extends
        preg_match_all('/@extends\((.*?)\)/', $viewContent, $matches);
        $directivaExtends = []; // nueva directiva 
        $contentExtends = []; // nuevo contenido
        if (count($matches[1]) > 0) {
            foreach ($matches[0] as $key => $value) {
                array_push($directivaExtends, $value);
            }
            foreach ($matches[1] as $key => $value) {
                $value = trim($value, "'");
                $value = trim($value, '"');
                $value = str_replace('.', '/', $value);
                $value = $value . '.php';
                if (!file_exists($this->viewDirectory . '/' . $value)) {
                    throw new \Error("No existe el archivo {$value}");
                }
                $patchExtends = $this->viewDirectory . '/' . $value;
                $extendsContent = $this->renderLayout($patchExtends, $params);
                array_push($contentExtends, $extendsContent);
            }
        }
        $viewContent = str_replace($directivaExtends, $contentExtends, $viewContent);

        // Agregamos soporte para la directiva @include
        preg_match_all('/@include\((.*?)\)/', $viewContent, $matches);
        $directivaInclude = []; //['@include('layouts.head')', '@include('layouts.footer')']
        $contentInclude = [];

        if (count($matches[1]) > 0) {
            // enviar todos los @include('layouts.footer') a $directivaInclude
            foreach ($matches[0] as $key => $value) {
                array_push($directivaInclude, $value);
            }
            foreach ($matches[1] as $key => $value) {
                $value = trim($value, "'");
                $value = trim($value, '"');
                //cambiar el punto por /
                $value = str_replace('.', '/', $value);
                $value = $value . '.php';
                //preguntar si existe el los archivos de la vista
                if (!file_exists($this->viewDirectory . '/' . $value)) {
                    throw new \Error("No existe el archivo {$value}");
                }
                //obtenemos la ruta del archivo de la vista
                $patchInclude = $this->viewDirectory . '/' . $value;
                //renderizamos el archivo de la vista
                $layoutContent = $this->renderLayout($patchInclude, $params);
                //almacenamos el contenido de $layoutContent en $contentInclude
                array_push($contentInclude, $layoutContent);
            }
        }
        //reemplazar las $directivaInclude por $contentInclude en $viewContent
        $viewContent = str_replace($directivaInclude, $contentInclude, $viewContent);

        // Agregamos soporte para la directiva @section
        // preg_match_all('/@section\(\'(.*?)\'\)/', $viewContent, $matches); //solo comillas simples
        preg_match_all('/@section\((.*?)\)/', $viewContent, $matches);
        $directivaSection = [];
        $sectionContent = [];
        $sectionName = null;

        if (count($matches[1]) > 0) {
            foreach ($matches[0] as $key => $value) {
                array_push($directivaSection, $value);
            }
            foreach ($matches[1] as $key => $value) {
                $value = trim($value, "'");
                $value = trim($value, '"');
                $sectionName = $value;
                // Buscamos la sección en el contenido del archivo de la vista
                $sectionContent[$sectionName] = $this->getSectionContent($sectionName, $viewContent);
            }
        }
        // Reemplazamos las directivas de sección por su contenido
        if (count($sectionContent) > 0) {
            foreach ($sectionContent as $name => $content) {
                $viewContent = str_replace("@yield('{$name}')", $content, $viewContent);
            }
            //eliminamos las @yield de la vista que no tienen contenido
            $viewContent = preg_replace('/@yield\((.*?)\)/', '', $viewContent);
        } else {
            //eliminamos las directivas de @yield del contenido de la vista
            $viewContent = preg_replace('/@yield\((.*?)\)/', '', $viewContent);
        }

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

    protected function getSectionContent(string $sectionName, string &$viewContent): string
    {
        $sectionContent = '';
        $sectionStart = "@section('$sectionName')";
        $sectionEnd = "@endsection";

        $sectionStartPos = strpos($viewContent, $sectionStart);
        $sectionEndPos = strpos($viewContent, $sectionEnd, $sectionStartPos);

        if ($sectionStartPos !== false && $sectionEndPos !== false) {
            $sectionContent = substr($viewContent, $sectionStartPos + strlen($sectionStart), $sectionEndPos - $sectionStartPos - strlen($sectionStart));
            $viewContent = substr_replace($viewContent, '', $sectionStartPos, $sectionEndPos - $sectionStartPos + strlen($sectionEnd));
        }

        return $sectionContent;
    }
}
