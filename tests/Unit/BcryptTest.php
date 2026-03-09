<?php

namespace Tests\Unit;

use Cronos\Crypto\Bcrypt;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para el componente Bcrypt.
 * 
 * Estos tests son independientes y no requieren
 * inicialización del framework completo.
 */
class BcryptTest extends TestCase
{
    private Bcrypt $bcrypt;

    protected function setUp(): void
    {
        $this->bcrypt = new Bcrypt();
    }

    /**
     * Test que verifica que hash genera un hash válido.
     */
    public function testHashGeneratesValidHash(): void
    {
        $password = 'secret123';
        $hash = $this->bcrypt->hash($password);

        $this->assertIsString($hash);
        $this->assertGreaterThanOrEqual(60, strlen($hash));
    }

    /**
     * Test que verifica que hash genera hashes diferentes para el mismo password.
     */
    public function testHashGeneratesDifferentHashesForSamePassword(): void
    {
        $password = 'secret123';
        $hash1 = $this->bcrypt->hash($password);
        $hash2 = $this->bcrypt->hash($password);

        $this->assertNotSame($hash1, $hash2);
    }

    /**
     * Test que verifica que verify valida el password correcto.
     */
    public function testVerifyValidatesCorrectPassword(): void
    {
        $password = 'secret123';
        $hash = $this->bcrypt->hash($password);

        $this->assertTrue($this->bcrypt->verify($password, $hash));
    }

    /**
     * Test que verifica que verify rechaza el password incorrecto.
     */
    public function testVerifyRejectsIncorrectPassword(): void
    {
        $password = 'secret123';
        $hash = $this->bcrypt->hash($password);
        $wrongPassword = 'wrong123';

        $this->assertFalse($this->bcrypt->verify($wrongPassword, $hash));
    }

    /**
     * Test que verifica que verify rechaza un hash inválido.
     */
    public function testVerifyRejectsInvalidHash(): void
    {
        $password = 'secret123';
        $invalidHash = 'invalid_hash';

        $this->assertFalse($this->bcrypt->verify($password, $invalidHash));
    }

    /**
     * Test que verifica que hash maneja password vacío.
     */
    public function testHashHandlesEmptyPassword(): void
    {
        $password = '';
        $hash = $this->bcrypt->hash($password);

        $this->assertIsString($hash);
        $this->assertGreaterThanOrEqual(60, strlen($hash));
    }

    /**
     * Test que verifica que hash maneja caracteres especiales.
     */
    public function testHashHandlesSpecialCharacters(): void
    {
        $password = 'p@$$w0rd!#$%&*()';
        $hash = $this->bcrypt->hash($password);

        $this->assertIsString($hash);
        $this->assertTrue($this->bcrypt->verify($password, $hash));
    }

    /**
     * Test que verifica que hash maneja passwords largos.
     */
    public function testHashHandlesLongPassword(): void
    {
        $password = str_repeat('a', 1000);
        $hash = $this->bcrypt->hash($password);

        $this->assertIsString($hash);
        $this->assertTrue($this->bcrypt->verify($password, $hash));
    }

    /**
     * Test que verifica que verify es case sensitive.
     */
    public function testVerifyIsCaseSensitive(): void
    {
        $password = 'Secret123';
        $hash = $this->bcrypt->hash($password);
        $wrongCase = 'secret123';

        $this->assertTrue($this->bcrypt->verify($password, $hash));
        $this->assertFalse($this->bcrypt->verify($wrongCase, $hash));
    }
}
