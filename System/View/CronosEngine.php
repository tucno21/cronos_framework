<?php

namespace Cronos\View;

use Cronos\View\View;


class CronosEngine implements View
{
    protected string $viewDirectory;
    protected string $cacheDirectory;
    protected string $componentsDirectory = '';
    protected array $sections = []; // Inicializar sections
    protected array $stacks = [];
    protected static array $customDirectives = []; // Para almacenar directivas personalizadas

    public function __construct(string $viewsDirectory, string $cacheDirectory)
    {
        $this->viewDirectory = $viewsDirectory;
        $this->cacheDirectory = $cacheDirectory;
        $this->componentsDirectory = $viewsDirectory . DIRECTORY_SEPARATOR . 'components';
    }

    public function render(string $view, array $params = []): string
    {
        $viewFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Error("No existe el archivo: $viewFile");
        }

        $cacheFile = $this->cacheDirectory . DIRECTORY_SEPARATOR . md5($view) . '.php';
        $cacheKey = md5($viewFile . json_encode($this->getIncludeFiles($viewFile)));

        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($viewFile) || file_get_contents($cacheFile) !== $this->getCacheContent($cacheKey)) {

            //extraer el contenido del archivo de la vista
            $content = file_get_contents($viewFile);

            // ── EXISTENTES: fusionar primero ──────────────────────
            $content = $this->compileExtends($content);    // ① fusionar layout PRIMERO
            $content = $this->compileIncludes($content);   // ② fusionar includes SEGUNDO
            $content = $this->compileXComponents($content);
            $content = $this->compileSections($content);
            $content = $this->compileYields($content);

            // ── NUEVOS: procesar después de fusionar ────────────────
            $content = $this->compileComments($content);    // ③ eliminar comentarios AHORA
            $content = $this->compilePushStack($content);   // ④ procesar push/stack AHORA

            // ── NUEVO: forelse ANTES de foreach ───────────────────
            $content = $this->compileForelse($content);     // ③ antes de compileForeach

            // ── EXISTENTES ────────────────────────────────────────
            $content = $this->compileForeach($content);
            $content = $this->compileIf($content);
            $content = $this->compileFor($content);
            $content = $this->compileWhile($content);
            $content = $this->compileSwitch($content);
            $content = $this->compileEmpty($content);
            $content = $this->compileIsset($content);
            $content = $this->compileComponents($content);
            $content = $this->compileCustomDirectives($content);

            // ── NUEVOS: antes de compileVariables ─────────────────
            $content = $this->compileAuth($content);        // ④
            $content = $this->compileCsrf($content);        // ⑤
            $content = $this->compileMethod($content);      // ⑥
            $content = $this->compileError($content);       // ⑦
            $content = $this->compileUnless($content);      // ⑧
            $content = $this->compileDebug($content);       // ⑨
            $content = $this->compileAsset($content);       // ⑩
            $content = $this->compileRawEcho($content);     // ⑪ antes de variables

            // ── EXISTENTE: SIEMPRE AL FINAL ───────────────────────
            $content = $this->compileVariables($content);   // ← ÚLTIMO SIEMPRE

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

        preg_match_all('/@extends\((.*?)\)/', $content, $matches);

        foreach ($matches[1] as $parentView) {
            $parentFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, trim($parentView, "'\"")) . '.php';
            $files[] = $parentFile;
        }

        preg_match_all('/@include\((.*?)\)/', $content, $matches);

        foreach ($matches[1] as $includeView) {
            $includeFile = $this->viewDirectory . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, trim($includeView, "'\"")) . '.php';
            $files[] = $includeFile;
        }

        return $files;
    }


    protected function compileExtends(string $content): string
    {
        return preg_replace_callback('/@extends\((.*?)\)/', function ($matches) {
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
        return preg_replace_callback(
            '/@include\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(.*?))?\s*\)/s',  // ← AGREGADO 's' para que . match newlines
            function (array $matches): string {
                $includeView = trim($matches[1], "'\"");
                $includeFile = $this->viewDirectory
                    . DIRECTORY_SEPARATOR
                    . str_replace('.', DIRECTORY_SEPARATOR, $includeView)
                    . '.php';

                if (!file_exists($includeFile)) {
                    throw new \Error("No existe el archivo: $includeFile");
                }

                $fileContent = file_get_contents($includeFile);

                // Si hay segundo parámetro (array de variables), extraerlas en scope local
                if (!empty(trim($matches[2] ?? ''))) {
                    $params = trim($matches[2]);
                    return "<?php (function(\$__data) { "
                        . "extract(\$__data); "
                        . "extract({$params}); ?>"
                        . $fileContent
                        . "<?php })(get_defined_vars()); ?>";
                }

                return $fileContent;
            },
            $content
        );
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

    // ──────────────────────────────────────────────────────────────
    // NUEVOS MÉTODOS DE COMPILACIÓN
    // ──────────────────────────────────────────────────────────────

    /**
     * Compila @csrf a un input hidden con token CSRF.
     *
     * Uso en vista:
     *   <form method="POST">
     *       @csrf
     *   </form>
     *
     * Resultado compilado:
     *   <input type="hidden" name="_token" value="<?= csrf_token() ?>">
     *
     * IMPORTANTE: Si csrf_token() no existe en el proyecto, usa session_id()
     * como fallback. Adaptar según el sistema de seguridad real del proyecto.
     */
    protected function compileCsrf(string $content): string
    {
        return preg_replace(
            '/@csrf\b/',
            '<?php if (function_exists(\'csrf_token\')) { echo \'<input type="hidden" name="_token" value="\' . htmlspecialchars(csrf_token(), ENT_QUOTES) . \'">\'; } else { echo \'<input type="hidden" name="_token" value="\' . htmlspecialchars(session_id(), ENT_QUOTES) . \'">\'; } ?>',
            $content
        );
    }

    /**
     * Compila @method('PUT') a un input hidden _method.
     *
     * Uso en vista:
     *   <form method="POST">
     *       @csrf
     *       @method('PUT')
     *   </form>
     *
     * Resultado compilado:
     *   <input type="hidden" name="_method" value="PUT">
     *
     * Acepta: PUT, PATCH, DELETE (mayúsculas o minúsculas)
     */
    protected function compileMethod(string $content): string
    {
        return preg_replace_callback(
            '/@method\(\s*[\'"](\w+)[\'"]\s*\)/',
            function (array $matches): string {
                $method = strtoupper($matches[1]);
                return '<input type="hidden" name="_method" value="' . $method . '">';
            },
            $content
        );
    }

    /**
     * Compila @auth / @endauth y @guest / @endguest.
     *
     * @auth     → muestra el bloque si hay usuario autenticado
     * @guest    → muestra el bloque si NO hay usuario autenticado
     *
     * Uso en vista:
     *   @auth
     *       <p>Bienvenido {{ $user->name }}</p>
     *   @endauth
     *
     *   @guest
     *       <a href="/login">Iniciar sesión</a>
     *   @endguest
     *
     * ADAPTAR: Cambiar session()->hasUser() según el sistema de auth del proyecto.
     * Si usa $_SESSION['user'] directamente, cambiar la condición.
     */
    protected function compileAuth(string $content): string
    {
        // @auth ... @endauth
        $content = preg_replace(
            '/@auth\b/',
            '<?php if (function_exists(\'session\') && session()->hasUser()): ?>',
            $content
        );
        $content = preg_replace('/@endauth\b/', '<?php endif; ?>', $content);

        // @guest ... @endguest
        $content = preg_replace(
            '/@guest\b/',
            '<?php if (!function_exists(\'session\') || !session()->hasUser()): ?>',
            $content
        );
        $content = preg_replace('/@endguest\b/', '<?php endif; ?>', $content);

        return $content;
    }

    /**
     * Compila @error('campo') / @enderror.
     *
     * Dentro del bloque, la variable $message contiene el texto del error.
     *
     * Uso en vista:
     *   <input name="email" type="email">
     *   @error('email')
     *       <p class="text-red-600">{{ $message }}</p>
     *   @enderror
     *
     * Resultado compilado:
     *   <?php if ($__err = session()->error('email')): $message = $__err; ?>
     *       <p>...</p>
     *   <?php unset($message); endif; ?>
     *
     * ADAPTAR: Si el proyecto usa otro mecanismo de errores, cambiar la condición.
     */
    protected function compileError(string $content): string
    {
        $content = preg_replace_callback(
            '/@error\(\s*[\'"](\w+)[\'"]\s*\)/',
            function (array $matches): string {
                $field = $matches[1];
                return "<?php if (function_exists('session') && (\$__err_{$field} = session()->error('{$field}'))): \$message = \$__err_{$field}; ?>";
            },
            $content
        );

        $content = preg_replace(
            '/@enderror\b/',
            '<?php unset($message); endif; ?>',
            $content
        );

        return $content;
    }

    /**
     * Compila @push('nombre') / @endpush y @stack('nombre').
     *
     * @push acumula contenido en un stack con ese nombre.
     * @stack renderiza todo el contenido acumulado de ese stack.
     *
     * Uso en vista hija:
     *   @push('scripts')
     *       <script src="/js/mi-script.js"></script>
     *   @endpush
     *
     * Uso en layout:
     *   @stack('scripts')
     *
     * IMPORTANTE: El contenido de @push se captura en tiempo de compilación.
     * El @stack se reemplaza con todo el contenido acumulado de ese nombre.
     *
     * LIMITACIÓN CONOCIDA: Esta implementación funciona cuando @push y @stack
     * están en el mismo archivo compilado (después de que @extends fusiona
     * la vista con el layout). Si @push está en la vista hija y @stack en
     * el layout, ambos quedan en el mismo string después de compileExtends,
     * por lo que sí funcionan correctamente.
     */
    protected function compilePushStack(string $content): string
    {
        // Paso 1: Recolectar todos los @push y guardar su contenido
        $stacks = [];

        $content = preg_replace_callback(
            '/@push\(\s*[\'"](\w+)[\'"]\s*\)(.*?)@endpush/s',
            function (array $matches) use (&$stacks): string {
                $stackName = $matches[1];
                $stackContent = $matches[2];
                if (!isset($stacks[$stackName])) {
                    $stacks[$stackName] = '';
                }
                $stacks[$stackName] .= $stackContent;
                return ''; // Eliminar el bloque @push del contenido
            },
            $content
        );

        // Paso 2: Reemplazar @stack('nombre') con el contenido acumulado
        $content = preg_replace_callback(
            '/@stack\(\s*[\'"](\w+)[\'"]\s*\)/',
            function (array $matches) use ($stacks): string {
                $stackName = $matches[1];
                return $stacks[$stackName] ?? '';
            },
            $content
        );

        return $content;
    }

    /**
     * Compila @forelse / @empty / @endforelse.
     *
     * Uso en vista:
     *   @forelse($blogs as $blog)
     *       <div>{{ $blog->title }}</div>
     *   @empty
     *       <p>No hay blogs aún</p>
     *   @endforelse
     *
     * Resultado compilado:
     *   <?php if (!empty($blogs)): foreach ($blogs as $blog): ?>
     *       <div>...</div>
     *   <?php endforeach; else: ?>
     *       <p>No hay blogs aún</p>
     *   <?php endif; ?>
     *
     * IMPORTANTE: El @empty de @forelse es diferente al @empty($var) existente.
     * Este nuevo @forelse/@empty usa un regex más específico que incluye @endforelse,
     * así que NO conflictúa con el @empty($var)/@endempty existente.
     */
    protected function compileForelse(string $content): string
    {
        return preg_replace_callback(
            '/@forelse\s*\((.*?)\)(.*?)@empty(.*?)@endforelse/s',
            function (array $matches): string {
                $expression  = trim($matches[1]); // "$blogs as $blog"
                $loopContent = $matches[2];
                $emptyContent = $matches[3];

                // Extraer la variable del array (la parte antes de " as ")
                $parts = preg_split('/\s+as\s+/i', $expression, 2);
                $arrayVar = trim($parts[0]);

                return "<?php if (!empty({$arrayVar})): ?>" .
                    "<?php foreach ({$expression}): ?>" .
                    $loopContent .
                    "<?php endforeach; ?>" .
                    "<?php else: ?>" .
                    $emptyContent .
                    "<?php endif; ?>";
            },
            $content
        );
    }

    /**
     * Compila @unless($condition) / @endunless.
     * Es el inverso de @if: muestra el bloque si la condición es FALSE.
     *
     * Uso en vista:
     *   @unless(session()->hasUser())
     *       <p>No estás autenticado</p>
     *   @endunless
     *
     * Resultado compilado:
     *   <?php if (!(session()->hasUser())): ?>
     *       <p>No estás autenticado</p>
     *   <?php endif; ?>
     */
    protected function compileUnless(string $content): string
    {
        $content = preg_replace_callback(
            '/@unless\s*\((.*?)\)/',
            function (array $matches): string {
                $condition = trim($matches[1]);
                return "<?php if (!({$condition})): ?>";
            },
            $content
        );

        $content = preg_replace('/@endunless\b/', '<?php endif; ?>', $content);

        return $content;
    }

    /**
     * Elimina los comentarios Blade {{-- comentario --}} del output.
     *
     * A diferencia de los comentarios HTML <!-- -->, estos NO aparecen
     * en el HTML que recibe el navegador.
     *
     * Uso en vista:
     *   {{-- Este comentario no aparece en el HTML --}}
     *   {{-- 
     *       Tampoco este
     *       aunque sea multilínea
     *   --}}
     *
     * Resultado: string vacío (el comentario es eliminado)
     */
    protected function compileComments(string $content): string
    {
        // Eliminar todos los comentarios Blade --}}
        // Patrón: {{-- ... --}} con delimitador @ para evitar escape
        return preg_replace('@\{\{--.*?--\}\}@s', '', $content);
    }

    /**
     * Compila @dump($var) y @dd($var) para debug en plantillas.
     *
     * @dump($var) → var_dump($var) y continúa
     * @dd($var)   → var_dump($var) y detiene ejecución
     *
     * Uso en vista:
     *   @dump($blogs)
     *   @dd($user)
     */
    protected function compileDebug(string $content): string
    {
        // @dump($variable)
        $content = preg_replace_callback(
            '/@dump\s*\((.*?)\)/',
            function (array $matches): string {
                return "<?php var_dump({$matches[1]}); ?>";
            },
            $content
        );

        // @dd($variable) — dump and die
        $content = preg_replace_callback(
            '/@dd\s*\((.*?)\)/',
            function (array $matches): string {
                return "<?php var_dump({$matches[1]}); exit(1); ?>";
            },
            $content
        );

        return $content;
    }

    /**
     * Compila @asset('ruta') a URL con version hash automático.
     *
     * El hash se genera con filemtime() del archivo físico.
     * Si el archivo cambia, el hash cambia y el navegador descarga la nueva versión.
     *
     * Uso en layout:
     *   <link href="@asset('assets/css/home.css')" rel="stylesheet">
     *   <script src="@asset('assets/js/app.js')"></script>
     *
     * Resultado compilado:
     *   <link href="<?= asset_url('assets/css/home.css') ?>" rel="stylesheet">
     *
     * NOTA: Genera código PHP inline. La función asset_url() se define abajo.
     * Si el proyecto ya tiene una función similar, adaptar para usarla.
     */
    protected function compileAsset(string $content): string
    {
        return preg_replace_callback(
            '/@asset\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            function (array $matches): string {
                $path = addslashes($matches[1]);
                return "<?php " .
                    "\$__ap = rtrim(base_url ?? '', '/') . '/' . ltrim('{$path}', '/'); " .
                    "\$__af = rtrim(\$_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . ltrim('{$path}', '/'); " .
                    "echo \$__ap . (file_exists(\$__af) ? '?v=' . filemtime(\$__af) : ''); " .
                    "?>";
            },
            $content
        );
    }

    /**
     * Compila {!! $variable !!} a echo sin escape HTML.
     *
     * ⚠️ DEBE ejecutarse ANTES de compileVariables ({{ }}).
     * ⚠️ Usar solo con contenido de confianza — no protege contra XSS.
     *
     * Uso en vista:
     *   {!! $htmlContent !!}
     *
     * Resultado compilado:
     *   <?= $htmlContent ?>
     */
    protected function compileRawEcho(string $content): string
    {
        return preg_replace(
            '/\{!!\s*(.*?)\s*!!\}/',
            '<?= $1 ?>',
            $content
        );
    }

    /**
     * Compilador principal de componentes <x-nombre>.
     *
     * Soporta tres formas:
     *   1. Self-closing:  <x-alert type="error" />
     *   2. Con contenido: <x-card>Contenido aquí</x-card>
     *   3. Con slots:     <x-card><x-slot:header>Título</x-slot:header>Cuerpo</x-card>
     *
     * Si el archivo del componente NO existe en resources/views/components/,
     * la etiqueta se deja sin cambios (puede ser un web component nativo).
     *
     * Busca el componente en: resources/views/components/{nombre}.php
     * Guiones en el nombre son válidos: <x-form-input> → form-input.php
     */
    protected function compileXComponents(string $content): string
    {
        // Paso 1: componentes con contenido <x-nombre ...>...</x-nombre>
        $content = preg_replace_callback(
            '/<x-([\w-]+)((?:\s[^>]*)?)\s*>(.*?)<\/x-\1>/s',
            function (array $matches): string {
                return $this->renderXComponent($matches[1], $matches[2], $matches[3]);
            },
            $content
        );

        // Paso 2: componentes self-closing <x-nombre ... />
        $content = preg_replace_callback(
            '/<x-([\w-]+)((?:\s[^>]*)?)\s*\/>/',
            function (array $matches): string {
                return $this->renderXComponent($matches[1], $matches[2], '');
            },
            $content
        );

        return $content;
    }

    /**
     * Renderiza un componente x- individual.
     *
     * @param string $name        Nombre sin prefijo x- (ej: "alert", "form-input")
     * @param string $attrsString Atributos del tag como string (ej: ' type="error" ')
     * @param string $slotContent Contenido entre las etiquetas (slot por defecto + slots nombrados)
     */
    protected function renderXComponent(string $name, string $attrsString, string $slotContent): string
    {
        $componentFile = $this->componentsDirectory . DIRECTORY_SEPARATOR . $name . '.php';

        if (!file_exists($componentFile)) {
            // No es un componente Cronos — devolver el tag original sin modificar
            $close = empty($slotContent) ? '/>' : ">{$slotContent}</x-{$name}>";
            return "<x-{$name}{$attrsString}{$close}";
        }

        // Extraer slots nombrados: <x-slot:nombre>contenido</x-slot:nombre>
        $namedSlots  = [];
        $defaultSlot = preg_replace_callback(
            '/<x-slot:([\w-]+)\s*>(.*?)<\/x-slot:\1>/s',
            function (array $m) use (&$namedSlots): string {
                $namedSlots[$m[1]] = trim($m[2]);
                return '';
            },
            $slotContent
        );

        $namedSlots['default'] = trim($defaultSlot);

        // Parsear atributos del tag a array PHP
        $props = $this->parseXAttributes($attrsString);

        // Generar código PHP que incluye el componente con sus variables
        $escapedFile = str_replace("'", "\\'", $componentFile);
        $propsExport = var_export($props, true);
        $slotsExport = var_export($namedSlots, true);

        return "<?php " .
            "(function() { " .
            "\$__props = {$propsExport}; " .
            "\$__slots = {$slotsExport}; " .
            "\$slot = \$__slots['default'] ?? ''; " .
            "extract(\$__props); " .
            "ob_start(); " .
            "include '{$escapedFile}'; " .
            "echo ob_get_clean(); " .
            "})(); " .
            "?>";
    }

    /**
     * Parsea los atributos de una etiqueta HTML a un array asociativo PHP.
     *
     * Soporta:
     *   name="valor"    → ['name' => 'valor']           string con comillas dobles
     *   name='valor'    → ['name' => 'valor']           string con comillas simples
     *   required        → ['required' => true]          atributo booleano sin valor
     *
     * @param  string $attrsString String de atributos del tag
     * @return array<string, mixed>
     */
    protected function parseXAttributes(string $attrsString): array
    {
        $props = [];
        $attrsString = trim($attrsString);

        if (empty($attrsString)) {
            return $props;
        }

        // Atributos con comillas dobles: name="valor"
        preg_match_all('/([\w-]+)="([^"]*)"/', $attrsString, $dm, PREG_SET_ORDER);
        foreach ($dm as $m) {
            $props[$m[1]] = $m[2];
            $attrsString  = str_replace($m[0], '', $attrsString);
        }

        // Atributos con comillas simples: name='valor'
        preg_match_all("/([\w-]+)='([^']*)'/", $attrsString, $sm, PREG_SET_ORDER);
        foreach ($sm as $m) {
            $props[$m[1]] = $m[2];
            $attrsString  = str_replace($m[0], '', $attrsString);
        }

        // Atributos booleanos: required, disabled, checked
        preg_match_all('/\b([\w-]+)\b/', $attrsString, $bm);
        foreach ($bm[1] as $attr) {
            if (!empty($attr) && !isset($props[$attr])) {
                $props[$attr] = true;
            }
        }

        return $props;
    }
}
