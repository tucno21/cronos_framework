<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\Debug\ErrorRenderer;
use Cronos\Errors\ExceptionHandler;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorRendererTest extends TestCase
{
    public function testRenderOutputsValidHtmlWithExceptionDetails(): void
    {
        $renderer = new ErrorRenderer();
        $exception = new RuntimeException("Test runtime explosion", 500);

        $html = $renderer->render($exception);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('RuntimeException', $html);
        $this->assertStringContainsString('Test runtime explosion', $html);
        $this->assertStringContainsString('CRONOS FRAMEWORK', $html);
        $this->assertStringContainsString('Stack Trace', $html);
        $this->assertStringContainsString('class="code-container"', $html);
    }

    public function testRenderCodeSnippetHighlightsCorrectLine(): void
    {
        $renderer = new ErrorRenderer();
        $snippet = $renderer->renderCodeSnippet(__FILE__, __LINE__);

        $this->assertStringContainsString('class="code-container"', $snippet);
        $this->assertStringContainsString('active', $snippet);
        $this->assertStringContainsString('testRenderCodeSnippetHighlightsCorrectLine', $snippet);
    }

    public function testExceptionHandlerReturnsHtmlInDebugMode(): void
    {
        $_ENV['CRONOS_APP_DEBUG'] = 'true';
        unset($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_X_REQUESTED_WITH']);

        $handler = new ExceptionHandler();
        $exception = new Exception("Database connection failed");

        $response = $handler->handle($exception);

        $this->assertSame(500, $response->statusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->headers('content-type'));
        $this->assertStringContainsString('Database connection failed', $response->content());
        $this->assertStringContainsString('<!DOCTYPE html>', $response->content());
    }

    public function testExceptionHandlerReturnsJsonWhenRequestedInDebugMode(): void
    {
        $_ENV['CRONOS_APP_DEBUG'] = 'true';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $handler = new ExceptionHandler();
        $exception = new Exception("API query failure");

        $response = $handler->handle($exception);

        $this->assertSame(500, $response->statusCode());
        $this->assertSame('application/json', $response->headers('content-type'));
        
        $json = json_decode($response->content(), true);
        $this->assertIsArray($json);
        $this->assertSame('Exception', $json['Type error']);
        $this->assertSame('API query failure', $json['message']);
        $this->assertArrayHasKey('trace', $json);
    }

    public function testExceptionHandlerReturnsGenericInProductionMode(): void
    {
        $_ENV['CRONOS_APP_DEBUG'] = 'false';
        unset($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_X_REQUESTED_WITH']);

        $handler = new ExceptionHandler();
        $exception = new Exception("Sensitive internal secret");

        $response = $handler->handle($exception);

        $this->assertSame(500, $response->statusCode());
        $this->assertStringNotContainsString('Sensitive internal secret', $response->content());
    }
}
