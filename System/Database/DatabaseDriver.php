<?php

namespace Cronos\Database;

interface DatabaseDriver
{
    public function connect(string $protocol, string $host, int $port, string $database, string $username, string $password): void;

    public function lastInsertId(): string|false|int;

    public function close(): void;

    public function statement(string $query, array $bind = []): mixed;

    public function statementC_U_D(string $query, array $bind = []): mixed;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;
}
