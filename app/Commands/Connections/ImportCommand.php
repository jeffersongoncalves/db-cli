<?php

namespace App\Commands\Connections;

use App\DTOs\Connection;
use App\Services\ConnectionService;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;

class ImportCommand extends Command
{
    protected $signature = 'connections:import
        {url? : Connection URL, e.g. mysql://user:pass@host:3306/database?name=alfa. Use single quotes: inside double quotes the shell expands "$..." in the password. Omit it to paste the URL at a prompt instead}
        {name? : Profile name (defaults to the URL\'s ?name= parameter)}
        {--force : Overwrite an existing profile without asking}';

    protected $description = 'Save a connection profile from a connection URL (e.g. exported from TablePlus)';

    public function handle(ConnectionService $connections): int
    {
        try {
            $name = $this->argument('name');
            // Pasted at a prompt, the URL never goes through the shell, so "$", "!" etc. in the password survive.
            $url = (string) ($this->argument('url') ?? password(label: 'Connection URL', required: true));
            $connection = Connection::fromUrl($url, is_string($name) && $name !== '' ? $name : null);
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
