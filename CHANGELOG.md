# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2026-11-13

### Features

- Resolve Docker container IP over SSH for tunneled connections

## [1.1.0] - 2026-11-04

### CI/CD

- Pass release version input through env
- Standardize dependabot config
- Standardize tests workflow
- Remove run-tests.yml (replaced by tests.yml)
- Standardize tests workflow
- Auto-merge dependabot github-actions minor/patch

### Dependencies

- **deps:** Bump orhun/git-cliff-action in the actions-deps group (#14)
- **deps:** Bump shivammathur/setup-php (#15)
- **deps:** Bump stefanzweifel/git-auto-commit-action (#17)

### Documentation

- Point tests badge to tests.yml

### Miscellaneous Tasks

- **deps-dev:** Bump league/flysystem from 3.35.2 to 3.36.0
- **deps-dev:** Bump the composer-deps group with 5 updates
- Upgrade to Laravel Zero 13

### Other

- Auto-assign issues and pull requests

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>

## [1.0.0] - 2026-09-10

### Dependencies

- **deps:** Bump orhun/git-cliff-action from 4.8.0 to 4.9.0

### Documentation

- Use 127.0.0.1 in SSH tunnel example host

## [0.2.6] - 2026-09-08

### Bug Fixes

- **ci:** Publish release as draft until PHAR asset is attached

## [0.2.5] - 2026-09-08

### Documentation

- Add Buy Me a Coffee sponsor link
- Standardize README section structure

### Features

- Add SSH tunnel support for database connections

### Miscellaneous Tasks

- Add GitHub Sponsors to FUNDING.yml

## [0.2.4] - 2026-08-25

### Bug Fixes

- **deps:** Update guzzlehttp/guzzle to patch security advisories

### CI/CD

- **release:** Generate CHANGELOG.md and release notes with git-cliff

### Documentation

- Clarify connection profile is a positional argument, not a flag

### Miscellaneous Tasks

- Normalize line endings via .gitattributes

## [0.2.3] - 2026-08-03

### CI/CD

- Pin actions to commit SHA, add dependabot cooldown/composer, trim dist archive

### Dependencies

- **deps:** Bump shivammathur/setup-php

### Features

- Add benchmark command to time read-only queries

## [0.2.2] - 2026-07-24

### Features

- Allow database-less connections and per-command database override

## [0.2.1] - 2026-07-24

### CI/CD

- Replace split build/changelog/publish-phar workflows with a single release job

## [0.2.0] - 2026-07-24

### Features

- Non-interactive connection flags, self-update, AGENTS.md

## [0.1.0] - 2026-07-21

### Bug Fixes

- Add missing phpunit.xml.dist for pest --parallel
- Drop empty Feature testsuite from phpunit.xml.dist

### Miscellaneous Tasks

- Add FUNDING.yml

### Other

- Initial commit - db-cli

CLI to inspect and query relational databases (MySQL, PostgreSQL, SQLite)
through named connection profiles, with a read-only query guard and
table/json/csv output, built for pairing with an LLM doing schema
exploration.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>


