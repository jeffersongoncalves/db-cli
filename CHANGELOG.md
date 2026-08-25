# Changelog

All notable changes to this project will be documented in this file.

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


