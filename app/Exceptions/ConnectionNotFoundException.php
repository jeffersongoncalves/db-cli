<?php

namespace App\Exceptions;

use RuntimeException;

class ConnectionNotFoundException extends RuntimeException
{
    public static function named(string $name): self
    {
        return new self("Connection \"{$name}\" not found. Run `db connections:add` or `db connections:list`.");
    }
}
