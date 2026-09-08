<?php

namespace App\DTOs;

class Connection
{
    public function __construct(
        public readonly string $name,
        public readonly string $driver,
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        public readonly string $database = '',
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?string $sshHost = null,
        public readonly ?int $sshPort = null,
        public readonly ?string $sshUsername = null,
        public readonly ?string $sshPrivateKey = null,
    ) {}

    public function withDatabase(string $database): self
    {
        return new self(
            name: $this->name,
            driver: $this->driver,
            host: $this->host,
            port: $this->port,
            database: $database,
            username: $this->username,
            password: $this->password,
            sshHost: $this->sshHost,
            sshPort: $this->sshPort,
            sshUsername: $this->sshUsername,
            sshPrivateKey: $this->sshPrivateKey,
        );
    }

    /**
     * Points the connection at a local port instead of its real host, used
     * once an SSH tunnel has forwarded that port to $this->host:$this->port.
     */
    public function withTunnel(int $localPort): self
    {
        return new self(
            name: $this->name,
            driver: $this->driver,
            host: '127.0.0.1',
            port: $localPort,
            database: $this->database,
            username: $this->username,
            password: $this->password,
            sshHost: $this->sshHost,
            sshPort: $this->sshPort,
            sshUsername: $this->sshUsername,
            sshPrivateKey: $this->sshPrivateKey,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $name, array $data): self
    {
        return new self(
            name: $name,
            driver: (string) ($data['driver'] ?? 'mysql'),
            host: isset($data['host']) ? (string) $data['host'] : null,
            port: isset($data['port']) ? (int) $data['port'] : null,
            database: (string) ($data['database'] ?? ''),
            username: isset($data['username']) ? (string) $data['username'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            sshHost: isset($data['ssh_host']) ? (string) $data['ssh_host'] : null,
            sshPort: isset($data['ssh_port']) ? (int) $data['ssh_port'] : null,
            sshUsername: isset($data['ssh_username']) ? (string) $data['ssh_username'] : null,
            sshPrivateKey: isset($data['ssh_private_key']) ? (string) $data['ssh_private_key'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'ssh_host' => $this->sshHost,
            'ssh_port' => $this->sshPort,
            'ssh_username' => $this->sshUsername,
            'ssh_private_key' => $this->sshPrivateKey,
        ];
    }
}
