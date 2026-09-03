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

    ////////////////////////////////////////////////////////////////////
    // alpha_dash
    ////////////////////////////////////////////////////////////////////

    public function testAlphaDashValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['username' => 'mi_post-1'],
            ['username' => 'required|alpha_dash']
        );

        $this->assertTrue($result);
    }

    public function testAlphaDashValidationFails(): void
    {
        $result = $this->validation->validate(
            ['username' => 'mi post'],
            ['username' => 'required|alpha_dash']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('username', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // alpha_space
    ////////////////////////////////////////////////////////////////////

    public function testAlphaSpaceValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['name' => 'Juan Perez'],
            ['name' => 'required|alpha_space']
        );

        $this->assertTrue($result);
    }

    public function testAlphaSpaceValidationFails(): void
    {
        $result = $this->validation->validate(
            ['name' => 'Juan1'],
            ['name' => 'required|alpha_space']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // alpha_numeric_space
    ////////////////////////////////////////////////////////////////////

    public function testAlphaNumericSpaceValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['address' => 'Casa 25 azul'],
            ['address' => 'required|alpha_numeric_space']
        );

        $this->assertTrue($result);
    }

    public function testAlphaNumericSpaceValidationFails(): void
    {
        $result = $this->validation->validate(
            ['address' => 'Casa #25'],
            ['address' => 'required|alpha_numeric_space']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // numeric
    ////////////////////////////////////////////////////////////////////

    public function testNumericValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['price' => '10.5'],
            ['price' => 'required|numeric']
        );

        $this->assertTrue($result);
    }

    public function testNumericValidationFails(): void
    {
        $result = $this->validation->validate(
            ['price' => 'abc'],
            ['price' => 'required|numeric']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('price', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // decimal
    ////////////////////////////////////////////////////////////////////

    public function testDecimalValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['price' => '99.99'],
            ['price' => 'required|decimal']
        );

        $this->assertTrue($result);
    }

    public function testDecimalValidationFails(): void
    {
        $result = $this->validation->validate(
            ['price' => '10,5'],
            ['price' => 'required|decimal']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('price', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // integer
    ////////////////////////////////////////////////////////////////////

    public function testIntegerValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['quantity' => '-5'],
            ['quantity' => 'required|integer']
        );

        $this->assertTrue($result);
    }

    public function testIntegerValidationFails(): void
    {
        $result = $this->validation->validate(
            ['quantity' => '5.5'],
            ['quantity' => 'required|integer']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('quantity', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // is_natural
    ////////////////////////////////////////////////////////////////////

    public function testIsNaturalValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['page' => '7'],
            ['page' => 'required|is_natural']
        );

        $this->assertTrue($result);
    }

    public function testIsNaturalValidationFails(): void
    {
        $result = $this->validation->validate(
            ['page' => '-1'],
            ['page' => 'required|is_natural']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('page', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // is_natural_no_zero
    ////////////////////////////////////////////////////////////////////

    public function testIsNaturalNoZeroValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['id' => '7'],
            ['id' => 'required|is_natural_no_zero']
        );

        $this->assertTrue($result);
    }

    public function testIsNaturalNoZeroValidationFails(): void
    {
        $result = $this->validation->validate(
            ['id' => '0'],
            ['id' => 'required|is_natural_no_zero']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // string
    ////////////////////////////////////////////////////////////////////

    public function testStringValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['title' => 'un titulo'],
            ['title' => 'required|string']
        );

        $this->assertTrue($result);
    }

    public function testStringValidationFails(): void
    {
        $result = $this->validation->validate(
            ['title' => 123],
            ['title' => 'required|string']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // text
    ////////////////////////////////////////////////////////////////////

    public function testTextValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['body' => 'texto con espacio'],
            ['body' => 'required|text']
        );

        $this->assertTrue($result);
    }

    public function testTextValidationFails(): void
    {
        $result = $this->validation->validate(
            ['body' => 'texto'],
            ['body' => 'required|text']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // datetime
    ////////////////////////////////////////////////////////////////////

    public function testDatetimeValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['published' => '2026-01-15 10:30:00'],
            ['published' => 'required|datetime']
        );

        $this->assertTrue($result);
    }

    public function testDatetimeValidationFails(): void
    {
        $result = $this->validation->validate(
            ['published' => '15/01/2026'],
            ['published' => 'required|datetime']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('published', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // time
    ////////////////////////////////////////////////////////////////////

    public function testTimeValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['hour' => '10:30:00'],
            ['hour' => 'required|time']
        );

        $this->assertTrue($result);
    }

    public function testTimeValidationFails(): void
    {
        $result = $this->validation->validate(
            ['hour' => '25:99:99'],
            ['hour' => 'required|time']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hour', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // between
    ////////////////////////////////////////////////////////////////////

    public function testBetweenValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['code' => 'abc'],
            ['code' => 'required|between:2,4']
        );

        $this->assertTrue($result);
    }

    public function testBetweenValidationFails(): void
    {
        $result = $this->validation->validate(
            ['code' => 'a'],
            ['code' => 'required|between:2,4']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // matches
    ////////////////////////////////////////////////////////////////////

    public function testMatchesValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['password' => 'secreto123', 'password_confirm' => 'secreto123'],
            ['password' => 'required|matches:password_confirm']
        );

        $this->assertTrue($result);
    }

    public function testMatchesValidationFails(): void
    {
        $result = $this->validation->validate(
            ['password' => 'secreto123', 'password_confirm' => 'otra-cosa'],
            ['password' => 'required|matches:password_confirm']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('password', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // slug
    ////////////////////////////////////////////////////////////////////

    public function testSlugValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['slug' => 'mi-post-1'],
            ['slug' => 'required|slug']
        );

        $this->assertTrue($result);
    }

    public function testSlugValidationFails(): void
    {
        $result = $this->validation->validate(
            ['slug' => 'Mi Post'],
            ['slug' => 'required|slug']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('slug', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // choice
    ////////////////////////////////////////////////////////////////////

    public function testChoiceValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['role' => 'admin'],
            ['role' => 'required|choice:admin,editor']
        );

        $this->assertTrue($result);
    }

    public function testChoiceValidationFails(): void
    {
        $result = $this->validation->validate(
            ['role' => 'otro'],
            ['role' => 'required|choice:admin,editor']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('role', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // required_file
    ////////////////////////////////////////////////////////////////////

    public function testRequiredFileValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['avatar' => ['name' => 'foto.png', 'size' => 1000, 'type' => 'image/png']],
            ['avatar' => 'required_file']
        );

        $this->assertTrue($result);
    }

    public function testRequiredFileValidationFails(): void
    {
        $result = $this->validation->validate(
            ['avatar' => ['name' => '', 'size' => 0, 'type' => '']],
            ['avatar' => 'required_file']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('avatar', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // max_size (parametro en MB)
    ////////////////////////////////////////////////////////////////////

    public function testMaxSizeValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['avatar' => ['name' => 'foto.png', 'size' => 1048576, 'type' => 'image/png']],
            ['avatar' => 'max_size:2']
        );

        $this->assertTrue($result);
    }

    public function testMaxSizeValidationFails(): void
    {
        $result = $this->validation->validate(
            ['avatar' => ['name' => 'foto.png', 'size' => 3145728, 'type' => 'image/png']],
            ['avatar' => 'max_size:2']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('avatar', $result);
    }

    ////////////////////////////////////////////////////////////////////
    // type
    ////////////////////////////////////////////////////////////////////

    public function testTypeValidationPasses(): void
    {
        $result = $this->validation->validate(
            ['avatar' => ['name' => 'foto.png', 'size' => 1000, 'type' => 'image/png']],
            ['avatar' => 'type:png']
        );

        $this->assertTrue($result);
    }

    public function testTypeValidationFails(): void
    {
        $result = $this->validation->validate(
            ['avatar' => ['name' => 'foto.jpg', 'size' => 1000, 'type' => 'image/jpg']],
            ['avatar' => 'type:png']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('avatar', $result);
    }
}
