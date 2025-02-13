<?php

namespace Cronos\Database;

class DatabaseMigrate
{
    protected $connection;
    protected $host;
    protected $port;
    protected $database;
    protected $username;
    protected $password;
    protected $pdo;

    public function __construct()
    {
        // Cargar variables de entorno
        $envFile = dirname(__DIR__, 2) . '/.env';
        $this->loadEnv($envFile);

        $this->connection = $this->env('DB_CONNECTION', 'mysql');
        $this->host = $this->env('DB_HOST', 'localhost');
        $this->port = $this->env('DB_PORT', 3306);
        $this->database = $this->env('DB_DATABASE', 'cronos');
        $this->username = $this->env('DB_USERNAME', 'root');
        $this->password = $this->env('DB_PASSWORD', '');

        // Debug information
        echo "\nConfiguración de conexión:";
        echo "\nHost: " . $this->host;
        echo "\nPort: " . $this->port;
        echo "\nUsername: " . $this->username;
        echo "\nDatabase: " . $this->database;
        echo "\nPassword is " . ($this->password ? "set" : "not set") . "\n";
    }

    /**
     * Carga las variables de entorno desde el archivo .env
     */
    private function loadEnv($path)
    {
        if (!file_exists($path)) {
            echo "\nArchivo .env no encontrado en: $path\n";
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Remover comillas si existen
                $value = trim($value, '"');
                $value = trim($value, "'");

                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }

    /**
     * Obtiene una variable de entorno con valor por defecto
     */
    private function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            echo "\nVariable de entorno no encontrada: $key, usando valor por defecto: $default\n";
            return $default;
        }
        return $value;
    }

    /**
     * Establece la conexión a la base de datos
     */
    public function connect()
    {
        try {
            $dsn = "{$this->connection}:host={$this->host};port={$this->port}";
            echo "\nIntentando conectar al servidor MySQL...\n";

            try {
                $this->pdo = new \PDO(
                    $dsn,
                    $this->username,
                    $this->password,
                    array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
                );
            } catch (\PDOException $e) {
                if (empty($this->password)) {
                    echo "\nReintentando conexión sin contraseña...\n";
                    $this->pdo = new \PDO(
                        $dsn,
                        $this->username,
                        null,
                        array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
                    );
                } else {
                    throw $e;
                }
            }

            echo "\nConexión exitosa al servidor MySQL.\n";

            // Check if database exists
            $stmt = $this->pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->database}'");

            if (!$stmt->fetch()) {
                echo "\nCreando base de datos '{$this->database}'...\n";
                $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->database}`");
                echo "Base de datos creada exitosamente.\n";
            } else {
                echo "\nLa base de datos '{$this->database}' ya existe.\n";
            }

            // Connect to the specific database
            echo "\nConectando a la base de datos '{$this->database}'...\n";
            $this->pdo = new \PDO(
                "{$this->connection}:host={$this->host};port={$this->port};dbname={$this->database}",
                $this->username,
                $this->password
            );
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            echo "Conexión exitosa a la base de datos.\n";

            return true;
        } catch (\PDOException $e) {
            echo "\nError de conexión: " . $e->getMessage() . "\n";
            echo "\nPosibles soluciones:";
            echo "\n1. Verifica tu usuario y contraseña de MySQL en el archivo .env";
            echo "\n2. Asegúrate que el servidor MySQL esté corriendo";
            echo "\n3. Verifica que el usuario '{$this->username}' tenga los permisos correctos";
            echo "\n4. Configura la contraseña en el archivo .env si usas autenticación con contraseña";
            echo "\n5. Verifica que MySQL esté corriendo en el puerto {$this->port}\n";
            return false;
        }
    }

    /**
     * Obtiene la conexión PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }
}
