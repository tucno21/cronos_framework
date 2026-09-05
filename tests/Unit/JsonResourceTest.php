<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\Http\JsonResource;
use Cronos\Http\ResourceCollection;
use Cronos\Http\Response;
use Tests\TestCase\CronosTestCase;

class DummyUserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'correo' => $this->correo,
            'secret' => $this->when(false, 'no_debe_aparecer'),
            'rol_visible' => $this->when(true, 'admin'),
        ];
    }
}

class JsonResourceTest extends CronosTestCase
{
    public function test_json_resource_transforma_objeto_individual(): void
    {
        $user = (object) [
            'id' => 1,
            'nombre' => 'Carlos',
            'correo' => 'carlos@example.com',
            'secret' => '123456',
        ];

        $resource = new DummyUserResource($user);
        $resolved = $resource->resolve();

        $this->assertArrayHasKey('data', $resolved);
        $this->assertEquals(1, $resolved['data']['id']);
        $this->assertEquals('Carlos', $resolved['data']['nombre']);
        $this->assertEquals('carlos@example.com', $resolved['data']['correo']);
        $this->assertEquals('admin', $resolved['data']['rol_visible']);
        $this->assertNull($resolved['data']['secret']);
    }

    public function test_json_resource_permite_wrapping_personalizado_o_desactivado(): void
    {
        $user = ['id' => 2, 'nombre' => 'Ana', 'correo' => 'ana@example.com'];

        DummyUserResource::$wrap = null;
        $resource = new DummyUserResource($user);
        $resolved = $resource->resolve();

        $this->assertArrayNotHasKey('data', $resolved);
        $this->assertEquals(2, $resolved['id']);

        // Restaurar wrap por defecto
        DummyUserResource::$wrap = 'data';
    }

    public function test_json_resource_permite_additional_meta(): void
    {
        $user = (object) ['id' => 10, 'nombre' => 'Luis', 'correo' => 'luis@test.com'];

        $resource = (new DummyUserResource($user))->additional([
            'status' => 'success',
            'meta' => ['version' => '1.0'],
        ]);

        $resolved = $resource->resolve();

        $this->assertEquals('success', $resolved['status']);
        $this->assertEquals(['version' => '1.0'], $resolved['meta']);
        $this->assertEquals(10, $resolved['data']['id']);
    }

    public function test_resource_collection_transforma_conjunto_de_modelos(): void
    {
        $users = [
            (object) ['id' => 1, 'nombre' => 'User 1', 'correo' => 'u1@test.com'],
            (object) ['id' => 2, 'nombre' => 'User 2', 'correo' => 'u2@test.com'],
        ];

        $collection = DummyUserResource::collection($users);
        $this->assertInstanceOf(ResourceCollection::class, $collection);
        $this->assertCount(2, $collection);

        $resolved = $collection->resolve();
        $this->assertArrayHasKey('data', $resolved);
        $this->assertCount(2, $resolved['data']);
        $this->assertEquals('User 1', $resolved['data'][0]['nombre']);
        $this->assertEquals('User 2', $resolved['data'][1]['nombre']);
    }

    public function test_to_response_genera_cronos_response_json(): void
    {
        $user = (object) ['id' => 5, 'nombre' => 'Test', 'correo' => 'test@test.com'];
        $resource = new DummyUserResource($user);
        $response = $resource->toResponse(201);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->statusCode());
        $this->assertStringContainsString('"nombre":"Test"', $response->content());
    }
}
