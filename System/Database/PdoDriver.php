<?php

namespace Cronos\Database;

use PDO;
use PDOException;
use Cronos\Database\DatabaseDriver;

class PdoDriver implements DatabaseDriver
{
    protected ?PDO $pdo;

    public function connect(string $protocol, string $host, int $port, string $database, string $username, string $password)
    {
        try {
            $dsn = "$protocol:host=$host;port=$port;dbname=$database;charset=utf8mb4"; // Incluye el charset en el DSN

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Configuración predeterminada para fetch
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'" // Establece la codificación de caracteres
            ];

            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            echo 'Message: ' . $e->getMessage();
            echo '<br>Code: ' . $e->getCode();
            echo '<br>File: ' . $e->getFile();
            echo '<br>Line: ' . $e->getLine();
            echo '<br>Trace: ' . $e->getTraceAsString();
            exit;
        }
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function close()
    {
        $this->pdo = null;
    }

    public function statement(string $query, array $bind = []): mixed
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bind);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statementC_U_D(string $query, array $bind = []): mixed
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bind);
        $statement->fetchAll(PDO::FETCH_OBJ);
        $cant = $statement->rowCount();

        return $cant;
    }
}
