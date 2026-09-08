<?php

use App\DTOs\Connection;
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
