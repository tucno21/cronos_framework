<?php

namespace Cronos\Crypto;

use Cronos\Crypto\Hasher;

class Bcrypt implements Hasher
{
    public function hash(string $input): string
    {
        return password_hash($input, PASSWORD_BCRYPT);
    }

    public function verify(string $input, string $hash): bool
    {
        return password_verify($input, $hash);
    }
}
