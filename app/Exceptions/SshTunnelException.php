<?php

namespace App\Exceptions;

use RuntimeException;

class SshTunnelException extends RuntimeException
{
    public static function failed(string $connectionName, string $errorOutput): self
    {
        $reason = trim($errorOutput) !== '' ? trim($errorOutput) : 'ssh exited unexpectedly';

        return new self("SSH tunnel for \"{$connectionName}\" failed: {$reason}");
    }

    public static function timedOut(string $connectionName): self
    {
        return new self("SSH tunnel for \"{$connectionName}\" timed out waiting for the forwarded port to open.");
    }
}
