<?php

namespace Cronos\View;

use Cronos\View\Compiler\BladeCompiler;
use Cronos\View\Exceptions\ViewCompileException;
use Cronos\View\Exceptions\ViewNotFoundException;

/**
 * motor de plantillas estilo Blade: compila las vistas a PHP plano con
 * cache por dependencias y las ejecuta en un scope aislado.
 *
 * aislamiento: el include del archivo compilado corre dentro de una
 * funcion anonima con variables internas con prefijo __, por lo que los
 * parametros de la vista nunca pueden pisarlas (previene inclusion de
 * archivos via colision de nombres).
 *
 * cache: cada vista compila a storage cache con una cabecera que lista
 * sus dependencias (layout, includes, componentes); el cache se invalida
 * si la vista o cualquiera de sus dependencias es mas nueva.
 */
class BladeEngine extends SectionManager implements View
{
    public function __construct(
        protected string $viewsPath,
        protected string $cachePath
    ) {
    }

    /**
     * ruta absoluta de una vista por notacion de punto (o barra)
     *
     * @throws ViewNotFoundException
     */
    public function path(string $view): string
    {
        $path = $this->viewsPath . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!is_file($path)) {
            throw ViewNotFoundException::forView($view, $path);
        }

        return $path;
    }

    public function exists(string $view): bool
    {
        return is_file($this->viewsPath . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php');
    }

    public function render(string $view, array $params = []): string
    {
        return (new RenderEnvironment($this))->makeView($view, $params);
    }

    /**
     * resuelve, compila (con cache) y ejecuta una vista dentro del entorno dado
     */
    public function renderView(string $view, array $vars, RenderEnvironment $env): string
    {
        $sourcePath = $this->path($view);
        $compiledPath = $this->compiledPath($view, $sourcePath);

        //las claves internas nunca pueden venir de los datos de la vista:
        //extract() las pisaria y permitiria incluir un archivo arbitrario
        $vars = array_diff_key($vars, [
            '__viewPath' => 1,
            '__viewData' => 1,
            '__viewEnv' => 1,
            '__env' => 1,
        ]);

        ob_start();

        try {
            (static function (string $__viewPath, array $__viewData, RenderEnvironment $__viewEnv): void {
                extract($__viewData, EXTR_OVERWRITE);
                $__env = $__viewEnv;
                include $__viewPath;
            })($compiledPath, $vars, $env);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_get_clean();

            throw new ViewCompileException(
                "Error renderizando la vista [{$view}]: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * retorna la ruta del archivo compilado, recompilando si la vista o
     * alguna de sus dependencias registradas en la cabecera del cache
     * es mas nueva que el archivo compilado
     */
    protected function compiledPath(string $view, string $sourcePath): string
    {
        $directory = $this->cachePath . DIRECTORY_SEPARATOR . 'views';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $target = $directory . DIRECTORY_SEPARATOR . md5($sourcePath) . '.php';

        $dependencies = $this->readDependencies($target);
        $compiledTime = is_file($target) ? (int) filemtime($target) : 0;

        if ($compiledTime > 0) {
            foreach ($dependencies as $dependencyPath) {
                if (is_file($dependencyPath) && filemtime($dependencyPath) > $compiledTime) {
                    $compiledTime = 0;
                    break;
                }
            }
        }

        if ($compiledTime > 0 && filemtime($sourcePath) <= $compiledTime) {
            return $target;
        }

        $compiler = new BladeCompiler();

        try {
            $compiled = $compiler->compileString((string) file_get_contents($sourcePath), $view);
        } catch (ViewCompileException $e) {
            throw new ViewCompileException("Error compilando la vista [{$view}]: {$e->getMessage()}", 0, $e);
        }

        $dependencyPaths = [];

        foreach ($compiler->getDependencies() as $dependencyView) {
            try {
                $dependencyPaths[] = $this->path($dependencyView);
            } catch (ViewNotFoundException) {
                //dinamicas o inexistentes: no pueden rastrearse para invalidar
            }
        }

        file_put_contents(
            $target,
            '<?php /*deps:' . json_encode($dependencyPaths) . '*/ ?>' . PHP_EOL . $compiled
        );

        return $target;
    }

    /**
     * lee la cabecera de dependencias del archivo compilado
     *
     * @return list<string>
     */
    protected function readDependencies(string $target): array
    {
        if (!is_file($target)) {
            return [];
        }

        $header = (string) file_get_contents($target, false, null, 0, 4096);

        if (!preg_match('/^<\?php \/\*deps:(.*?)\*\/ \?>/', $header, $match)) {
            return [];
        }

        $decoded = json_decode($match[1], true);

        return is_array($decoded) ? $decoded : [];
    }
}
