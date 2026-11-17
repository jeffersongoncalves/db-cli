<?php

namespace App\Commands\Connections;

use App\DTOs\Connection;
use App\Services\ConnectionService;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;

class ImportCommand extends Command
{
    protected $signature = 'connections:import
        {url : Connection URL, e.g. mysql://user:pass@host:3306/database?name=alfa (quote it in the shell)}
        {name? : Profile name (defaults to the URL\'s ?name= parameter)}
        {--force : Overwrite an existing profile without asking}';

    protected $description = 'Save a connection profile from a connection URL (e.g. exported from TablePlus)';

    public function handle(ConnectionService $connections): int
    {
        try {
            $name = $this->argument('name');
            $connection = Connection::fromUrl((string) $this->argument('url'), is_string($name) && $name !== '' ? $name : null);
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($connections->get($connection->name) && ! $this->option('force')
            && ! confirm("Connection \"{$connection->name}\" already exists. Overwrite?", default: false)) {
            $this->components->warn('Aborted.');

            return self::SUCCESS;
        }

        $connections->save($connection);

        $this->components->info("Connection \"{$connection->name}\" saved to {$connections->path()}");

        return self::SUCCESS;
    }
}
