<div class="filament-hidden">

![db-cli](https://raw.githubusercontent.com/jeffersongoncalves/db-cli/main/art/jeffersongoncalves-db-cli.png)

</div>

# db-cli

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-FFDD00?style=flat-square&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/jeffersongoncalves)

CLI to inspect and query relational databases (MySQL, PostgreSQL, SQLite)
through named connection profiles, with table/json/csv output. Built to pair
with an LLM doing schema exploration — describe a table, sample rows, run a
read-only query — without hand-rolling a PHP script per database each time.

Built with [Laravel Zero](https://laravel-zero.com) and modeled on the other
CLIs in this monorepo.

<p align="center">
  <a href="https://github.com/jeffersongoncalves/db-cli/actions"><img src="https://github.com/jeffersongoncalves/db-cli/actions/workflows/run-tests.yml/badge.svg" alt="Tests" /></a>
  <a href="https://packagist.org/packages/jeffersongoncalves/db-cli"><img src="https://img.shields.io/packagist/dt/jeffersongoncalves/db-cli" alt="Total Downloads" /></a>
  <a href="https://github.com/jeffersongoncalves/db-cli/blob/main/LICENSE"><img src="https://img.shields.io/github/license/jeffersongoncalves/db-cli" alt="License" /></a>
  <img src="https://img.shields.io/badge/php-%3E%3D8.2-8892BF" alt="PHP 8.2+" />
</p>

## Requirements

- PHP `^8.2` with `ext-pdo` (plus the driver you need: `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`)

## Install

### Global (recommended)

```bash
composer global require jeffersongoncalves/db-cli
```

The binary `db` will be on your `PATH` as long as Composer's global
`vendor/bin` is in it.

### From source

```bash
git clone https://github.com/jeffersongoncalves/db-cli.git
cd db-cli
composer install
```

## Usage

### Update

```bash
db self-update
```

### Connection profiles

Profiles are saved to `~/.db-cli/config.json` (mode `0600`).

```bash
db connections:add            # interactive: name, driver, host, port, database, user, password
db connections:list
db connections:remove alfa
```

### Explore a database

```bash
db tables alfa
db describe alfa users
db sample alfa users
db sample alfa users --column=role --distinct
```

### Run a read-only query

```bash
db query alfa "select id, email from users where active = 1"
db query alfa "select * from orders" --limit=50 --format=json
```

`query` only accepts `SELECT` / `SHOW` / `DESCRIBE` / `EXPLAIN` / `WITH` /
`PRAGMA`, rejects stacked statements (`a; b`), and appends a `LIMIT` when the
query doesn't already have one — this CLI is meant for read-only exploration,
including against production databases.

### Benchmark a query

```bash
db benchmark alfa "select * from orders where status = 'open'" --runs=20
db benchmark alfa --file=queries.sql --runs=20 --warmup=2 --format=json
```

Runs the statement `--runs` times (plus `--warmup` discarded runs to prime
caches) and reports min/avg/median/max in milliseconds, plus the row count.
`--file` accepts a `.sql` file with one or more `;`-terminated statements;
a `-- label` comment on a statement names it in the output, otherwise
statements are labeled `q1`, `q2`, ... Same read-only restriction as `query`.

### Output formats

Every read command accepts `--format=table|json|csv` (default `table`).
`json` is the format to reach for when piping results to an LLM or another
program.

## Development

```bash
composer install
composer test       # Pest tests + Pint lint
composer lint        # Auto-fix style
composer phpstan      # Static analysis
composer build        # Build the PHAR into builds/db
```

## License

MIT © Jefferson Goncalves
