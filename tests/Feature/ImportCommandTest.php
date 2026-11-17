<?php

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

it('prompts for the URL when it is omitted', function () {
    $this->artisan('connections:import')
        ->expectsQuestion('Connection URL', 'mysql://app:pass_$Abc1@10.0.0.5:3306/app?name=prod')
        ->assertSuccessful();

    $connection = (new ConnectionService)->getOrFail('prod');

    expect($connection->host)->toBe('10.0.0.5')
        ->and($connection->password)->toBe('pass_$Abc1');
});

it('accepts the URL as an argument', function () {
    $this->artisan('connections:import', ['url' => 'postgresql://app:secret@10.0.0.6:5432/app', 'name' => 'alfa'])
        ->assertSuccessful();

    expect((new ConnectionService)->getOrFail('alfa')->host)->toBe('10.0.0.6');
});
