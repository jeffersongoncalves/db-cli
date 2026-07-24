<?php

namespace App\Commands;

use App\Concerns\FormatsOutput;
use App\Services\ConnectionService;
use App\Services\DatabaseService;
use LaravelZero\Framework\Commands\Command;

class SampleCommand extends Command
{
    use FormatsOutput;

    protected $signature = 'sample {connection : Saved connection profile name} {table : Table name}
        {--database= : Override the profile\'s database (same server, different database)}
        {--column= : Limit to one column (default: all columns)}
        {--distinct : Return distinct values (requires --column)}
        {--limit=20 : Row cap}
        {--format=table : table|json|csv}';

    protected $description = 'Preview rows (or distinct values of a column) from a table';

    public function handle(ConnectionService $connections, DatabaseService $database): int
    {
        $connection = $connections->getOrFail((string) $this->argument('connection'));

        if ($override = $this->option('database')) {
            $connection = $connection->withDatabase((string) $override);
        }

        $pdo = $database->connect($connection);

        $column = $this->option('column');
        $distinct = (bool) $this->option('distinct');

        if ($distinct && $column === null) {
            $this->components->error('--distinct requires --column.');

            return self::FAILURE;
        }

        $rows = $database->sample(
            $pdo,
            (string) $this->argument('table'),
            $column !== null ? (string) $column : null,
            $distinct,
            (int) $this->option('limit'),
        );

        $this->renderRows($rows, (string) $this->option('format'));

        return self::SUCCESS;
    }
}
