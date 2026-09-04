<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class ViewsIntegrationTest extends TestCase
{
    private string $viewsDir;

    protected function setUp(): void
    {
        $this->viewsDir = dirname(__DIR__, 2) . '/resources/views';
    }

    public function test_layout_principal_existe(): void
    {
        $viewPath = $this->viewsDir . '/layouts/app.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString("@yield('content')", $content);
        $this->assertStringContainsString("@include('partials.nav')", $content);
        $this->assertStringContainsString("@stack('scripts')", $content);
    }

    public function test_estructura_de_vistas_laravel(): void
    {
        $esperadas = [
            'layouts/app.php',
            'partials/nav.php',
            'home/index.php',
            'errors/404.php',
            'spa/index.php',
        ];

        foreach ($esperadas as $relativa) {
            $this->assertFileExists($this->viewsDir . '/' . $relativa);
        }
    }

    public function test_home_extiende_layout(): void
    {
        $content = file_get_contents($this->viewsDir . '/home/index.php');

        $this->assertStringContainsString("@extends('layouts.app')", $content);
        $this->assertStringContainsString("@section('content')", $content);
    }

    public function test_404_usa_asset_y_ruta_nombrada(): void
    {
        $content = file_get_contents($this->viewsDir . '/errors/404.php');

        $this->assertStringContainsString("@asset('assets/css/error.css')", $content);
        $this->assertStringContainsString("{{ route('home.index') }}", $content);
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

    public function test_componente_button_usa_props_y_attributes(): void
    {
        $content = file_get_contents($this->viewsDir . '/components/button.php');

        $this->assertStringContainsString('@props(', $content);
        $this->assertStringContainsString('$attributes->merge(', $content);
    }
}
