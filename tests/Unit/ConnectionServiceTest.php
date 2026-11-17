<?php

use App\DTOs\Connection;
use App\Services\ConnectionService;

beforeEach(function () {
    $this->originalHome = getenv('HOME');
    $this->tempHome = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('db-cli-test-');
    mkdir($this->tempHome);
    putenv('HOME='.$this->tempHome);
});

afterEach(function () {
    putenv($this->originalHome === false ? 'HOME' : "HOME={$this->originalHome}");
});

it('saves and reloads a connection profile', function () {
    $connections = new ConnectionService;

    $connections->save(new Connection(name: 'alfa', driver: 'mysql', host: '127.0.0.1', port: 3306, database: 'app', username: 'root', password: 'secret'));

    $reloaded = (new ConnectionService)->getOrFail('alfa');

    expect($reloaded->driver)->toBe('mysql')
        ->and($reloaded->host)->toBe('127.0.0.1')
        ->and($reloaded->database)->toBe('app')
        ->and($reloaded->password)->toBe('secret');
});

it('removes a connection profile', function () {
    $connections = new ConnectionService;
    $connections->save(new Connection(name: 'alfa', driver: 'sqlite', database: '/tmp/a.db'));

    expect($connections->forget('alfa'))->toBeTrue()
        ->and($connections->forget('alfa'))->toBeFalse()
        ->and($connections->get('alfa'))->toBeNull();
});

it('saves and reloads a connection profile without a database', function () {
    $connections = new ConnectionService;
    $connections->save(new Connection(name: 'server', driver: 'mysql', host: '127.0.0.1', port: 3306, username: 'root'));

    $reloaded = (new ConnectionService)->getOrFail('server');

    expect($reloaded->database)->toBe('');
});

it('saves and reloads a connection profile with an SSH tunnel', function () {
    $connections = new ConnectionService;
    $connections->save(new Connection(
        name: 'bastion',
        driver: 'pgsql',
        host: 'db.internal',
        port: 5432,
        username: 'app',
        sshHost: 'bastion.example.com',
        sshPort: 2222,
        sshUsername: 'deploy',
        sshPrivateKey: '/home/me/.ssh/id_ed25519',
    ));

    $reloaded = (new ConnectionService)->getOrFail('bastion');

    expect($reloaded->sshHost)->toBe('bastion.example.com')
        ->and($reloaded->sshPort)->toBe(2222)
        ->and($reloaded->sshUsername)->toBe('deploy')
        ->and($reloaded->sshPrivateKey)->toBe('/home/me/.ssh/id_ed25519');
});

it('parses a TablePlus-style connection URL with special characters in the password', function () {
    $c = Connection::fromUrl('mysql://user:pass_#_qu@l/q@db.example.com:3307/app?statusColor=005392&env=production&name=BANCO%20-%20Nuvem&tLSMode=0');

    expect($c->name)->toBe('BANCO - Nuvem')
        ->and($c->driver)->toBe('mysql')
        ->and($c->username)->toBe('user')
        ->and($c->password)->toBe('pass_#_qu@l/q')
        ->and($c->host)->toBe('db.example.com')
        ->and($c->port)->toBe(3307)
        ->and($c->database)->toBe('app');
});

it('fills connection URL defaults and lets the name argument win', function () {
    $c = Connection::fromUrl('postgresql://app@10.0.0.5?name=ignored', 'prod');

    expect($c->name)->toBe('prod')
        ->and($c->driver)->toBe('pgsql')
        ->and($c->port)->toBe(5432)
        ->and($c->password)->toBeNull()
        ->and($c->database)->toBe('');
});

it('rejects connection URLs without a name or with an unknown driver', function (string $url) {
    Connection::fromUrl($url);
})->with(['mysql://u:p@h/db', 'mongodb://u:p@h/db?name=x', 'not a url'])->throws(InvalidArgumentException::class);

it('derives a connection targeting a different database on the same server', function () {
    $server = new Connection(name: 'server', driver: 'mysql', host: '127.0.0.1', port: 3306, username: 'root');
    $shop = $server->withDatabase('shop');

    expect($shop->database)->toBe('shop')
        ->and($shop->host)->toBe($server->host)
        ->and($shop->username)->toBe($server->username);
});
