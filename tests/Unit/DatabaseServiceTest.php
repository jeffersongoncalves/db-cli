<?php

use App\DTOs\Connection;
use App\Exceptions\UnsafeQueryException;
use App\Services\DatabaseService;

beforeEach(function () {
    $this->service = new DatabaseService;
    $this->pdo = $this->service->connect(new Connection(name: 'test', driver: 'sqlite', database: ':memory:'));
    $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, role TEXT)');
    $this->pdo->exec("INSERT INTO users (name, role) VALUES ('ana', 'admin'), ('bob', 'user'), ('cid', 'user')");
});

it('lists tables', function () {
    expect($this->service->tables($this->pdo, 'sqlite'))->toBe(['users']);
});

it('describes columns', function () {
    $columns = array_column($this->service->describe($this->pdo, 'sqlite', 'users'), 'name');

    expect($columns)->toBe(['id', 'name', 'role']);
});

it('runs a select and appends a default limit', function () {
    $rows = $this->service->query($this->pdo, 'SELECT * FROM users ORDER BY id', 2);

    expect($rows)->toHaveCount(2);
});

it('does not double-append limit when already present', function () {
    $rows = $this->service->query($this->pdo, 'SELECT * FROM users ORDER BY id LIMIT 1', 100);

    expect($rows)->toHaveCount(1);
});

it('rejects write statements', function () {
    $this->service->query($this->pdo, "UPDATE users SET name = 'x'", 100);
})->throws(UnsafeQueryException::class);

it('rejects stacked statements', function () {
    $this->service->query($this->pdo, 'SELECT 1; DROP TABLE users', 100);
})->throws(UnsafeQueryException::class);

it('samples distinct values of a column', function () {
    $rows = $this->service->sample($this->pdo, 'users', 'role', distinct: true, limit: 10);

    expect(array_column($rows, 'role'))->toBe(['admin', 'user']);
});

it('rejects invalid table identifiers', function () {
    $this->service->describe($this->pdo, 'sqlite', 'users; DROP TABLE users');
})->throws(InvalidArgumentException::class);

it('builds a mysql dsn without dbname when the connection has no database', function () {
    $connection = new Connection(name: 'test', driver: 'mysql', host: '127.0.0.1', port: 3306, database: '');
    $dsn = (new ReflectionMethod(DatabaseService::class, 'dsn'))->invoke($this->service, $connection);

    expect($dsn)->toBe('mysql:host=127.0.0.1;port=3306;charset=utf8mb4');
});

it('builds a mysql dsn with dbname when a database is set', function () {
    $connection = new Connection(name: 'test', driver: 'mysql', host: '127.0.0.1', port: 3306, database: 'shop');
    $dsn = (new ReflectionMethod(DatabaseService::class, 'dsn'))->invoke($this->service, $connection);

    expect($dsn)->toBe('mysql:host=127.0.0.1;port=3306;dbname=shop;charset=utf8mb4');
});
