<?php

use Cronos\Session\Session;

function session(): Session
{
    return app()->session;
}
