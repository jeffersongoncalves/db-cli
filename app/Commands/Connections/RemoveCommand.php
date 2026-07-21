<?php

namespace App\Commands\Connections;

use App\Services\ConnectionService;
use LaravelZero\Framework\Commands\Command;

class RemoveCommand extends Command
{
    protected $signature = 'connections:remove {name : Profile name}';

    protected $description = 'Remove a saved database connection profile';

    public function handle(ConnectionService $connections): int
    {
        $name = (string) $this->argument('name');

        if (! $connections->forget($name)) {
            $this->components->error("Connection \"{$name}\" not found.");

            return self::FAILURE;
        }

        $this->components->info("Connection \"{$name}\" removed.");

        return self::SUCCESS;
    }
}
