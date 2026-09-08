<?php

namespace App\Commands\Connections;

use App\DTOs\Connection;
use App\Services\ConnectionService;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddCommand extends Command
{
    protected $signature = 'connections:add
        {name? : Profile name (e.g. "alfa")}
        {--driver= : mysql, pgsql or sqlite}
        {--host= : Database host}
        {--port= : Database port}
        {--database= : Database name or, for sqlite, the file path. Leave empty for mysql/pgsql to connect to the server only (pick a database later with --database on each command)}
        {--username= : Database username}
        {--password= : Database password}
        {--ssh-host= : SSH bastion host to tunnel the database connection through (leave empty to connect directly)}
        {--ssh-port= : SSH port (default 22)}
        {--ssh-username= : SSH username}
        {--ssh-private-key= : Path to the SSH private key (leave empty to use ssh-agent/default identity)}';

    protected $description = 'Save a named database connection profile';

    public function handle(ConnectionService $connections): int
    {
        $name = $this->argument('name') ?: text(label: 'Profile name', placeholder: 'alfa', required: true);

        $driver = $this->option('driver') ?: select(
            label: 'Driver',
            options: ['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite'],
            default: 'mysql',
        );

        if (! in_array($driver, ['mysql', 'pgsql', 'sqlite'], true)) {
            $this->components->error("Invalid driver \"{$driver}\". Use mysql, pgsql or sqlite.");

            return self::FAILURE;
        }

        if ($driver === 'sqlite') {
            $database = $this->option('database') ?: text(label: 'Database file path', required: true);
            $host = null;
            $port = null;
            $username = null;
            $password = null;
            $sshHost = null;
            $sshPort = null;
            $sshUsername = null;
            $sshPrivateKey = null;
        } else {
            $host = $this->option('host') ?: text(label: 'Host', default: '127.0.0.1', required: true);
            $port = (int) ($this->option('port') ?: text(label: 'Port', default: $driver === 'mysql' ? '3306' : '5432', required: true));
            $database = $this->option('database') ?: text(
                label: 'Database name',
                hint: 'Leave empty to connect to the server only, without picking a database',
            );
            $username = $this->option('username') ?: text(label: 'Username', default: 'root');
            $password = $this->option('password') ?: password(label: 'Password');

            $sshHost = $this->option('ssh-host') ?: (
                confirm(label: 'Connect through an SSH tunnel?', default: false)
                    ? text(label: 'SSH host', required: true)
                    : null
            );

            if ($sshHost) {
                $sshPort = (int) ($this->option('ssh-port') ?: text(label: 'SSH port', default: '22', required: true));
                $sshUsername = $this->option('ssh-username') ?: text(label: 'SSH username', required: true);
                $sshPrivateKey = $this->option('ssh-private-key') ?: text(
                    label: 'SSH private key path',
                    hint: 'Leave empty to use ssh-agent/default identity',
                );
                $sshPrivateKey = $sshPrivateKey ?: null;
            } else {
                $sshPort = null;
                $sshUsername = null;
                $sshPrivateKey = null;
            }
        }

        $connections->save(new Connection(
            name: $name,
            driver: $driver,
            host: $host,
            port: $port,
            database: $database,
            username: $username ?: null,
            password: $password ?: null,
            sshHost: $sshHost ?: null,
            sshPort: $sshPort,
            sshUsername: $sshUsername,
            sshPrivateKey: $sshPrivateKey,
        ));

        $this->components->info("Connection \"{$name}\" saved to {$connections->path()}");

        return self::SUCCESS;
    }
}
