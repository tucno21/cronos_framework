<?php

namespace Cronos\View;

use Cronos\View\View;


class CronosEngine implements View
{
    protected string $viewDirectory;
    protected string $cacheDirectory;
    protected array $sections = []; // Inicializar sections
    protected static array $customDirectives = []; // Para almacenar directivas personalizadas

    public function __construct(string $viewsDirectory, string $cacheDirectory)
    {
        $this->viewDirectory = $viewsDirectory;
        $this->cacheDirectory = $cacheDirectory;
    }

    public function render(string $view, array $params = []): string
    {
        $viewFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Error("No existe el archivo: $viewFile");
        }

        $cacheFile = $this->cacheDirectory . DIRECTORY_SEPARATOR . md5($view) . '.php';
        $cacheKey = md5($viewFile . json_encode($this->getIncludeFiles($viewFile)));

        // if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($viewFile)) {
        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($viewFile) || file_get_contents($cacheFile) !== $this->getCacheContent($cacheKey)) {

            //extraer el contenido del archivo de la vista
            $content = file_get_contents($viewFile);

            // Agregar la directiva @extends
            $content = $this->compileExtends($content);

            // Agregar la directiva @include
            $content = $this->compileIncludes($content);

            // Agregar la directiva @section
            $content = $this->compileSections($content);

            // Agregar la directiva @yield
            $content = $this->compileYields($content);

            // Agregar la directiva @foreach
            $content = $this->compileForeach($content);

            // Agregar la directiva @if
            $content = $this->compileIf($content);

            // Agregar la directiva @for
            $content = $this->compileFor($content);

            // Agregar la directiva @while
            $content = $this->compileWhile($content);

            // Agregar la directiva @switch
            $content = $this->compileSwitch($content);

            // Agregar la directiva @empty
            $content = $this->compileEmpty($content);

            // Agregar la directiva @isset
            $content = $this->compileIsset($content);

            // Agregar la directiva @component
            $content = $this->compileComponents($content);

            // Agregar directivas personalizadas
            $content = $this->compileCustomDirectives($content);

            // Agregar la directiva {{ }}
            $content = $this->compileVariables($content);

            //todo el contenido de la vista se guarda en el archivo de cache
            file_put_contents($cacheFile, $content);
        }

        ob_start();
        extract($params);
        include $cacheFile;
        return ob_get_clean();
    }

    protected function getCacheContent(string $cacheKey)
    {
        $cacheFile = $this->cacheDirectory . DIRECTORY_SEPARATOR . $cacheKey . '.php';
        if (!file_exists($cacheFile)) {
            return '';
        }
        return file_get_contents($cacheFile);
    }

    protected function getIncludeFiles(string $viewFile): array
    {
        $content = file_get_contents($viewFile);

        $files = [];

        preg_match_all('/@extends\((.*)\)/', $content, $matches);

        foreach ($matches[1] as $parentView) {
            $parentFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, trim($parentView, "'\"")) . '.php';
            $files[] = $parentFile;
        }

        preg_match_all('/@include\((.*)\)/', $content, $matches);

        foreach ($matches[1] as $includeView) {
            $includeFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, trim($includeView, "'\"")) . '.php';
            $files[] = $includeFile;
        }

        return $files;
    }


    protected function compileExtends(string $content): string
    {
        return preg_replace_callback('/@extends\((.*)\)/', function ($matches) {
            $parentView = trim($matches[1], "'\"");
            $parentFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $parentView) . '.php';
            if (!file_exists($parentFile)) {
                throw new \Error("No existe el archivo: $parentFile");
            }
            return file_get_contents($parentFile);
        }, $content);
    }

    protected function compileIncludes(string $content): string
    {
        // $contents = preg_replace_callback('/@include\((.*?)\)/', function ($match) {
        //     $filename = trim($match[1], '\'"');

        //     $filepath = $this->viewDirectory . '/' . str_replace('.', '/', $filename) . '.php';

        //     if (!file_exists($filepath)) {
        //         throw new \Error("No existe el archivo {$filepath}");
        //     }

        //     return file_get_contents($filepath);
        // }, $contents);

        // return $contents;

        return preg_replace_callback('/@include\((.*)\)/', function ($matches) {
            $includeView = trim($matches[1], "'\"");
            $includeFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $includeView) . '.php';
            if (!file_exists($includeFile)) {
                throw new \Error("No existe el archivo: $includeFile");
            }
            return file_get_contents($includeFile);
        }, $content);
    }

    protected function compileSections(string $content): string
    {
        $content = preg_replace_callback('/@section\((.*?)\)(.*?)@endsection/s', function ($match) {
            $sectionName = trim($match[1], '\'"');
            $sectionContent = trim($match[2]);
            // dd($sectionName);

            $this->sections[$sectionName] = $sectionContent;

            return '';
        }, $content);

        return $content;
    }
    protected function compileYields(string $content): string
    {
        $content = preg_replace_callback('/@yield\((.*?)\)/', function ($match) {
            $sectionName = trim($match[1], '\'"');

            return $this->sections[$sectionName] ?? '';
        }, $content);

        return $content;
    }

    protected function compileForeach(string $content): string
    {
        $contents = preg_replace_callback('/@foreach\((.*?)\)(.*?)@endforeach/s', function ($match) {
            $foreach = trim($match[1]);
            $foreachContent = trim($match[2]);

            return "<?php foreach ($foreach): ?> $foreachContent <?php endforeach; ?>";
        }, $content);

        return $contents;
    }

    protected function compileIf(string $content): string
    {
        // $contents = preg_replace_callback('/@if\((.*?)\)(.*?)@endif/s', function ($match) {
        //     $if = trim($match[1]);
        //     $ifContent = trim($match[2]);

        //     return "<?php if ($if): ?/> $ifContent </?php endif; ?/>";
        // }, $content);

        // return $contents;

        // Agregar la directiva @if
        $content = preg_replace_callback('/@if\((.*?)\)/', function ($match) {
            $condition = trim($match[1], '\'"');
            // dd($condition);

            return "<?php if ({$condition}): ?>";
        }, $content);

        // Agregar la directiva @elseif
        $content = preg_replace_callback('/@elseif\((.*?)\)/', function ($match) {
            $condition = trim($match[1], '\'"');
            // dd($condition);

            return "<?php elseif ({$condition}): ?>";
        }, $content);

        // Agregar la directiva @else
        $content = preg_replace('/@else/', '<?php else: ?>', $content);

        // Agregar la directiva @endif
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);

        return $content;
    }

    protected function compileFor(string $content): string
    {
        $contents = preg_replace_callback('/@for\((.*?)\)(.*?)@endfor/s', function ($match) {
            $for = trim($match[1]);
            $forContent = trim($match[2]);

            return "<?php for ($for): ?> $forContent <?php endfor; ?>";
        }, $content);

        return $contents;
    }

    protected function compileWhile(string $content): string
    {
        $contents = preg_replace_callback('/@while\((.*?)\)(.*?)@endwhile/s', function ($match) {
            $while = trim($match[1]);
            $whileContent = trim($match[2]);

            return "<?php while ($while): ?> $whileContent <?php endwhile; ?>";
        }, $content);

        return $contents;
    }

    protected function compileSwitch(string $content): string
    {
        $contents = preg_replace_callback('/@switch\((.*?)\)(.*?)@endswitch/s', function ($match) {
            $switch = trim($match[1]);
            $switchContent = trim($match[2]);

            return "<?php switch ($switch): ?> $switchContent <?php endswitch; ?>";
        }, $content);

        return $contents;
    }

    protected function compileEmpty(string $content): string
    {
        $contents = preg_replace_callback('/@empty\((.*?)\)(.*?)@endempty/s', function ($match) {
            $empty = trim($match[1]);
            $emptyContent = trim($match[2]);

            return "<?php if(empty($empty)): ?> $emptyContent <?php endif; ?>";
        }, $content);

        return $contents;
    }

    protected function compileIsset(string $content): string
    {
        $contents = preg_replace_callback('/@isset\((.*?)\)(.*?)@endisset/s', function ($match) {
            $isset = trim($match[1]);
            $issetContent = trim($match[2]);

            return "<?php if(isset($isset)): ?> $issetContent <?php endif; ?>";
        }, $content);

        return $contents;
    }

    protected function compileVariables(string $content): string
    {
        $contents = preg_replace('/\{\{\s*(.*?)\s*\}\}/', "<?= htmlspecialchars($1, ENT_QUOTES) ?>", $content);

        return $contents;
    }

    protected function compileComponents(string $content): string
    {
        // Regex para capturar @component(...) ... @endcomponent
        return preg_replace_callback('/@component\((.*?)(?:,\s*(.*?))?\)(.*?)@endcomponent/s', function ($matches) {
            $componentView = trim($matches[1], "'\"");
            $paramsString = $matches[2] ?? '[]'; // Parámetros pasados al componente, por defecto un array vacío
            $componentContent = $matches[3]; // Contenido entre @component y @endcomponent

            $componentFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $componentView) . '.php';
            if (!file_exists($componentFile)) {
                throw new \Error("No existe el archivo de componente: $componentFile");
            }

            // Procesar los slots dentro del contenido del componente
            $slots = [];
            $defaultSlotContent = preg_replace_callback('/@slot\((.*?)\)(.*?)@endslot/s', function ($slotMatches) use (&$slots) {
                $slotName = trim($slotMatches[1], "'\"");
                $slotContent = trim($slotMatches[2]);
                $slots[$slotName] = $slotContent;
                return ''; // Eliminar el slot del contenido principal
            }, $componentContent);

            // El contenido restante es el slot por defecto
            $slots['default'] = trim($defaultSlotContent);

            // Generar el código PHP para incluir el componente
            // Pasamos los parámetros y los slots al ámbito del componente
            $phpCode = "<?php ";
            $phpCode .= "\$__component_params = {$paramsString}; ";
            $phpCode .= "\$__component_slots = " . var_export($slots, true) . "; "; // Exportar los slots como un array PHP
            $phpCode .= "extract(\$__component_params); "; // Extraer los parámetros como variables
            $phpCode .= "ob_start(); "; // Iniciar buffer de salida para capturar el contenido del componente
            $phpCode .= "include '{$componentFile}'; "; // Incluir el archivo del componente
            $phpCode .= "\$__component_output = ob_get_clean(); "; // Capturar la salida
            $phpCode .= "echo \$__component_output; "; // Imprimir la salida
            $phpCode .= "?>";

            return $phpCode;
        }, $content);
    }

    /**
     * Registra una directiva personalizada.
     *
     * @param string $name El nombre de la directiva (ej. 'datetime').
     * @param callable $handler La función de callback que procesará la directiva.
     */
    public static function directive(string $name, callable $handler): void
    {
        self::$customDirectives[$name] = $handler;
    }

    /**
     * Compila las directivas personalizadas registradas.
     *
     * @param string $content El contenido de la vista.
     * @return string El contenido de la vista con las directivas personalizadas compiladas.
     */
    protected function compileCustomDirectives(string $content): string
    {
        foreach (self::$customDirectives as $name => $handler) {
            // Regex para capturar @directiva(...) o @directiva
            $pattern = '/@' . preg_quote($name) . '(?:\((.*?)\))?/s';
            $content = preg_replace_callback($pattern, function ($matches) use ($handler) {
                $arguments = isset($matches[1]) ? $matches[1] : ''; // Argumentos dentro de los paréntesis
                return call_user_func($handler, $arguments);
            }, $content);
        }
        return $content;
    }
}
