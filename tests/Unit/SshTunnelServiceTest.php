<?php

use App\DTOs\Connection;
use App\Exceptions\SshTunnelException;
use App\Services\SshTunnelService;

it('builds an ssh -L command with a private key and username', function () {
    $connection = new Connection(
        name: 'alfa',
        driver: 'mysql',
        host: 'db.internal',
        port: 3306,
        sshHost: 'bastion.example.com',
        sshPort: 2222,
        sshUsername: 'deploy',
        sshPrivateKey: '/home/me/.ssh/id_ed25519',
    );

    $command = (new SshTunnelService)->command($connection, 54321);

    expect($command)->toBe([
        'ssh', '-N',
        '-L', '54321:db.internal:3306',
        '-p', '2222',
        '-o', 'StrictHostKeyChecking=accept-new',
        '-o', 'ExitOnForwardFailure=yes',
        '-i', '/home/me/.ssh/id_ed25519',
        'deploy@bastion.example.com',
    ]);
});

it('forwards to an explicit target host instead of the connection host', function () {
    $connection = new Connection(name: 'alfa', driver: 'pgsql', host: '127.0.0.1', port: 5432, sshHost: 'vps');

    expect((new SshTunnelService)->command($connection, 54321, '172.18.0.5')[3])->toBe('54321:172.18.0.5:5432');
});

it('builds a docker inspect command over the same ssh options', function () {
    $connection = new Connection(
        name: 'alfa',
        driver: 'pgsql',
        port: 5432,
        sshHost: 'vps',
        sshPort: 2222,
        sshUsername: 'deploy',
        sshPrivateKey: '/k',
        dockerContainer: "pg'x",
    );

    expect((new SshTunnelService)->dockerInspectCommand($connection))->toBe([
        'ssh',
        '-p', '2222',
        '-o', 'StrictHostKeyChecking=accept-new',
        '-i', '/k',
        'deploy@vps',
        "docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' 'pg'\\''x'",
    ]);
});

it('explains why a docker container could not be resolved', function (string $stderr, string $reason) {
    expect(SshTunnelException::containerUnresolved('pg', 'vps', $stderr)->getMessage())
        ->toBe("Docker container \"pg\" on vps: {$reason}");
})->with([
    ['Error: No such object: pg', 'not found'],
    ['permission denied while trying to connect to the Docker daemon socket', 'permission denied (is the SSH user in the docker group?)'],
    ['', 'has no IP address (is it attached to a Docker network?)'],
]);

it('builds an ssh command without a key or username, defaulting the ssh port to 22', function () {
    $connection = new Connection(
        name: 'alfa',
        driver: 'mysql',
        host: 'db.internal',
        port: 3306,
        sshHost: 'bastion.example.com',
    );

    $command = (new SshTunnelService)->command($connection, 54321);

    expect($command)->toBe([
        'ssh', '-N',
        '-L', '54321:db.internal:3306',
        '-p', '22',
        '-o', 'StrictHostKeyChecking=accept-new',
        '-o', 'ExitOnForwardFailure=yes',
        'bastion.example.com',
    ]);
});
