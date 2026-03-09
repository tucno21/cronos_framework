<?php

namespace Tests\Integration;

use Cronos\Validation\Validation;
use Tests\TestCase\CronosTestCase;

/**
 * Tests de integración para el componente Validation.
 * 
 * Estos tests requieren constantes globales y dependencias
 * del framework, por lo que extienden CronosTestCase.
 */
class ValidationTest extends CronosTestCase
{
    private Validation $validation;

    protected function setUp(): void
    {
        // Llamar al setUp del padre para inicializar $_SESSION y constantes
        parent::setUp();

        $this->validation = new Validation();
    }

    /**
     * Test que verifica la validación de campo requerido exitoso.
     */
    public function testRequiredValidationPasses(): void
    {
        $inputs = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de campo requerido fallida.
     */
    public function testRequiredValidationFails(): void
    {
        $inputs = ['name' => ''];
        $rules = ['name' => 'required'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    /**
     * Test que verifica la validación de email exitosa.
     */
    public function testEmailValidationPasses(): void
    {
        $inputs = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de email fallida.
     */
    public function testEmailValidationFails(): void
    {
        $inputs = ['email' => 'invalid-email'];
        $rules = ['email' => 'required|email'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
    }

    /**
     * Test que verifica la validación de longitud mínima exitosa.
     */
    public function testMinLengthValidationPasses(): void
    {
        $inputs = ['name' => 'John'];
        $rules = ['name' => 'required|min:3'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de longitud mínima fallida.
     */
    public function testMinLengthValidationFails(): void
    {
        $inputs = ['name' => 'Jo'];
        $rules = ['name' => 'required|min:3'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    /**
     * Test que verifica la validación de longitud máxima exitosa.
     */
    public function testMaxLengthValidationPasses(): void
    {
        $inputs = ['name' => 'John'];
        $rules = ['name' => 'required|max:15'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de longitud máxima fallida.
     */
    public function testMaxLengthValidationFails(): void
    {
        $inputs = ['name' => 'John Doe Smith'];
        $rules = ['name' => 'required|max:10'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    /**
     * Test que verifica la validación alfabética exitosa.
     */
    public function testAlphaValidationPasses(): void
    {
        $inputs = ['name' => 'John'];
        $rules = ['name' => 'required|alpha'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación alfabética fallida.
     */
    public function testAlphaValidationFails(): void
    {
        $inputs = ['name' => 'John123'];
        $rules = ['name' => 'required|alpha'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    /**
     * Test que verifica la validación alfanumérica exitosa.
     */
    public function testAlphaNumericValidationPasses(): void
    {
        $inputs = ['username' => 'John123'];
        $rules = ['username' => 'required|alpha_numeric'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación alfanumérica fallida.
     */
    public function testAlphaNumericValidationFails(): void
    {
        $inputs = ['username' => 'John_123'];
        $rules = ['username' => 'required|alpha_numeric'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('username', $result);
    }

    /**
     * Test que verifica la validación de URL exitosa.
     */
    public function testUrlValidationPasses(): void
    {
        $inputs = ['website' => 'https://example.com'];
        $rules = ['website' => 'required|url'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de URL fallida.
     */
    public function testUrlValidationFails(): void
    {
        $inputs = ['website' => 'not-a-url'];
        $rules = ['website' => 'required|url'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('website', $result);
    }

    /**
     * Test que verifica múltiples reglas de validación exitosas.
     */
    public function testMultipleValidationRulesPass(): void
    {
        $inputs = ['name' => 'John', 'email' => 'john@example.com'];
        $rules = [
            'name' => 'required|alpha|min:3|max:15',
            'email' => 'required|email'
        ];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica múltiples reglas de validación fallidas.
     */
    public function testMultipleValidationRulesFail(): void
    {
        $inputs = ['name' => 'Jo', 'email' => 'invalid-email'];
        $rules = [
            'name' => 'required|alpha|min:3',
            'email' => 'required|email'
        ];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
    }

    /**
     * Test que verifica la validación de confirmación exitosa.
     */
    public function testConfirmValidationPasses(): void
    {
        $inputs = [
            'password' => 'secret123',
            'password_confirm' => 'secret123'
        ];
        $rules = ['password' => 'required|confirm'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de confirmación fallida.
     */
    public function testConfirmValidationFails(): void
    {
        $inputs = [
            'password' => 'secret123',
            'password_confirm' => 'different'
        ];
        $rules = ['password' => 'required|confirm'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('password', $result);
    }

    /**
     * Test que verifica la validación de fecha exitosa.
     */
    public function testDateValidationPasses(): void
    {
        $inputs = ['birthday' => '2023-01-01'];
        $rules = ['birthday' => 'required|date'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test que verifica la validación de fecha fallida.
     */
    public function testDateValidationFails(): void
    {
        $inputs = ['birthday' => 'not-a-date'];
        $rules = ['birthday' => 'required|date'];

        $result = $this->validation->validate($inputs, $rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('birthday', $result);
    }
}
