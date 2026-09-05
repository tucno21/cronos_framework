# Testing HTTP Funcional en Memoria

Cronos Framework cuenta con un subsistema de pruebas HTTP funcionales que permite ejecutar peticiones sintéticas directamente contra el ciclo de vida del framework (**Router**, **Middlewares**, **Pipeline**, **Inyección de Dependencias**, **FormRequests** y **Excepciones**) dentro del entorno de PHPUnit, **sin necesidad de levantar servidores web externos** (php -S / Apache / Nginx) ni recurrir a herramientas lentas como curl.

---

## 1. Concepto y Arquitectura

El testing funcional HTTP en Cronos utiliza dos componentes clave ubicados en System/Testing/:

1. **MakesHttpRequests (Trait)**: Proporciona métodos para simular verbos HTTP (get, post, put, patch, delete, getJson, postJson, etc.). Construye instancias sintéticas de Cronos\Http\Request vía Request::create() y las despacha a través de $router->resolve().
2. **TestResponse (Wrapper)**: Envuelve la instancia de Cronos\Http\Response resultante y provee aserciones fluidas orientadas a APIs y respuestas web.

`
[ PHPUnit Test ]
      │
      ▼  ->getJson('/api/blogs')
[ MakesHttpRequests ]
      │  Request::create(...)
      ▼
[ Container::instance(Request::class, ) ]
      │
      ▼
[ Router::resolve() ] ──► [ Middlewares (Pipeline) ] ──► [ FormRequest / Controller ]
      │
      ▼ (Response)
[ TestResponse ] ──► ->assertOk()->assertJsonPath('status', 'success')
`

---

## 2. Métodos de Petición Disponibles

El trait MakesHttpRequests ya está integrado en CronosTestCase (y por herencia en OrmTestCase), por lo que está disponible en cualquier test de integración:

### Verbos HTTP Estándar
`php
 = ->get('/ruta', );
 = ->post('/ruta', , );
 = ->put('/ruta', , );
 = ->patch('/ruta', , );
 = ->delete('/ruta', , );
`

### Peticiones JSON (Recomendado para APIs)
Envían automáticamente Content-Type: application/json y Accept: application/json, serializando los datos como payload JSON en el body de la petición:
`php
 = ->getJson('/api/recurso', );
 = ->postJson('/api/recurso', ['campo' => 'valor'], );
 = ->putJson('/api/recurso/1', ['campo' => 'nuevo_valor'], );
 = ->patchJson('/api/recurso/1', ['campo' => 'parcial'], );
 = ->deleteJson('/api/recurso/1', [], );
`

### Cabeceras y Tokens
`php
// Cabecera única para la siguiente petición
->withHeader('X-Custom-Header', 'valor');

// Múltiples cabeceras
->withHeaders([
    'X-Api-Key' => 'secret123',
    'X-Client' => 'mobile'
]);

// Token Bearer para autenticación
->withToken('jwt-token-string');
`

---

## 3. Aserciones de TestResponse

TestResponse permite encadenar aserciones fluidas sobre el estado, cabeceras, contenido HTML/texto y estructura/datos JSON.

### Estado HTTP
- ssertStatus(int ): Comprueba el código de estado exacto (e.g. 200, 201, 400, 404, 422).
- ssertOk(): Comprueba que el código de estado sea 200.
- ssertCreated(): Comprueba que el código de estado sea 201.
- ssertNoContent(): Comprueba que el código de estado sea 204.
- ssertBadRequest(): Comprueba que el código de estado sea 400.
- ssertUnauthorized(): Comprueba que el código de estado sea 401.
- ssertForbidden(): Comprueba que el código de estado sea 403.
- ssertNotFound(): Comprueba que el código de estado sea 404.
- ssertUnprocessable(): Comprueba que el código de estado sea 422.

### Cabeceras
- ssertHeader(string , ?string  = null): Comprueba la existencia (y opcionalmente el valor) de una cabecera.
- ssertHeaderJson(): Comprueba que el Content-Type contenga pplication/json.

### Aserciones JSON
- ssertJson(array , bool  = false): Verifica que la respuesta JSON contenga el fragmento o coincida exactamente.
- ssertJsonPath(string , mixed ): Verifica un valor usando notación por puntos (data.usuario.id).
- ssertJsonStructure(array ): Valida la estructura de claves esperada en el objeto JSON.
- ssertJsonValidationErrors(array|string ): Comprueba que existan errores de validación para los campos indicados bajo la clave rrors.

### Redirecciones y Contenido
- ssertRedirect(?string  = null): Verifica que la respuesta sea un código 301, 302, 307 o 308, y opcionalmente la URL de destino.
- ssertSee(string ): Verifica que el contenido contenga la cadena de texto indicada.
- ssertDontSee(string ): Verifica que el contenido NO contenga la cadena de texto indicada.

---

## 4. Ejemplo Completo de Test Funcional

`php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase\OrmTestCase;

class PublicacionHttpTest extends OrmTestCase
{
    public function test_creacion_publicacion_exitosa_con_token(): void
    {
        ->rollbackAfter(function () {
            // Simular petición protegida con token
             = ->postJson('/api/blogs', [
                'titulo' => 'Mi Nuevo Post en Cronos',
                'contenido' => 'Contenido redactado para el test funcional.',
            ], [
                'X-Token' => 'token-de-prueba-valido',
            ]);

            ->assertCreated()
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
         = ->postJson('/api/register', [
            'nombre' => 'A', // Demasiado corto (min:3)
            'correo' => 'no-es-correo',
        ]);

        ->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre', 'correo']);
    }
}
`
