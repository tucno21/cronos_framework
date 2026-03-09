<?php

use Cronos\Session\Session;

/**
 * Helper global para obtener la instancia de Session.
 * 
 * En modo de testing, usa $GLOBALS['test_session'] directamente.
 * En producción, usa app()->session.
 */
function session(): Session
{
    // Si estamos en modo de testing, usar la sesión mockeada
    if (isset($GLOBALS['test_session'])) {
        return $GLOBALS['test_session'];
    }

    // En producción, usar app()->session
    return app()->session;
}

function ifError(string $input): bool
{
    return session()->ifError($input);
}

function error(string $input): ?string
{
    return session()->error($input);
}

function old(string $input): ?string
{
    return session()->old($input);
}
