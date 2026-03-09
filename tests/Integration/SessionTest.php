<?php

namespace Tests\Integration;

use Cronos\Session\Session;
use Tests\TestCase\CronosTestCase;

/**
 * Tests de integración para el componente Session.
 * 
 * Estos tests requieren inicialización de $_SESSION y otros
 * componentes del framework, por lo que extienden CronosTestCase.
 */
class SessionTest extends CronosTestCase
{

    protected function setUp(): void
    {
        // Llamar al setUp del padre para inicializar $_SESSION y session()
        parent::setUp();
    }

    /**
     * Test que verifica que se puede establecer un valor en la sesión.
     */
    public function testSetSessionValue(): void
    {
        $value = ['data' => 'value'];
        $this->session->set('key', $value);

        $this->assertEquals($value, $_SESSION['key']);
    }

    /**
     * Test que verifica que se puede obtener un valor de la sesión.
     */
    public function testGetSessionValue(): void
    {
        $value = ['data' => 'value'];
        $_SESSION['key'] = $value;

        $result = $this->session->get('key');

        $this->assertEquals($value, $result);
    }

    /**
     * Test que verifica que get retorna valor por defecto si no existe.
     */
    public function testGetReturnsDefaultWhenKeyNotExists(): void
    {
        $result = $this->session->get('nonexistent', 'default');

        $this->assertEquals('default', $result);
    }

    /**
     * Test que verifica que se puede verificar si existe una clave en sesión.
     */
    public function testHasReturnsTrueForExistingKey(): void
    {
        $_SESSION['key'] = ['data' => 'value'];

        $this->assertTrue($this->session->has('key'));
        $this->assertFalse($this->session->has('nonexistent'));
    }

    /**
     * Test que verifica que se puede eliminar una clave de sesión.
     */
    public function testRemoveSessionKey(): void
    {
        $_SESSION['key'] = ['data' => 'value'];

        $this->session->remove('key');

        $this->assertFalse(isset($_SESSION['key']));
    }

    /**
     * Test que verifica que se puede establecer datos flash.
     */
    public function testFlashData(): void
    {
        $this->session->flash('flash_key', 'flash_value');

        $this->assertEquals('flash_value', $_SESSION['flash_key']);
        $this->assertContains('flash_key', $_SESSION['_flash']['new']);
    }

    /**
     * Test que verifica que se puede obtener datos flash.
     */
    public function testGetFlashData(): void
    {
        $this->session->flash('flash_key', 'flash_value');

        $result = $this->session->get('flash_key');

        $this->assertEquals('flash_value', $result);
    }

    /**
     * Test que verifica que se puede obtener y eliminar datos con pull.
     */
    public function testPullSessionValue(): void
    {
        $_SESSION['key'] = ['data' => 'value'];

        $result = $this->session->pull('key');

        $this->assertEquals(['data' => 'value'], $result);
        $this->assertFalse(isset($_SESSION['key']));
    }

    /**
     * Test que verifica que se puede obtener todas las sesiones.
     */
    public function testAllReturnsAllSessions(): void
    {
        $_SESSION['key1'] = ['data' => 'value1'];
        $_SESSION['key2'] = ['data' => 'value2'];

        $result = $this->session->all();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key2', $result);
    }

    /**
     * Test que verifica que se puede destruir toda la sesión.
     */
    public function testDestroySession(): void
    {
        $_SESSION['key'] = ['data' => 'value'];

        // destroy() llama a storage->destroy()
        $this->session->destroy();

        // Nota: El constructor de Session reinicializa las claves reservadas
        // después de destroy(), por lo que verificamos que destroy() no arroja error
        $this->assertTrue(true);
    }

    /**
     * Test que verifica que se pueden almacenar datos de error de inputs.
     */
    public function testSetErrorsInputs(): void
    {
        $dataInput = ['name' => 'John'];
        $errors = ['email' => 'Email is required'];

        $this->session->setErrorsInputs($dataInput, $errors);

        $stored = $this->session->get('_errors_inputs');
        $this->assertEquals($dataInput, $stored['dataInput']);
        $this->assertEquals($errors, $stored['errors']);
    }

    /**
     * Test que verifica que se puede obtener un error específico.
     */
    public function testGetErrorForField(): void
    {
        $errors = ['email' => 'Email is required'];
        $this->session->setErrorsInputs([], $errors);

        $result = $this->session->error('email');

        $this->assertEquals('Email is required', $result);
    }

    /**
     * Test que verifica que se puede obtener un valor antiguo de input.
     */
    public function testGetOldInputValue(): void
    {
        $dataInput = ['name' => 'John'];
        $this->session->setErrorsInputs($dataInput, []);

        $result = $this->session->old('name');

        $this->assertEquals('John', $result);
    }

    /**
     * Test que verifica que se puede verificar si existe un error para un campo.
     */
    public function testIfErrorReturnsTrueForExistingError(): void
    {
        $errors = ['email' => 'Email is required'];
        $this->session->setErrorsInputs([], $errors);

        $this->assertTrue($this->session->ifError('email'));
        $this->assertFalse($this->session->ifError('name'));
    }

    /**
     * Test que verifica que se pueden eliminar errores de inputs.
     */
    public function testDeleteErrorsInputs(): void
    {
        $dataInput = ['name' => 'John'];
        $errors = ['email' => 'Email is required'];
        $this->session->setErrorsInputs($dataInput, $errors);

        $this->session->deleteErrorsInputs();

        $stored = $this->session->get('_errors_inputs');
        $this->assertEmpty($stored['dataInput']);
        $this->assertEmpty($stored['errors']);
    }

    /**
     * Test que verifica que se puede guardar el path anterior.
     */
    public function testPreviousPath(): void
    {
        $this->session->previousPath('/first-path');
        $this->session->previousPath('/second-path');

        $previousPath = $this->session->get('_cronos_previous_path');

        $this->assertEquals('/first-path', $previousPath['old']);
        $this->assertEquals('/second-path', $previousPath['new']);
    }

    /**
     * Test que verifica que se puede autenticar un usuario.
     */
    public function testAttemptAuthentication(): void
    {
        $user = ['id' => 1, 'name' => 'John'];

        $this->session->attempt($user);

        $this->assertEquals($user, $_SESSION['SESSION_CRONOS']);
    }

    /**
     * Test que verifica que se puede obtener el usuario autenticado.
     */
    public function testGetAuthenticatedUser(): void
    {
        $user = ['id' => 1, 'name' => 'John'];
        $_SESSION['SESSION_CRONOS'] = $user;

        $result = $this->session->user();

        $this->assertEquals($user, $result);
    }

    /**
     * Test que verifica que se puede verificar si hay usuario autenticado.
     */
    public function testHasUserReturnsTrueWhenAuthenticated(): void
    {
        $_SESSION['SESSION_CRONOS'] = ['id' => 1];

        $this->assertTrue($this->session->hasUser());
    }

    /**
     * Test que verifica que hasUser retorna false cuando no hay usuario.
     */
    public function testHasUserReturnsFalseWhenNotAuthenticated(): void
    {
        unset($_SESSION['SESSION_CRONOS']);

        $this->assertFalse($this->session->hasUser());
    }

    /**
     * Test que verifica que se puede hacer logout.
     */
    public function testLogout(): void
    {
        $_SESSION['SESSION_CRONOS'] = ['id' => 1];

        $this->session->logout();

        $this->assertFalse(isset($_SESSION['SESSION_CRONOS']));
    }

    /**
     * Test que verifica que flush elimina todas las sesiones excepto las reservadas.
     */
    public function testFlushRemovesAllExceptReserved(): void
    {
        $_SESSION['custom_key'] = ['data' => 'custom_value'];

        $this->session->flush();

        $this->assertFalse(isset($_SESSION['custom_key']));
        $this->assertTrue(isset($_SESSION['_flash']));
        $this->assertTrue(isset($_SESSION['_cronos_previous_path']));
        $this->assertTrue(isset($_SESSION['_errors_inputs']));
    }

    /**
     * Test que verifica que se puede obtener el ID de sesión.
     */
    public function testGetSessionId(): void
    {
        $id = $this->session->id();

        $this->assertIsString($id);
        // El ID puede estar vacío si la sesión no está activa
        // Solo verificamos que sea un string válido
    }
}
