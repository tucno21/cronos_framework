<?php

declare(strict_types=1);

namespace Cronos\Testing;

use Cronos\Http\Response;
use PHPUnit\Framework\Assert;

/**
 * Wrapper fluido sobre Cronos\Http\Response para aserciones en tests HTTP funcionales.
 */
class TestResponse
{
    public function __construct(
        protected Response $baseResponse
    ) {
    }

    /**
     * Retorna la instancia subyacente de Response del framework.
     */
    public function baseResponse(): Response
    {
        return $this->baseResponse;
    }

    /**
     * Retorna el código de estado HTTP.
     */
    public function getStatusCode(): int
    {
        return $this->baseResponse->statusCode();
    }

    /**
     * Retorna el contenido del cuerpo de la respuesta como string.
     */
    public function getContent(): string
    {
        return (string) $this->baseResponse->content();
    }

    /**
     * Decodifica el cuerpo JSON en un array o retorna una clave específica (soporta dot notation).
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $decoded = json_decode($this->getContent(), true);

        if (!is_array($decoded)) {
            Assert::fail("El contenido de la respuesta no es un JSON válido: [{$this->getContent()}].");
        }

        if ($key === null) {
            return $decoded;
        }

        return $this->dataGet($decoded, $key, $default);
    }

    /**
     * Retorna los headers de la respuesta o un header específico.
     */
    public function headers(?string $key = null): array|string|null
    {
        return $this->baseResponse->headers($key);
    }

    /**
     * Verifica que el código de estado sea el esperado.
     */
    public function assertStatus(int $expectedStatus): static
    {
        $actual = $this->getStatusCode();
        Assert::assertSame(
            $expectedStatus,
            $actual,
            "Se esperaba el código de estado {$expectedStatus} pero se obtuvo {$actual}. Contenido: " . $this->getContent()
        );
        return $this;
    }

    /**
     * Verifica que el código de estado sea 200 OK.
     */
    public function assertOk(): static
    {
        return $this->assertStatus(200);
    }

    /**
     * Verifica que el código de estado sea 201 Created.
     */
    public function assertCreated(): static
    {
        return $this->assertStatus(201);
    }

    /**
     * Verifica que el código de estado sea 204 No Content.
     */
    public function assertNoContent(int $status = 204): static
    {
        $this->assertStatus($status);
        Assert::assertEmpty($this->getContent(), "Se esperaba contenido vacío para status {$status}.");
        return $this;
    }

    /**
     * Verifica que el código de estado sea 400 Bad Request.
     */
    public function assertBadRequest(): static
    {
        return $this->assertStatus(400);
    }

    /**
     * Verifica que el código de estado sea 401 Unauthorized.
     */
    public function assertUnauthorized(): static
    {
        return $this->assertStatus(401);
    }

    /**
     * Verifica que el código de estado sea 403 Forbidden.
     */
    public function assertForbidden(): static
    {
        return $this->assertStatus(403);
    }

    /**
     * Verifica que el código de estado sea 404 Not Found.
     */
    public function assertNotFound(): static
    {
        return $this->assertStatus(404);
    }

    /**
     * Verifica que el código de estado sea 422 Unprocessable Entity.
     */
    public function assertUnprocessable(): static
    {
        return $this->assertStatus(422);
    }

    /**
     * Verifica que el código de estado sea 500 Internal Server Error.
     */
    public function assertInternalServerError(): static
    {
        return $this->assertStatus(500);
    }

    /**
     * Verifica que la respuesta sea una redirección.
     */
    public function assertRedirect(?string $uri = null): static
    {
        $location = $this->baseResponse->headers('Location');
        Assert::assertNotNull(
            $location,
            "La respuesta no contiene la cabecera 'Location' de redirección."
        );

        if ($uri !== null) {
            Assert::assertEquals($uri, $location, "La redirección esperaba dirigir a [{$uri}] pero fue a [{$location}].");
        }

        return $this;
    }

    /**
     * Verifica que un header exista en la respuesta y opcionalmente compare su valor.
     */
    public function assertHeader(string $headerName, ?string $value = null): static
    {
        $actual = $this->baseResponse->headers($headerName);
        Assert::assertNotNull(
            $actual,
            "La cabecera [{$headerName}] no está presente en la respuesta."
        );

        if ($value !== null) {
            Assert::assertEquals(
                $value,
                $actual,
                "La cabecera [{$headerName}] esperaba el valor [{$value}] pero obtuvo [{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Verifica que la respuesta tenga Content-Type application/json.
     */
    public function assertHeaderJson(): static
    {
        $contentType = (string) $this->baseResponse->headers('Content-Type');
        Assert::assertStringContainsString(
            'application/json',
            $contentType,
            "Se esperaba cabecera Content-Type 'application/json', pero fue '{$contentType}'."
        );
        return $this;
    }

    /**
     * Verifica que el contenido de la respuesta contenga el texto indicado.
     */
    public function assertSee(string $value, bool $escape = false): static
    {
        $needle = $escape ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $value;
        Assert::assertStringContainsString(
            $needle,
            $this->getContent(),
            "No se encontró [{$needle}] en el contenido de la respuesta."
        );
        return $this;
    }

    /**
     * Verifica que el contenido de la respuesta NO contenga el texto indicado.
     */
    public function assertDontSee(string $value, bool $escape = false): static
    {
        $needle = $escape ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $value;
        Assert::assertStringNotContainsString(
            $needle,
            $this->getContent(),
            "Se encontró [{$needle}] en el contenido cuando no debía aparecer."
        );
        return $this;
    }

    /**
     * Verifica que el JSON contenga el subconjunto de datos esperado.
     */
    public function assertJson(array $data, bool $strict = false): static
    {
        $actual = $this->json();

        if ($strict) {
            Assert::assertSame($data, $actual);
        } else {
            $this->assertArraySubset($data, $actual);
        }

        return $this;
    }

    /**
     * Verifica una ruta en notación de punto en el JSON.
     */
    public function assertJsonPath(string $path, mixed $expected): static
    {
        $actual = $this->json($path);
        Assert::assertEquals(
            $expected,
            $actual,
            "En la ruta JSON [{$path}] se esperaba " . json_encode($expected) . " pero se obtuvo " . json_encode($actual)
        );
        return $this;
    }

    /**
     * Verifica que el JSON tenga una estructura de claves dada.
     */
    public function assertJsonStructure(array $structure, ?array $responseData = null): static
    {
        $responseData = $responseData ?? $this->json();

        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if ($key === '*') {
                    Assert::assertIsArray($responseData, "Se esperaba un array para comodín '*'.");
                    foreach ($responseData as $item) {
                        $this->assertJsonStructure($value, $item);
                    }
                    continue;
                }

                Assert::assertArrayHasKey($key, $responseData, "Falta la clave [{$key}] en la estructura JSON.");
                Assert::assertIsArray($responseData[$key], "El valor de [{$key}] debe ser un array.");
                $this->assertJsonStructure($value, $responseData[$key]);
            } else {
                Assert::assertArrayHasKey($value, $responseData, "Falta la propiedad [{$value}] en la estructura JSON.");
            }
        }

        return $this;
    }

    /**
     * Verifica que el JSON contenga errores de validación para las claves indicadas.
     */
    public function assertJsonValidationErrors(array|string $keys): static
    {
        $this->assertStatus(422);

        $keys = is_array($keys) ? $keys : [$keys];
        $errors = $this->json('errors', []);

        foreach ($keys as $key) {
            Assert::assertTrue(
                isset($errors[$key]) && !empty($errors[$key]),
                "No se encontraron errores de validación para el campo [{$key}]. Errores disponibles: " . json_encode($errors)
            );
        }

        return $this;
    }

    /**
     * Verifica que el JSON NO contenga errores de validación para las claves indicadas.
     */
    public function assertJsonMissingValidationErrors(array|string $keys): static
    {
        $keys = is_array($keys) ? $keys : [$keys];
        $errors = $this->json('errors', []);

        foreach ($keys as $key) {
            Assert::assertFalse(
                isset($errors[$key]),
                "Se encontraron errores de validación inesperados para el campo [{$key}]: " . json_encode($errors[$key] ?? null)
            );
        }

        return $this;
    }

    /**
     * Helper recursivo para verificar que $subset esté presente en $array.
     */
    protected function assertArraySubset(array $subset, array $array, string $prefix = ''): void
    {
        foreach ($subset as $key => $expectedValue) {
            $path = $prefix ? "{$prefix}.{$key}" : (string) $key;
            Assert::assertArrayHasKey($key, $array, "Falta la clave [{$path}] en el JSON recibido.");

            if (is_array($expectedValue)) {
                Assert::assertIsArray($array[$key], "El elemento en [{$path}] debe ser un array.");
                $this->assertArraySubset($expectedValue, $array[$key], $path);
            } else {
                Assert::assertEquals(
                    $expectedValue,
                    $array[$key],
                    "El valor en la ruta [{$path}] no coincide. Esperado: " . json_encode($expectedValue) . ", Recibido: " . json_encode($array[$key])
                );
            }
        }
    }

    /**
     * Helper dot-notation getter.
     */
    protected function dataGet(mixed $target, string $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } else {
                return $default;
            }
        }

        return $target;
    }
}
