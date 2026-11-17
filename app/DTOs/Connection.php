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
        public readonly ?string $dockerContainer = null,
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
            dockerContainer: $this->dockerContainer,
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
            dockerContainer: $this->dockerContainer,
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
            dockerContainer: isset($data['docker_container']) ? (string) $data['docker_container'] : null,
        );
    }

    /**
     * Parses a connection URL such as the ones TablePlus exports:
     * mysql://user:pass@host:port/database?name=Label&env=production.
     * Parsed by hand instead of parse_url() because exported passwords often
     * carry raw "#", "@" or "/" characters, which parse_url() splits on.
     */
    public static function fromUrl(string $url, ?string $name = null): self
    {
        // ponytail: plain URLs only; TablePlus "+ssh" URLs need their own parser
        if (! preg_match('~^([a-z0-9]+)://(?:([^:@/]*)(?::(.*))?@)?([^:/?@]*)(?::(\d+))?(?:/([^?]*))?(?:\?(.*))?$~is', trim($url), $m)) {
            throw new \InvalidArgumentException('Invalid connection URL.');
        }

        $driver = match (strtolower($m[1])) {
            'mysql', 'mariadb' => 'mysql',
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            default => throw new \InvalidArgumentException("Unsupported driver \"{$m[1]}\". Use mysql or pgsql (add sqlite profiles with connections:add)."),
        };

        parse_str($m[7] ?? '', $query);
        $name = $name ?: (is_string($query['name'] ?? null) && $query['name'] !== '' ? $query['name'] : null);

        if ($name === null) {
            throw new \InvalidArgumentException('No profile name: pass one or add ?name= to the URL.');
        }

        $database = rawurldecode($m[6] ?? '');

        return new self(
            name: $name,
            driver: $driver,
            host: $m[4] !== '' ? $m[4] : '127.0.0.1',
            port: ($m[5] ?? '') !== '' ? (int) $m[5] : ($driver === 'mysql' ? 3306 : 5432),
            database: $database,
            username: $m[2] !== '' ? rawurldecode($m[2]) : null,
            password: $m[3] !== '' ? rawurldecode($m[3]) : null,
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
            'docker_container' => $this->dockerContainer,
        ];
    }
}
