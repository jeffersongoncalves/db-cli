<?php

namespace App\Services;

use App\DTOs\Connection;
use App\Exceptions\SshTunnelException;
use Symfony\Component\Process\Process;

class SshTunnelService
{
    private const CONNECT_TIMEOUT_SECONDS = 10;

    /**
     * Opens a local port forward to $connection's real host:port through its
     * SSH config, using the system `ssh` client. The tunnel process is kept
     * alive for the lifetime of the script and stopped on shutdown.
     *
     * @return int the local port PDO should connect to instead
     */
    public function open(Connection $connection): int
    {
        $localPort = $this->freeLocalPort();

        $process = new Process($this->command($connection, $localPort));
        $process->setTimeout(null);
        $process->start();

        register_shutdown_function(static function () use ($process): void {
            if ($process->isRunning()) {
                $process->stop();
            }
        });

        $this->waitUntilOpen($process, $localPort, $connection->name);

        return $localPort;
    }

    /**
     * Builds the `ssh -L ...` argv for forwarding $localPort to the
     * connection's real host:port through its SSH bastion.
     *
     * @return list<string>
     */
    public function command(Connection $connection, int $localPort): array
    {
        $command = [
            'ssh', '-N',
            '-L', "{$localPort}:{$connection->host}:{$connection->port}",
            '-p', (string) ($connection->sshPort ?? 22),
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ExitOnForwardFailure=yes',
        ];

        if ($connection->sshPrivateKey !== null) {
            $command[] = '-i';
            $command[] = $connection->sshPrivateKey;
        }

        $command[] = $connection->sshUsername !== null
            ? "{$connection->sshUsername}@{$connection->sshHost}"
            : (string) $connection->sshHost;

        return $command;
    }

    // ponytail: bind-then-close to find a free port; a rival process could grab it
    // before ssh binds. Upgrade to a retry loop if this ever proves flaky in practice.
    private function freeLocalPort(): int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if ($socket === false) {
            throw new SshTunnelException("Could not allocate a local port for the SSH tunnel: {$errstr}");
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private function waitUntilOpen(Process $process, int $localPort, string $connectionName): void
    {
        $deadline = microtime(true) + self::CONNECT_TIMEOUT_SECONDS;

        while (microtime(true) < $deadline) {
            if (! $process->isRunning()) {
                throw SshTunnelException::failed($connectionName, $process->getErrorOutput());
            }

            $socket = @fsockopen('127.0.0.1', $localPort, $errno, $errstr, 0.2);

            if ($socket !== false) {
                fclose($socket);

                return;
            }

            usleep(100_000);
        }

        $process->stop();

        throw SshTunnelException::timedOut($connectionName);
    }
}
