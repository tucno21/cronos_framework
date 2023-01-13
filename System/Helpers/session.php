<?php

use Cronos\Session\Session;

function session(): Session
{
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
