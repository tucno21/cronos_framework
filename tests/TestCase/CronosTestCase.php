<?php

namespace Tests\TestCase;

use PHPUnit\Framework\TestCase;
use Cronos\Session\PhpNativeSessionStorage;
use Cronos\Session\Session;

/**
 * TestCase base para tests del Cronos Framework.
 * 
 * Proporciona la configuración inicial necesaria para los tests
 * que dependen de componentes del framework.
 */
abstract class CronosTestCase extends TestCase
{
    protected ?Session $session = null;

    /**
     * Configuración inicial común para todos los tests.
     * 
     * Esta configuración:
     * - Define constantes necesarias del framework
     * - Inicializa $_SESSION con los arrays requeridos
     * - Prepara el entorno para testing
     * - Mockea el helper session() para evitar dependencias de App
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Definir constantes necesarias si no existen
        if (!defined('RESULT_TYPE')) {
            define('RESULT_TYPE', 'array');
        }

        // Inicializar $_SESSION con arrays necesarios para Session
        $_SESSION = [
            '_flash' => ['old' => [], 'new' => []],
            '_cronos_previous_path' => ['old' => '', 'new' => ''],
            '_errors_inputs' => ['dataInput' => [], 'errors' => []],
        ];

        // Inicializar sesión para tests
        $storage = new PhpNativeSessionStorage();
        $storage->start();
        $this->session = new Session($storage);

        // Crear helpers globales para testing
        $GLOBALS['test_session'] = $this->session;

        // Crear mock de App con sesión
        $mockApp = new class($this->session) {
            public $session;

            public function __construct($session)
            {
                $this->session = $session;
            }
        };
        $GLOBALS['test_app'] = $mockApp;

        // Cargar helpers de testing si no existen
        if (!function_exists('session')) {
            require_once __DIR__ . '/../Helpers/testing_helpers.php';
        }
    }

    /**
     * Limpieza después de cada test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Limpiar variables globales
        unset($GLOBALS['test_session']);
        unset($GLOBALS['test_app']);

        // Limpiar $_SESSION
        $_SESSION = [];
        session_unset();

        // Destruir sesión solo si está activa
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
