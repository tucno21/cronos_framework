<?php

namespace Tests\Unit;

use Cronos\Routing\Route;
use Cronos\Routing\Router;
use Cronos\Http\HttpMethod;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para el componente Router.
 * 
 * Estos tests son independientes y no requieren
 * inicialización del framework completo.
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    /**
     * Test que verifica que se puede registrar una ruta GET.
     */
    public function testGetRouteRegistration(): void
    {
        $route = $this->router->get('/test', function () {
            return 'Hello';
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('GET', $this->router->routes);
        $this->assertCount(1, $this->router->routes['GET']);
    }

    /**
     * Test que verifica que se puede registrar una ruta POST.
     */
    public function testPostRouteRegistration(): void
    {
        $route = $this->router->post('/test', function () {
            return 'Created';
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('POST', $this->router->routes);
        $this->assertCount(1, $this->router->routes['POST']);
    }

    /**
     * Test que verifica que se puede registrar una ruta PUT.
     */
    public function testPutRouteRegistration(): void
    {
        $route = $this->router->put('/test', function () {
            return 'Updated';
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('PUT', $this->router->routes);
        $this->assertCount(1, $this->router->routes['PUT']);
    }

    /**
     * Test que verifica que se puede registrar una ruta DELETE.
     */
    public function testDeleteRouteRegistration(): void
    {
        $route = $this->router->delete('/test', function () {
            return 'Deleted';
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('DELETE', $this->router->routes);
        $this->assertCount(1, $this->router->routes['DELETE']);
    }

    /**
     * Test que verifica que se pueden registrar múltiples rutas.
     */
    public function testMultipleRoutesRegistration(): void
    {
        $this->router->get('/route1', fn() => 'Route 1');
        $this->router->get('/route2', fn() => 'Route 2');
        $this->router->post('/route3', fn() => 'Route 3');

        $this->assertCount(2, $this->router->routes['GET']);
        $this->assertCount(1, $this->router->routes['POST']);
    }

    /**
     * Test que verifica el método setPrefix.
     */
    public function testSetPrefix(): void
    {
        $this->router->setPrefix('/api');
        $route = $this->router->get('/test', fn() => 'test');

        $this->assertCount(1, $this->router->routes['GET']);
    }

    /**
     * Test que verifica el método clearPrefix.
     */
    public function testClearPrefix(): void
    {
        $this->router->setPrefix('/api');
        $this->router->clearPrefix();
        $route = $this->router->get('/test', fn() => 'test');

        $this->assertCount(1, $this->router->routes['GET']);
    }

    /**
     * Test que verifica que el router inicializa todos los métodos HTTP.
     */
    public function testRouterInitializesAllHttpMethods(): void
    {
        $methods = array_map(fn($method) => $method->value, HttpMethod::cases());

        foreach ($methods as $method) {
            $this->assertArrayHasKey($method, $this->router->routes);
        }
    }

    /**
     * Test que verifica que se puede registrar una ruta con parámetros.
     */
    public function testRouteWithParameters(): void
    {
        $route = $this->router->get('/users/{id}', function ($id) {
            return "User $id";
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertCount(1, $this->router->routes['GET']);
    }

    /**
     * Test que verifica que diferentes métodos HTTP tienen rutas separadas.
     */
    public function testDifferentHttpMethodsHaveSeparateRoutes(): void
    {
        $this->router->get('/resource', fn() => 'GET');
        $this->router->post('/resource', fn() => 'POST');

        $this->assertCount(1, $this->router->routes['GET']);
        $this->assertCount(1, $this->router->routes['POST']);
    }
}
