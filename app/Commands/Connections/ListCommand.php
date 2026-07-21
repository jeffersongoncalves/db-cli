<?php

namespace App\Commands\Connections;

use App\Concerns\FormatsOutput;
use App\Services\ConnectionService;
use LaravelZero\Framework\Commands\Command;

class ListCommand extends Command
{
    use FormatsOutput;

    protected $signature = 'connections:list';

    protected $description = 'List saved database connection profiles';

    public function handle(ConnectionService $connections): int
    {
        $rows = [];

        foreach ($connections->all() as $connection) {
            $rows[] = [
                'name' => $connection->name,
                'driver' => $connection->driver,
                'host' => $connection->host ?? '-',
                'database' => $connection->database,
            ];
        }

        $this->renderTable(['name', 'driver', 'host', 'database'], $rows);

        return self::SUCCESS;
    }
}
