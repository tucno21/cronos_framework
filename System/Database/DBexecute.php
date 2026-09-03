<?php

namespace Cronos\Database;

class DBexecute
{
    public static function statement(string $query, array $bind = []): mixed
    {
        return app(DatabaseDriver::class)->statement($query, $bind);
    }

    public static function statementC_U_D(string $query, array $bind = []): mixed
    {
        return app(DatabaseDriver::class)->statementC_U_D($query, $bind);
    }

    /**
     * Ejecuta el callback dentro de una transaccion de base de datos.
     * Si el callback lanza una excepcion, se hace rollback y se re-lanza.
     *
     * Ejemplo:
     *
     *   DBexecute::transaction(function () {
     *       Usuario::create([...]);
     *       Perfil::create([...]);
     *   });
     */
    public static function transaction(callable $callback): mixed
    {
        $db = app(DatabaseDriver::class);

        $db->beginTransaction();

        try {
            $result = $callback();
            $db->commit();

            return $result;
        } catch (\Throwable $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public static function beginTransaction(): bool
    {
        return app(DatabaseDriver::class)->beginTransaction();
    }

    public static function commit(): bool
    {
        return app(DatabaseDriver::class)->commit();
    }

    public static function rollBack(): bool
    {
        return app(DatabaseDriver::class)->rollBack();
    }
}
