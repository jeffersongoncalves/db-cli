<?php

namespace App\Services;

use App\DTOs\Connection;
use App\Exceptions\UnsafeQueryException;
use InvalidArgumentException;
use PDO;

class DatabaseService
{
    /**
     * Statement keywords allowed to run through query(). Read-only by design:
     * this CLI exists to let an LLM inspect a database, not mutate it.
     */
    private const READ_ONLY_KEYWORDS = ['select', 'show', 'describe', 'desc', 'explain', 'with', 'pragma'];

    private const IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function connect(Connection $connection): PDO
    {
        return new PDO(
            $this->dsn($connection),
            $connection->username,
            $connection->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }

    /**
     * @return list<string>
     */
    public function tables(PDO $pdo, string $driver): array
    {
        $sql = match ($driver) {
            'mysql' => 'SHOW TABLES',
            'pgsql' => "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public' ORDER BY tablename",
            'sqlite' => "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
            default => throw new InvalidArgumentException("Unsupported driver: {$driver}"),
        };

        return array_map(static fn (array $row) => (string) array_values($row)[0], $pdo->query($sql)->fetchAll());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function describe(PDO $pdo, string $driver, string $table): array
    {
        $this->assertIdentifier($table);

        return match ($driver) {
            'mysql' => $pdo->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(),
            'pgsql' => $this->preparedFetchAll($pdo,
                'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position',
                [$table]
            ),
            'sqlite' => $pdo->query('PRAGMA table_info('.$table.')')->fetchAll(),
            default => throw new InvalidArgumentException("Unsupported driver: {$driver}"),
        };
    }

    /**
     * Runs a read-only SQL statement. Rejects anything but
     * SELECT/SHOW/DESCRIBE/EXPLAIN/WITH/PRAGMA and stacked statements,
     * and appends a LIMIT when the query doesn't already have one.
     *
     * @return array<int, array<string, mixed>>
     */
    public function query(PDO $pdo, string $sql, int $limit): array
    {
        $sql = $this->guardReadOnly($sql);
        $sql = $this->applyLimit($sql, $limit);

        return $pdo->query($sql)->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sample(PDO $pdo, string $table, ?string $column, bool $distinct, int $limit): array
    {
        $this->assertIdentifier($table);

        if ($column !== null) {
            $this->assertIdentifier($column);

            $select = $distinct ? 'DISTINCT `'.$column.'`' : '`'.$column.'`';
        } else {
            $select = '*';
        }

        $sql = "SELECT {$select} FROM `{$table}` LIMIT {$limit}";

        return $pdo->query($sql)->fetchAll();
    }

    private function dsn(Connection $connection): string
    {
        $dbname = $connection->database !== '' ? 'dbname='.$connection->database.';' : '';

        return match ($connection->driver) {
            'mysql' => sprintf('mysql:host=%s;port=%d;%scharset=utf8mb4', $connection->host, $connection->port ?? 3306, $dbname),
            'pgsql' => sprintf('pgsql:host=%s;port=%d;%s', $connection->host, $connection->port ?? 5432, $dbname),
            'sqlite' => 'sqlite:'.$connection->database,
            default => throw new InvalidArgumentException("Unsupported driver: {$connection->driver}"),
        };
    }

    private function guardReadOnly(string $sql): string
    {
        $trimmed = trim($sql);
        $withoutTrailingSemicolon = rtrim($trimmed, "; \t\n\r");

        if (str_contains($withoutTrailingSemicolon, ';')) {
            throw new UnsafeQueryException('Only a single statement is allowed (no stacked queries).');
        }

        $stripped = preg_replace('#^(\s*(--[^\n]*\n|/\*.*?\*/))*\s*#s', '', $trimmed) ?? $trimmed;
        $firstWord = strtolower((string) preg_replace('/^(\w+).*/s', '$1', $stripped));

        if (! in_array($firstWord, self::READ_ONLY_KEYWORDS, true)) {
            throw new UnsafeQueryException(
                'Only read-only statements are allowed (select/show/describe/explain/with/pragma). Got: '.strtoupper($firstWord ?: '?')
            );
        }

        return $withoutTrailingSemicolon;
    }

    private function applyLimit(string $sql, int $limit): string
    {
        $firstWord = strtolower((string) preg_replace('/^\s*(\w+).*/s', '$1', $sql));

        if (! in_array($firstWord, ['select', 'with'], true)) {
            return $sql;
        }

        if (preg_match('/\blimit\b/i', $sql) === 1) {
            return $sql;
        }

        return $sql.' LIMIT '.$limit;
    }

    private function assertIdentifier(string $identifier): void
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw new InvalidArgumentException("Invalid identifier: {$identifier}");
        }
    }

    /**
     * @param  list<mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    private function preparedFetchAll(PDO $pdo, string $sql, array $params): array
    {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }
}
