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

it('derives a connection targeting a different database on the same server', function () {
    $server = new Connection(name: 'server', driver: 'mysql', host: '127.0.0.1', port: 3306, username: 'root');
    $shop = $server->withDatabase('shop');

    expect($shop->database)->toBe('shop')
        ->and($shop->host)->toBe($server->host)
        ->and($shop->username)->toBe($server->username);
});
