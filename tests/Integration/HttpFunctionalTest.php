<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase\OrmTestCase;

class HttpFunctionalTest extends OrmTestCase
{
    public function test_get_public_blogs_retorna_200_y_estructura_json(): void
    {
        $response = $this->getJson('/api/blogs');

        $response->assertStatus(200)
                 ->assertHeaderJson();

        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function test_get_ruta_inexistente_retorna_404(): void
    {
        $response = $this->get('/ruta-inexistente-12345');

        $response->assertStatus(404)
                 ->assertSee('404');
    }

    public function test_post_ruta_protegida_sin_token_retorna_400(): void
    {
        // AuthApiMiddleware exige header X-Token, respondiendo 400 si falta
        $response = $this->postJson('/api/blogs', [
            'titulo' => 'Mi blog sin autenticar',
            'contenido' => 'Contenido de prueba',
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'No se ha enviado el token',
                 ]);
    }

    public function test_post_ruta_protegida_con_token_invalido_retorna_400(): void
    {
        $response = $this->postJson('/api/blogs', [
            'titulo' => 'Mi blog con token falso',
        ], [
            'X-Token' => 'token.invalido.123',
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Token inválido',
                 ]);
    }

    public function test_post_login_con_datos_invalidos_retorna_error_o_validacion(): void
    {
        $response = $this->postJson('/api/login', [
            'correo' => 'correo-inexistente@noexiste.com',
            'contrasena' => 'incorrecta123',
        ]);

        // Debe retornar una respuesta de error (400 o 401 o 422)
        $this->assertContains($response->getStatusCode(), [400, 401, 422]);
    }

    public function test_aserciones_fluidas_de_test_response(): void
    {
        $response = $this->getJson('/api/blogs');

        $response->assertOk()
                 ->assertHeader('Content-Type');

        $this->assertIsInt($response->getStatusCode());
        $this->assertIsString($response->getContent());
        $this->assertNotNull($response->baseResponse());
    }

    public function test_peticion_con_headers_personalizados(): void
    {
        $this->withHeader('X-Custom-Header', 'CronosTestVal');

        $response = $this->getJson('/api/blogs');
        $response->assertOk();
    }

    public function test_aserciones_json_path_y_structure(): void
    {
        $response = $this->getJson('/api/blogs');

        $response->assertOk()
                 ->assertJsonPath('status', 'success')
                 ->assertJsonStructure([
                     'status',
                     'publicaciones'
                 ]);
    }

    public function test_post_registro_validacion_fallida_retorna_422_con_errores(): void
    {
        $response = $this->postJson('/api/register', [
            'nombre' => 'A', // min:3
            'correo' => 'correo-invalido', // email
            'contrasena' => '123', // min:6
            'confirmar_contrasena' => '456', // matches:contrasena
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre', 'correo', 'contrasena']);
    }
}
