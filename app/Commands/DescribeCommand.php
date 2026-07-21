<?php

namespace App\Commands;

use App\Concerns\FormatsOutput;
use App\Services\ConnectionService;
use App\Services\DatabaseService;
use LaravelZero\Framework\Commands\Command;

class DescribeCommand extends Command
{
    use FormatsOutput;

    protected $signature = 'describe {connection : Saved connection profile name} {table : Table name} {--format=table : table|json|csv}';

    protected $description = 'Show columns of a table (name, type, nullable, key, default)';

    public function handle(ConnectionService $connections, DatabaseService $database): int
    {
        $connection = $connections->getOrFail((string) $this->argument('connection'));
        $pdo = $database->connect($connection);

        $rows = $database->describe($pdo, $connection->driver, (string) $this->argument('table'));

        $this->renderRows($rows, (string) $this->option('format'));

        return self::SUCCESS;
    }
}
