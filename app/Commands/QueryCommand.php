<?php

namespace App\Commands;

use App\Concerns\FormatsOutput;
use App\Exceptions\UnsafeQueryException;
use App\Services\ConnectionService;
use App\Services\DatabaseService;
use LaravelZero\Framework\Commands\Command;

class QueryCommand extends Command
{
    use FormatsOutput;

    protected $signature = 'query {connection : Saved connection profile name} {sql : Read-only SQL statement} {--limit=100 : Row cap appended when the query has no LIMIT} {--format=table : table|json|csv}';

    protected $description = 'Run a read-only SQL statement (SELECT/SHOW/DESCRIBE/EXPLAIN/WITH/PRAGMA only)';

    public function handle(ConnectionService $connections, DatabaseService $database): int
    {
        $connection = $connections->getOrFail((string) $this->argument('connection'));
        $pdo = $database->connect($connection);

        try {
            $rows = $database->query($pdo, (string) $this->argument('sql'), (int) $this->option('limit'));
        } catch (UnsafeQueryException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->renderRows($rows, (string) $this->option('format'));

        return self::SUCCESS;
    }
}
