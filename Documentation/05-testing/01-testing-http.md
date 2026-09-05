# Testing HTTP Funcional en Memoria

> **AVISO CRÍTICO PARA DESARROLLADORES E INTELIGENCIAS ARTIFICIALES (IAs)**
>
> Cronos **NO es Laravel**. Aunque la API de testing comparte nombres conocidos (`MakesHttpRequests`, `TestResponse`), **Cronos ejecuta su propio ciclo de vida HTTP (`Cronos\Http\Request`, `Cronos\Routing\Router`, `Cronos\Http\Pipeline`, `Cronos\Container\Container` y `Cronos\Http\Response`)**.
>
> **Reglas fundamentales para IAs y desarrolladores:**
> - **NUNCA levantes servidores web externos** (`php -S`, Apache, Nginx) ni intentes lanzar comandos `curl` para probar rutas, middlewares o controladores.
> - **NUNCA uses namespaces de Laravel** (`Illuminate\Foundation\Testing\*`). Usa exclusivamente `Tests\TestCase\OrmTestCase` o `Tests\TestCase\CronosTestCase`.
> - Las peticiones se ejecutan **100% en memoria** mediante `$this->get()`, `$this->post()`, `$this->getJson()`, `$this->postJson()`, etc.
> - Para tests que modifiquen la base de datos (POST/PUT/DELETE), usa **SIEMPRE** `$this->rollbackAfter(function () { ... });` para mantener la BD limpia sin residuos.

---

## 1. Concepto y Arquitectura

El testing funcional HTTP en Cronos utiliza dos componentes clave ubicados en `System/Testing/`:

1. **`MakesHttpRequests` (Trait)**: Proporciona métodos para simular verbos HTTP (`get`, `post`, `put`, `patch`, `delete`, `getJson`, `postJson`, etc.). Construye instancias sintéticas de `Cronos\Http\Request` vía `Request::create()` y las despacha a través de `$router->resolve($request)`.
2. **`TestResponse` (Wrapper)**: Envuelve la instancia de `Cronos\Http\Response` resultante y provee aserciones fluidas orientadas a APIs y respuestas web.

```
[ PHPUnit Test ]
      │
      ▼  $this->getJson('/api/blogs')
[ MakesHttpRequests ]
      │  Request::create(...)
      ▼
[ Container::instance(Request::class, $request) ]
      │
      ▼
[ Router::resolve($request) ] ──► [ Middlewares (Pipeline) ] ──► [ FormRequest / Controller ]
      │
      ▼ (Response)
[ TestResponse ] ──► ->assertOk()->assertJsonPath('status', 'success')
```

---

## 2. Métodos de Petición Disponibles

El trait `MakesHttpRequests` ya está integrado en `CronosTestCase` (y por herencia en `OrmTestCase`), por lo que está disponible automáticamente en cualquier test:

### Verbos HTTP Estándar
```php
$response = $this->get('/ruta', $headers);
$response = $this->post('/ruta', $data, $headers);
$response = $this->put('/ruta', $data, $headers);
$response = $this->patch('/ruta', $data, $headers);
$response = $this->delete('/ruta', $data, $headers);
```

### Peticiones JSON (Recomendado para APIs)
Envían automáticamente `Content-Type: application/json` y `Accept: application/json`, serializando los datos como payload JSON en el body de la petición:
```php
$response = $this->getJson('/api/recurso', $headers);
$response = $this->postJson('/api/recurso', ['campo' => 'valor'], $headers);
$response = $this->putJson('/api/recurso/1', ['campo' => 'nuevo_valor'], $headers);
$response = $this->patchJson('/api/recurso/1', ['campo' => 'parcial'], $headers);
$response = $this->deleteJson('/api/recurso/1', [], $headers);
```

### Cabeceras y Tokens
```php
// Cabecera única para la siguiente petición
$this->withHeader('X-Custom-Header', 'valor');

// Múltiples cabeceras
$this->withHeaders([
    'X-Api-Key' => 'secret123',
    'X-Client' => 'mobile'
]);

// Token Bearer para autenticación
$this->withToken('jwt-token-string');
```

---

## 3. Catálogo de Aserciones de `TestResponse`

`TestResponse` permite encadenar aserciones fluidas sobre el estado, cabeceras, contenido HTML/texto y estructura/datos JSON.

### Códigos de Estado HTTP
- `assertStatus(int $expectedStatus)`: Comprueba el código de estado exacto (e.g. 200, 201, 400, 404, 422).
- `assertOk()`: Comprueba que el código de estado sea `200`.
- `assertCreated()`: Comprueba que el código de estado sea `201`.
- `assertNoContent()`: Comprueba que el código de estado sea `204`.
- `assertBadRequest()`: Comprueba que el código de estado sea `400`.
- `assertUnauthorized()`: Comprueba que el código de estado sea `401`.
- `assertForbidden()`: Comprueba que el código de estado sea `403`.
- `assertNotFound()`: Comprueba que el código de estado sea `404`.
- `assertUnprocessable()`: Comprueba que el código de estado sea `422`.

### Cabeceras
- `assertHeader(string $name, ?string $value = null)`: Comprueba la existencia (y opcionalmente el valor) de una cabecera.
- `assertHeaderJson()`: Comprueba que el `Content-Type` contenga `application/json`.

### Aserciones JSON (Especial para APIs)
- `assertJson(array $expected, bool $exact = false)`: Verifica que la respuesta JSON contenga el fragmento dado o coincida exactamente.
- `assertJsonPath(string $path, mixed $expected)`: Verifica un valor exacto usando notación por puntos (ej: `'status'`, `'publicacion.titulo'`).
- `assertJsonStructure(array $structure)`: Valida la estructura y anidamiento de claves en el objeto JSON.
- `assertJsonValidationErrors(array|string $keys)`: Comprueba que existan mensajes de error de validación bajo `errors` para los campos indicados.

### Redirecciones y Contenido
- `assertRedirect(?string $uri = null)`: Verifica que la respuesta sea un código 301, 302, 307 o 308, y opcionalmente la URL de destino.
- `assertSee(string $value)`: Verifica que el contenido de la respuesta contenga la cadena de texto indicada.
- `assertDontSee(string $value)`: Verifica que el contenido NO contenga la cadena de texto indicada.

---

## 4. Ejemplos Completos de Tests Funcionales

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase\OrmTestCase;

class PublicacionHttpTest extends OrmTestCase
{
    public function test_creacion_publicacion_exitosa_con_token(): void
    {
        $this->rollbackAfter(function () {
            // Simular petición protegida con token
            $response = $this->postJson('/api/blogs', [
                'titulo' => 'Mi Nuevo Post en Cronos',
                'contenido' => 'Contenido redactado para el test funcional.',
            ], [
                'X-Token' => 'token-de-prueba-valido',
            ]);

            $response->assertCreated()
                     ->assertHeaderJson()
                     ->assertJsonPath('status', 'success')
                     ->assertJsonStructure([
                         'status',
                         'publicacion' => ['id', 'titulo', 'slug']
                     ]);
        });
    }

    public function test_validacion_falla_con_datos_incompletos(): void
    {
        $response = $this->postJson('/api/register', [
            'nombre' => 'A', // Demasiado corto (min:3)
            'correo' => 'no-es-correo',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre', 'correo']);
    }
}
```

---

## 5. Guía de Buenas Prácticas para Desarrolladores e IAs

1. **Heredar de la clase base correcta**:
   - Para tests que interactúan con base de datos o el ORM: extender de `Tests\TestCase\OrmTestCase`.
   - Para tests que solo prueban lógica sin base de datos: extender de `Tests\TestCase\CronosTestCase`.
2. **Uso de `rollbackAfter`**:
   - Siempre que ejecutes un test funcional que cree, actualice o borre registros en la base de datos real, envuélvelo en `$this->rollbackAfter(function () { ... });` para asegurar que ningún dato residual persista entre ejecuciones.
3. **Inyección de Dependencias y FormRequests**:
   - En cada llamada HTTP (`$this->call(...)`), el framework vincula automáticamente la petición sintética en el contenedor (`Container::instance(Request::class, $request)`), permitiendo que los FormRequests tipados (`store(PublicacionRequest $request)`) se instancien, validen y emitan sus respuestas 422 con total fidelidad.
4. **Flujo de Ejecución de la Suite**:
   - Para correr los tests funcionales de la aplicación:
     ```bash
     vendor/bin/phpunit tests/Integration/HttpFunctionalTest.php
     ```
   - Para correr la suite completa del framework y app:
     ```bash
     vendor/bin/phpunit
     ```
