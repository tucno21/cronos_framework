<?php

namespace Tests\TestCase;

use App\Models\Usuario;
use Cronos\Database\PdoDriver;
use Cronos\Model\Model;
use PDO;

/**
 * TestCase base para tests de integracion del ORM.
 *
 * Conecta con la BD `cronos` real (skip si MySQL no esta disponible)
 * y ofrece helpers para que los tests NO dejen datos residuales:
 * - rollbackAfter(): ejecuta un callback dentro de una transaccion que
 *   SIEMPRE hace rollback al final.
 */
abstract class OrmTestCase extends CronosTestCase
{
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
    }

    /**
     * Ejecuta el callback dentro de una transaccion y hace rollback SIEMPRE,
     * aunque el callback tenga asserts exitosos. Asi el test verifica
     * escrituras reales sin dejar datos en la BD.
     */
    protected function rollbackAfter(callable $callback): void
    {
        try {
            Model::transaction(function () use ($callback): void {
                $callback();

                throw new \RuntimeException('rollback-de-test');
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'rollback-de-test') {
                throw $e;
            }
        }
    }

    /**
     * Crea un usuario de prueba con correo unico.
     * Cumple todos los campos $fillable obligatorios del modelo.
     */
    protected function crearUsuario(array $overrides = []): Usuario
    {
        $data = array_merge([
            'nombre' => 'Usuario ORM Test',
            'correo' => 'orm-' . uniqid() . '@test.com',
            'contrasena' => password_hash('secreto123', PASSWORD_BCRYPT),
            'rol' => 'usuario',
            'avatar' => null,
            'correo_verificado_en' => null,
            'token_recordar' => null,
        ], $overrides);

        return Usuario::create($data);
    }
}
