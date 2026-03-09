<?php

/**
 * Helpers para testing del Cronos Framework.
 * 
 * Este archivo define las funciones globales app() y session()
 * que se usan en los tests de integración.
 */

/**
 * Retorna el mock de App para testing.
 */
function app()
{
    return $GLOBALS['test_app'] ?? null;
}

/**
 * Retorna el mock de Session para testing.
 */
function session()
{
    return $GLOBALS['test_session'] ?? null;
}
