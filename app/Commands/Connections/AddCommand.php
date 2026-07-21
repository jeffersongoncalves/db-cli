<?php

namespace App\Commands\Connections;

use App\DTOs\Connection;
use App\Services\ConnectionService;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddCommand extends Command
{
    protected $signature = 'connections:add {name? : Profile name (e.g. "alfa")}';

    protected $description = 'Save a named database connection profile';

    public function handle(ConnectionService $connections): int
    {
        $name = $this->argument('name') ?: text(label: 'Profile name', placeholder: 'alfa', required: true);

        $driver = select(
            label: 'Driver',
            options: ['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite'],
            default: 'mysql',
        );

        if ($driver === 'sqlite') {
            $database = text(label: 'Database file path', required: true);
            $host = null;
            $port = null;
            $username = null;
            $password = null;
        } else {
            $host = text(label: 'Host', default: '127.0.0.1', required: true);
            $port = (int) text(label: 'Port', default: $driver === 'mysql' ? '3306' : '5432', required: true);
            $database = text(label: 'Database name', required: true);
            $username = text(label: 'Username', default: 'root');
            $password = password(label: 'Password');
        }

        $connections->save(new Connection(
            name: $name,
            driver: $driver,
            host: $host,
            port: $port,
            database: $database,
            username: $username ?: null,
            password: $password ?: null,
        ));

        $this->components->info("Connection \"{$name}\" saved to {$connections->path()}");

        return self::SUCCESS;
    }
}
