<?php

declare(strict_types=1);

namespace Tests\Integration;

use Cronos\View\CronosEngine;
use PHPUnit\Framework\TestCase;

class ViewsIntegrationTest extends TestCase
{
    private CronosEngine $engine;
    private string $viewsDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $this->viewsDir = $root . '/resources/views';
        $this->cacheDir = sys_get_temp_dir() . '/cronos_views_test_' . uniqid();

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        $this->engine = new CronosEngine($this->viewsDir, $this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->cacheDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }

    public function test_layout_home_head_exists(): void
    {
        $viewPath = $this->viewsDir . '/home/layouts/head.php';
        $this->assertFileExists($viewPath);
        $content = file_get_contents($viewPath);
        $this->assertNotEmpty($content);
    }

    public function test_css_home_tiene_source_components(): void
    {
        $cssPath = dirname(__DIR__, 2) . '/resources/css/home.css';
        if (!file_exists($cssPath)) {
            $this->markTestSkipped('CSS no encontrado');
        }
        $content = file_get_contents($cssPath);
        $this->assertStringContainsString('components', $content);
    }

    public function test_componentes_existen(): void
    {
        $componentsDir = $this->viewsDir . '/components';
        $this->assertDirectoryExists($componentsDir);

        $required = ['alert.php', 'card.php', 'button.php', 'input.php', 'badge.php', 'textarea.php'];
        foreach ($required as $component) {
            $this->assertFileExists($componentsDir . '/' . $component);
        }
    }

    public function test_componente_alert_usa_type(): void
    {
        $path = $this->viewsDir . '/components/alert.php';
        if (!file_exists($path)) {
            $this->markTestSkipped('Componente no encontrado');
        }
        $content = file_get_contents($path);
        $this->assertStringContainsString('$type', $content);
    }

    public function test_componente_card_soporta_slots(): void
    {
        $path = $this->viewsDir . '/components/card.php';
        if (!file_exists($path)) {
            $this->markTestSkipped('Componente no encontrado');
        }
        $content = file_get_contents($path);
        $hasSlots = str_contains($content, "__slots['header']")
            || str_contains($content, '__slots["header"]');
        $this->assertTrue($hasSlots);
    }
}
