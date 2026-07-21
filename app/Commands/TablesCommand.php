<?php

namespace App\Commands;

use App\Concerns\FormatsOutput;
use App\Services\ConnectionService;
use App\Services\DatabaseService;
use LaravelZero\Framework\Commands\Command;

class TablesCommand extends Command
{
    use FormatsOutput;

    protected $signature = 'tables {connection : Saved connection profile name} {--format=table : table|json|csv}';

    protected $description = 'List tables in a database';

    public function handle(ConnectionService $connections, DatabaseService $database): int
    {
        $connection = $connections->getOrFail((string) $this->argument('connection'));
        $pdo = $database->connect($connection);

        $rows = array_map(
            static fn (string $table) => ['table' => $table],
            $database->tables($pdo, $connection->driver)
        );

        $this->renderRows($rows, (string) $this->option('format'));

        return self::SUCCESS;
    }
}
