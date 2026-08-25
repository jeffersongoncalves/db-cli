# AGENTS.md

Guidance for an LLM/agent using the `db` CLI. Every command below is
non-interactive-capable — pass all flags and no prompts fire.

Binary: `db` (or `php db` from a source checkout).

## Common mistake — connection is NOT a flag

`connection` and `table`/`sql` are **positional arguments**, always in this
order: `db COMMAND CONNECTION [TABLE|SQL] [--options]`.

There is **no** `--connection=` option on any command. This fails every time:

```bash
db describe orders --connection=alfa   # WRONG — "table" arg missing, --connection unknown
```

Correct form — connection name comes first, positionally:

```bash
db describe alfa orders                # RIGHT — connection, then table
```

Applies to `tables`, `describe`, `sample`, `query`, `benchmark` alike:
`db describe CONNECTION TABLE`, `db query CONNECTION "SELECT ..."`, etc.

## Connection profiles

Profiles live in `~/.db-cli/config.json` (mode `0600`), keyed by name.

```bash
# Non-interactive add (all fields via flags, no prompts)
db connections:add NAME --driver=mysql --host=127.0.0.1 --port=3306 --database=DB --username=USER --password=PASS
db connections:add NAME --driver=pgsql --host=127.0.0.1 --port=5432 --database=DB --username=USER --password=PASS
db connections:add NAME --driver=sqlite --database=/path/to/file.sqlite

# List
db connections:list

# Remove (--force skips the confirm prompt)
db connections:remove NAME --force
```

`--driver` must be `mysql`, `pgsql`, or `sqlite`; anything else fails with
exit code 1. Any flag omitted falls back to an interactive prompt — always
pass every flag when scripting/calling from an agent.

## Explore a database

```bash
db tables NAME [--format=table|json|csv]
db describe NAME TABLE [--format=table|json|csv]
db sample NAME TABLE [--column=COL] [--distinct] [--limit=20] [--format=table|json|csv]
```

- `tables`: list table names.
- `describe`: columns of a table (name, type, nullable, key, default).
- `sample`: preview rows, or with `--column` + `--distinct`, distinct values
  of one column. `--distinct` requires `--column`.

## Run read-only SQL

```bash
db query NAME "SELECT id, email FROM users WHERE active = 1" [--limit=100] [--format=table|json|csv]
```

- Only `SELECT` / `SHOW` / `DESCRIBE` / `EXPLAIN` / `WITH` / `PRAGMA` are
  accepted — anything else (INSERT/UPDATE/DELETE/DDL) is rejected before it
  reaches the database.
- Stacked statements (`a; b`) are rejected.
- If the query has no `LIMIT`, one is appended using `--limit` (default 100).
- Safe to point at production — it cannot write.

## Output format

Always pass `--format=json` when consuming output programmatically —
`table` is for human eyes, `json` is a stable structure to parse.

## Exit codes

`0` success, `1` failure (invalid driver, unknown connection, unsafe query,
`--distinct` without `--column`, connection not found on remove). Errors are
printed to stdout with an `ERROR` prefix, not raised as PHP exceptions.

## Example: end-to-end from a fresh agent

```bash
db connections:add prod --driver=mysql --host=10.0.0.5 --port=3306 --database=app --username=readonly --password="$DB_PASSWORD"
db tables prod --format=json
db describe prod users --format=json
db query prod "SELECT id, email FROM users LIMIT 5" --format=json
db connections:remove prod --force
```
