<?php

namespace Cronos\Database;

class DBexecute
{
    public static function statement(string $query, array $bind = []): mixed
    {
        return app(DatabaseDriver::class)->statement($query, $bind);
    }
}
