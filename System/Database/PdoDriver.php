<?php

namespace Cronos\Database;

use PDO;
use PDOException;
use Cronos\Database\DatabaseDriver;

class PdoDriver implements DatabaseDriver
{
    protected ?PDO $pdo;

    public function connect(string $protocol, string $host, int $port, string $database, string $username, string $password): void
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
            //Lanza una excepcion manejable por el ExceptionHandler del framework
            //(antes hacia echo + exit, lo que corrompia respuestas JSON)
            throw new \RuntimeException(
                'No se pudo conectar a la base de datos: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function lastInsertId(): string|false|int
    {
        return $this->pdo->lastInsertId();
    }

    public function close(): void
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

        return $statement->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
