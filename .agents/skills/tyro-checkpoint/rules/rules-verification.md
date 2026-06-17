# Verification Rules

## Default Checks

For narrow package changes, prefer focused checks:

```bash
php -l path/to/changed-file.php
vendor/bin/phpunit tests/Unit/RelevantTest.php
vendor/bin/pint --test
git diff --check
```

Use the smallest relevant PHPUnit target first. Broaden to `vendor/bin/phpunit` when shared service, driver, or command behavior changed broadly.

## Package Context

- This is a package checkout, not a full Laravel app.
- Do not assume a root `artisan` command is available for cache/route validation.
- Use Testbench/PHPUnit tests for package behavior.
- Avoid commands that require live MySQL/PostgreSQL binaries unless the task specifically needs integration verification.

## Useful Test Targets

- Encryption behavior: `tests/Unit/CheckpointEncryptTest.php`.
- Driver selection and connection handling: `tests/Unit/DriverManagerTest.php`.
- Process execution abstraction: `tests/Unit/SymfonyProcessRunnerTest.php`.
- Table formatting and command output: `tests/Unit/FormatsCheckpointTablesTest.php`.

## When Tests Are Not Enough

- For docs-only changes, `git diff --check` is usually enough.
- For command UX changes, add or update command tests if an existing test scaffold covers the command; otherwise run PHP syntax checks and explain any test gap.
- For driver/process changes, cover argument construction and error handling without requiring real database binaries where possible.
