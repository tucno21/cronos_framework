<?php

declare(strict_types=1);

namespace Cronos\Debug;

use Throwable;

/**
 * Renderizador de página interactiva de excepciones para Cronos Framework.
 * Proporciona una interfaz visual oscura, limpia y moderna con:
 * - Snippet de código fuente interactivo con resaltado de sintaxis y línea del error.
 * - Pila de ejecución (Stack Trace) navegable paso a paso.
 * - Variables de contexto de la petición (Request, Headers, Query, Post, Session, Servidor/PHP).
 */
class ErrorRenderer
{
    /**
     * Renderiza una excepción como una página HTML completa.
     */
    public function render(Throwable $e): string
    {
        $exceptionClass = get_class($e);
        $shortClass = $this->getShortClassName($exceptionClass);
        $message = $e->getMessage() ?: 'No exception message provided';
        $file = $e->getFile();
        $line = $e->getLine();
        $codeSnippet = $this->renderCodeSnippet($file, $line);
        $traceFrames = $this->collectTraceFrames($e);
        $renderedTrace = $this->renderTraceList($traceFrames);
        $contextTabs = $this->renderContextInformation();
        $phpVersion = PHP_VERSION;
        $cronosVersion = defined('CRONOS_VERSION') ? CRONOS_VERSION : '5.4';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$shortClass}: {$this->escape($message)} - Cronos Framework</title>
    <style>
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --bg-elevated: #21262d;
            --border-color: #30363d;
            --text-main: #f0f6fc;
            --text-muted: #8b949e;
            --text-accent: #f85149;
            --danger-bg: rgba(248, 81, 73, 0.15);
            --danger-border: #f85149;
            --highlight-line: rgba(248, 81, 73, 0.25);
            --code-bg: #090d13;
            --link-color: #58a6ff;
            --badge-vendor: #388bfd33;
            --badge-app: #23863644;
            --badge-app-text: #3fb950;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.5;
            padding: 0;
        }
        .cronos-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cronos-logo {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cronos-badge {
            background: #e53e3e;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cronos-versions {
            font-size: 12px;
            color: var(--text-muted);
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        }
        .cronos-main {
            max-width: 1300px;
            margin: 0 auto;
            padding: 24px 28px 48px;
        }
        .cronos-error-hero {
            background: var(--bg-card);
            border-left: 4px solid var(--danger-border);
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .cronos-error-type {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-accent);
            font-weight: 700;
            margin-bottom: 6px;
        }
        .cronos-error-msg {
            font-size: 22px;
            font-weight: 600;
            color: #fff;
            word-break: break-word;
            margin-bottom: 12px;
        }
        .cronos-error-loc {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 13px;
            color: var(--text-muted);
            background: var(--bg-elevated);
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-block;
        }
        .cronos-grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 20px;
        }
        @media (max-width: 900px) {
            .cronos-grid { grid-template-columns: 1fr; }
        }
        .cronos-sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            height: fit-content;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }
        .cronos-sidebar-title {
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--bg-elevated);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cronos-trace-list {
            overflow-y: auto;
            list-style: none;
        }
        .cronos-trace-item {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.5);
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .cronos-trace-item:hover, .cronos-trace-item.active {
            background: var(--bg-elevated);
        }
        .cronos-trace-call {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cronos-trace-path {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cronos-badge-app {
            background: var(--badge-app);
            color: var(--badge-app-text);
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 600;
            margin-right: 6px;
        }
        .cronos-editor {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .cronos-editor-bar {
            background: var(--bg-elevated);
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-color);
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 12px;
            color: var(--text-main);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .code-container {
            background: var(--code-bg);
            padding: 12px 0;
            overflow-x: auto;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .code-line {
            display: flex;
            padding: 0 16px;
        }
        .code-line:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        .code-line.active {
            background: var(--highlight-line);
            border-left: 3px solid var(--danger-border);
        }
        .line-number {
            width: 45px;
            text-align: right;
            padding-right: 16px;
            color: var(--text-muted);
            user-select: none;
            opacity: 0.7;
        }
        .line-content {
            white-space: pre;
            flex: 1;
        }
        .cronos-tabs {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
        }
        .cronos-tab-header {
            display: flex;
            background: var(--bg-elevated);
            border-bottom: 1px solid var(--border-color);
            overflow-x: auto;
        }
        .cronos-tab-btn {
            padding: 10px 18px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .cronos-tab-btn.active {
            color: #fff;
            border-bottom-color: #58a6ff;
            background: rgba(255, 255, 255, 0.03);
        }
        .cronos-tab-content {
            display: none;
            padding: 16px 20px;
        }
        .cronos-tab-content.active {
            display: block;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        }
        .data-table th, .data-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table th {
            color: var(--text-muted);
            width: 250px;
            background: rgba(255,255,255,0.01);
        }
        .data-table td {
            color: #79c0ff;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <header class="cronos-header">
        <div class="cronos-logo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:#e53e3e;">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            CRONOS FRAMEWORK
            <span class="cronos-badge">Debug Error</span>
        </div>
        <div class="cronos-versions">
            Cronos v{$cronosVersion} &bull; PHP {$phpVersion}
        </div>
    </header>

    <main class="cronos-main">
        <section class="cronos-error-hero">
            <div class="cronos-error-type">{$this->escape($exceptionClass)}</div>
            <h1 class="cronos-error-msg">{$this->escape($message)}</h1>
            <div class="cronos-error-loc">📍 {$this->escape($this->normalizePath($file))}:{$line}</div>
        </section>

        <div class="cronos-grid">
            <aside class="cronos-sidebar">
                <div class="cronos-sidebar-title">
                    <span>Stack Trace</span>
                    <span style="font-size: 11px; opacity: 0.8;">{$this->escape((string)count($traceFrames))} frames</span>
                </div>
                <ul class="cronos-trace-list">
                    {$renderedTrace}
                </ul>
            </aside>

            <section class="cronos-details-pane">
                <div class="cronos-editor">
                    <div class="cronos-editor-bar">
                        <span>{$this->escape($this->normalizePath($file))}</span>
                        <span style="color: var(--text-muted);">Línea {$line}</span>
                    </div>
                    {$codeSnippet}
                </div>

                <div class="cronos-tabs">
                    <nav class="cronos-tab-header">
                        <button class="cronos-tab-btn active" onclick="showTab(event, 'tab-request')">Request</button>
                        <button class="cronos-tab-btn" onclick="showTab(event, 'tab-headers')">Headers</button>
                        <button class="cronos-tab-btn" onclick="showTab(event, 'tab-session')">Session</button>
                        <button class="cronos-tab-btn" onclick="showTab(event, 'tab-server')">Server / Env</button>
                    </nav>
                    {$contextTabs}
                </div>
            </section>
        </div>
    </main>

    <script>
        function showTab(event, tabId) {
            document.querySelectorAll('.cronos-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.cronos-tab-content').forEach(tab => tab.classList.remove('active'));
            event.currentTarget.classList.add('active');
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
        }
    </script>
</body>
</html>
HTML;
    }

    /**
     * Renderiza un extracto de código con la línea resaltada.
     */
    public function renderCodeSnippet(string $file, int $highlightLine, int $padding = 7): string
    {
        if (!file_exists($file) || !is_readable($file)) {
            return '<div class="code-container"><div class="code-line"><span class="line-content" style="color: var(--text-muted); font-style: italic;">[El archivo no se puede leer o no existe]</span></div></div>';
        }

        $lines = file($file);
        if ($lines === false) {
            return '<div class="code-container"><div class="code-line"><span class="line-content" style="color: var(--text-muted); font-style: italic;">[No se pudo leer el contenido del archivo]</span></div></div>';
        }

        $start = max(1, $highlightLine - $padding);
        $end = min(count($lines), $highlightLine + $padding);

        $html = '<div class="code-container">';
        for ($i = $start; $i <= $end; $i++) {
            $currentLine = $lines[$i - 1] ?? '';
            $isActive = ($i === $highlightLine);
            $activeClass = $isActive ? ' active' : '';

            $escaped = $this->escape(rtrim($currentLine, "\r\n"));
            $html .= sprintf(
                '<div class="code-line%s"><span class="line-number">%d</span><span class="line-content">%s</span></div>',
                $activeClass,
                $i,
                $escaped !== '' ? $escaped : '&nbsp;'
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Recolecta y estructura los frames del Stack Trace.
     */
    private function collectTraceFrames(Throwable $e): array
    {
        $frames = [];
        $frames[] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'class' => null,
            'function' => 'throw',
            'is_app' => $this->isAppCode($e->getFile()),
        ];

        foreach ($e->getTrace() as $step) {
            $file = $step['file'] ?? '';
            $line = $step['line'] ?? 0;
            $class = $step['class'] ?? null;
            $function = $step['function'] ?? '{main}';
            $call = $class ? "{$class}->{$function}()" : "{$function}()";

            $frames[] = [
                'file' => $file,
                'line' => $line,
                'class' => $class,
                'function' => $call,
                'is_app' => $this->isAppCode($file),
            ];
        }

        return $frames;
    }

    /**
     * Renderiza la lista de elementos en el sidebar.
     */
    private function renderTraceList(array $frames): string
    {
        $html = '';
        foreach ($frames as $index => $frame) {
            $activeClass = ($index === 0) ? ' active' : '';
            $badge = $frame['is_app'] ? '<span class="cronos-badge-app">APP</span>' : '';
            $file = $frame['file'] ? $this->normalizePath($frame['file']) . ':' . $frame['line'] : '[internal call]';

            $html .= sprintf(
                '<li class="cronos-trace-item%s">
                    <div class="cronos-trace-call">%s%s</div>
                    <div class="cronos-trace-path" title="%s">%s</div>
                </li>',
                $activeClass,
                $badge,
                $this->escape($frame['function']),
                $this->escape($file),
                $this->escape($file)
            );
        }
        return $html;
    }

    /**
     * Renderiza las pestañas con información de contexto (Request, Session, etc.).
     */
    private function renderContextInformation(): string
    {
        // 1. Request
        $requestData = [
            'HTTP Method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'Request URI' => $_SERVER['REQUEST_URI'] ?? '/',
            'Query String' => $_SERVER['QUERY_STRING'] ?? '',
            'Client IP' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];
        $requestHtml = $this->renderTable('tab-request', $requestData, true);

        // 2. Headers
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = (array) getallheaders();
        } else {
            foreach ($_SERVER as $k => $v) {
                if (str_starts_with($k, 'HTTP_')) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                    $headers[$name] = (string) $v;
                }
            }
        }
        $headersHtml = $this->renderTable('tab-headers', $headers ?: ['Headers' => 'No headers available']);

        // 3. Session
        $sessionData = isset($_SESSION) && !empty($_SESSION) ? $_SESSION : ['Session' => 'Empty / Inactive'];
        $sessionHtml = $this->renderTable('tab-session', $sessionData);

        // 4. Server / Env
        $serverData = [
            'PHP Version' => PHP_VERSION,
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in',
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
        ];
        $serverHtml = $this->renderTable('tab-server', $serverData);

        return $requestHtml . $headersHtml . $sessionHtml . $serverHtml;
    }

    private function renderTable(string $id, array $data, bool $active = false): string
    {
        $activeClass = $active ? ' active' : '';
        $html = sprintf('<div id="%s" class="cronos-tab-content%s"><table class="data-table"><tbody>', $id, $activeClass);

        foreach ($data as $key => $value) {
            $strVal = is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $value;
            $html .= sprintf(
                '<tr><th>%s</th><td>%s</td></tr>',
                $this->escape((string) $key),
                $this->escape($strVal)
            );
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    private function isAppCode(string $filePath): bool
    {
        if (empty($filePath)) {
            return false;
        }

        if (str_contains($filePath, 'vendor') || str_contains($filePath, 'System')) {
            return false;
        }

        return true;
    }

    private function normalizePath(string $path): string
    {
        $root = defined('CRONOS_ROOT') ? CRONOS_ROOT : dirname(__DIR__, 2);
        return str_replace([$root . DIRECTORY_SEPARATOR, $root . '/'], '', $path);
    }

    private function getShortClassName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
