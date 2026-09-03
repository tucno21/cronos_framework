<?php

namespace Tests\Integration;

use App\Models\Usuario;
use Cronos\Database\PdoDriver;
use Cronos\Model\Model;
use Cronos\Validation\Validation;
use PDO;
use Tests\TestCase\CronosTestCase;

/**
 * Tests de integracion para las reglas de validacion que consultan
 * la base de datos: unique, not_unique y password_verify.
 *
 * Usan la tabla usuarios de la BD `cronos` (seed: admin@admin.com,
 * e2e@test.com con contrasena 'secreto123').
 * Si MySQL no esta disponible, los tests se marcan como skipped.
 */
class ValidationDbRulesTest extends CronosTestCase
{
    private Validation $validation;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $probe = new PDO(
                'mysql:host=127.0.0.1;port=3306;dbname=cronos',
                'root',
                'root',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]
            );
            unset($probe);
        } catch (\PDOException $e) {
            $this->markTestSkipped('MySQL no disponible: ' . $e->getMessage());
        }

        $driver = new PdoDriver();
        $driver->connect('mysql', '127.0.0.1', 3306, 'cronos', 'root', 'root');
        Model::setDB($driver);

        $this->validation = new Validation();
    }

    public function testUniqueValidationPassesConCorreoLibre(): void
    {
        $result = $this->validation->validate(
            ['correo' => 'libre-para-test@test.com'],
            ['correo' => 'required|email|unique:Usuario,correo']
        );

        $this->assertTrue($result);
    }

    public function testUniqueValidationFailsConCorreoExistente(): void
    {
        $result = $this->validation->validate(
            ['correo' => 'admin@admin.com'],
            ['correo' => 'required|email|unique:Usuario,correo']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('correo', $result);
    }

    public function testNotUniqueValidationPassesConCorreoExistente(): void
    {
        $result = $this->validation->validate(
            ['correo' => 'admin@admin.com'],
            ['correo' => 'required|email|not_unique:Usuario,correo']
        );

        $this->assertTrue($result);
    }

    public function testNotUniqueValidationFailsConCorreoLibre(): void
    {
        $result = $this->validation->validate(
            ['correo' => 'libre-para-test@test.com'],
            ['correo' => 'required|email|not_unique:Usuario,correo']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('correo', $result);
    }

    public function testPasswordVerifyValidationPassesConContrasenaCorrecta(): void
    {
        $result = $this->validation->validate(
            ['correo' => 'e2e@test.com', 'contrasena' => 'secreto123'],
            ['contrasena' => 'required|password_verify:Usuario,correo']
        );

        $this->assertTrue($result);
    }

    public function testPasswordVerifyValidationFailsConContrasenaIncorrecta(): void
    {
        $result = $this->validation->validate(
            ['correo' => 'e2e@test.com', 'contrasena' => 'contrasena-incorrecta'],
            ['contrasena' => 'required|password_verify:Usuario,correo']
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contrasena', $result);
    }

    public function testPasswordVerifyConUsuarioInexistenteNoFalla(): void
    {
        //comportamiento documentado: si getByColumn no encuentra registro,
        //la regla no agrega error (la existencia se valida con not_unique)
        $result = $this->validation->validate(
            ['correo' => 'no-existe@test.com', 'contrasena' => 'loquesea'],
            ['contrasena' => 'required|password_verify:Usuario,correo']
        );

        $this->assertTrue($result);
    }

    public function testSetDBRegistraElDriver(): void
    {
        //verifica que las consultas del modelo funcionan con el driver seteado
        $usuario = Usuario::where('correo', 'admin@admin.com')->first();

        $this->assertNotNull($usuario);
        $this->assertSame('Admin', $usuario->nombre);
    }
}
