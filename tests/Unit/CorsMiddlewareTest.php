<?php

namespace Tests\Unit;

use App\Middlewares\CorsMiddleware;
use Cronos\Config\Config;
use Cronos\Http\HttpMethod;
use Cronos\Http\Request;
use Cronos\Http\Response;
use PHPUnit\Framework\TestCase;

class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CorsMiddleware();

        $ref = new \ReflectionClass(Config::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, [
            'cors' => [
                'allowed_origins' => ['*'],
                'allowed_methods' => ['*'],
                'allowed_headers' => ['*'],
                'supports_credentials' => false,
                'max_age' => 86400,
            ]
        ]);

        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
    }

    public function test_options_request_returns_200_with_cors_headers(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/login';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->statusCode());
        $this->assertEquals('*', $response->headers('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('POST', $response->headers('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('X-Token', $response->headers('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('Content-Type', $response->headers('Access-Control-Allow-Headers'));
        $this->assertEquals('86400', $response->headers('Access-Control-Max-Age'));
    }

    public function test_options_request_includes_all_http_methods(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $methods = $response->headers('Access-Control-Allow-Methods');
        $this->assertStringContainsString('GET', $methods);
        $this->assertStringContainsString('POST', $methods);
        $this->assertStringContainsString('PUT', $methods);
        $this->assertStringContainsString('PATCH', $methods);
        $this->assertStringContainsString('DELETE', $methods);
        $this->assertStringContainsString('OPTIONS', $methods);
    }

    public function test_get_request_adds_cors_headers_to_response(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('*', $response->headers('Access-Control-Allow-Origin'));
        $this->assertNotNull($response->headers('Access-Control-Allow-Methods'));
        $this->assertNotNull($response->headers('Access-Control-Allow-Headers'));
    }

    public function test_post_request_adds_cors_headers_to_response(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/login';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('*', $response->headers('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('X-Token', $response->headers('Access-Control-Allow-Headers'));
    }

    public function test_x_token_is_in_allowed_headers(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertStringContainsString('X-Token', $response->headers('Access-Control-Allow-Headers'));
    }

    public function test_accept_is_in_allowed_headers(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertStringContainsString('Accept', $response->headers('Access-Control-Allow-Headers'));
    }

    public function test_cors_headers_not_wildcard_asterisk(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $methods = $response->headers('Access-Control-Allow-Methods');
        $headers = $response->headers('Access-Control-Allow-Headers');

        $this->assertNotEquals('*', $methods);
        $this->assertNotEquals('*', $headers);
    }

    public function test_http_method_enum_supports_options(): void
    {
        $method = HttpMethod::from('OPTIONS');
        $this->assertEquals('OPTIONS', $method->value);
        $this->assertInstanceOf(HttpMethod::class, $method);
    }

    public function test_preflight_does_not_call_next(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response();
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertFalse($nextCalled);
        $this->assertEquals(200, $response->statusCode());
    }

    public function test_normal_request_calls_next(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $request = new Request();
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response();
        };

        $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
    }

    public function test_specific_origin_allowed(): void
    {
        $ref = new \ReflectionClass(Config::class);
        $prop = $ref->getProperty('config');
        $prop->setValue(null, [
            'cors' => [
                'allowed_origins' => ['http://localhost:3000', 'https://example.com'],
                'allowed_methods' => ['GET', 'POST'],
                'allowed_headers' => ['Content-Type'],
                'max_age' => 3600,
            ]
        ]);

        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('http://localhost:3000', $response->headers('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST', $response->headers('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type', $response->headers('Access-Control-Allow-Headers'));
        $this->assertEquals('3600', $response->headers('Access-Control-Max-Age'));
    }

    public function test_credentials_header_is_false_by_default(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $request = new Request();
        $next = fn($req) => new Response();

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('false', $response->headers('Access-Control-Allow-Credentials'));
    }
}
