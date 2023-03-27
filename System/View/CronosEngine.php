<?php

namespace Cronos\View;

use Cronos\View\View;


class CronosEngine implements View
{
    protected string $viewDirectory;
    protected string $cacheDirectory;
    protected array $sections;

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
}
