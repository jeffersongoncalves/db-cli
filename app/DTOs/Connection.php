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
    ) {}

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
        ];
    }
}
