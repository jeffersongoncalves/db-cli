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

    public static function containerUnresolved(string $container, string $sshHost, string $errorOutput): self
    {
        $error = trim($errorOutput);

        $reason = match (true) {
            stripos($error, 'permission denied') !== false => 'permission denied (is the SSH user in the docker group?)',
            stripos($error, 'no such object') !== false || stripos($error, 'no such container') !== false => 'not found',
            $error === '' => 'has no IP address (is it attached to a Docker network?)',
            default => $error,
        };

        return new self("Docker container \"{$container}\" on {$sshHost}: {$reason}");
    }

    public static function timedOut(string $connectionName): self
    {
        return new self("SSH tunnel for \"{$connectionName}\" timed out waiting for the forwarded port to open.");
    }
}
