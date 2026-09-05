<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\Container\Container;
use Cronos\Container\DependencyInjection;
use Cronos\Errors\AuthorizationException;
use Cronos\Errors\ValidationException;
use Cronos\Http\FormRequest;
use Cronos\Http\Request;
use Tests\TestCase\CronosTestCase;

class DummyAuthorizedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|min:3',
            'email' => 'required|email',
        ];
    }
}

class DummyUnauthorizedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required',
        ];
    }
}

class FormRequestTest extends CronosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Container::flush();
    }

    public function test_form_request_valida_exitosamente(): void
    {
        $request = new Request();
        $request->titulo = 'Mi Titulo';
        $request->email = 'admin@example.com';
        $request->extra = 'no_en_rules';

        $formRequest = new DummyAuthorizedRequest();
        $formRequest->copyFrom($request);
        $formRequest->validateResolved();

        $this->assertEquals('Mi Titulo', $formRequest->validated('titulo'));
        $this->assertEquals('admin@example.com', $formRequest->validated('email'));
        $this->assertArrayNotHasKey('extra', $formRequest->validated());
    }

    public function test_form_request_lanza_authorization_exception_cuando_no_autorizado(): void
    {
        $this->expectException(AuthorizationException::class);

        $request = new Request();
        $formRequest = new DummyUnauthorizedRequest();
        $formRequest->copyFrom($request);
        $formRequest->validateResolved();
    }

    public function test_form_request_lanza_validation_exception_cuando_falla_regla(): void
    {
        $this->expectException(ValidationException::class);

        $request = new Request();
        $request->titulo = 'ab'; // min 3 requerido
        $request->email = 'no-es-un-email';

        $formRequest = new DummyAuthorizedRequest();
        $formRequest->copyFrom($request);
        $formRequest->validateResolved();
    }

    public function test_dependency_injection_inyecta_y_valida_form_request_automaticamente(): void
    {
        $baseRequest = new Request();
        $baseRequest->titulo = 'Articulo Excelente';
        $baseRequest->email = 'cronos@framework.test';

        Container::singleton(Request::class, fn() => $baseRequest);

        $controller = new class {
            public function store(DummyAuthorizedRequest $request): string
            {
                return 'stored: ' . $request->validated('titulo');
            }
        };

        $params = DependencyInjection::resolveParameters([$controller, 'store']);
        $this->assertCount(1, $params);
        $this->assertInstanceOf(DummyAuthorizedRequest::class, $params[0]);

        $result = $controller->store(...$params);
        $this->assertEquals('stored: Articulo Excelente', $result);
    }

    public function test_dependency_injection_detiene_ejecucion_si_form_request_falla(): void
    {
        $this->expectException(ValidationException::class);

        $baseRequest = new Request();
        $baseRequest->titulo = ''; // falla required

        Container::singleton(Request::class, fn() => $baseRequest);

        $controller = new class {
            public function store(DummyAuthorizedRequest $request): string
            {
                return 'nunca debe llegar aqui';
            }
        };

        DependencyInjection::resolveParameters([$controller, 'store']);
    }
}
