# Database Engine Rules

Use these rules whenever a feature can affect snapshot creation, restore, metadata, install checks, docs, config, or command output across database engines.

## Supported Engines

- Tyro Checkpoint supports SQLite, MySQL, and PostgreSQL.
- Treat all three engines as current product surface, not optional edge cases.
- Do not add SQLite-only behavior unless the feature is explicitly SQLite-specific and the limitation is documented.
- Do not describe the package as SQLite-only in docs, comments, errors, examples, tests, or release notes.

## Engine-Neutral First Pass

- Design service-level features so they work through `DriverManager` and the `CheckpointDriver` contract.
- Keep generic checkpoint metadata independent of the underlying engine whenever possible.
- Preserve `driver` and `database` metadata so restore can protect users from restoring into the wrong target.
- When adding a metadata field, consider how older checkpoints and all three drivers will read it.

## Engine-Specific Work

- SQLite behavior belongs in `SqliteCheckpointDriver`.
- MySQL behavior belongs in `MysqlCheckpointDriver`.
- PostgreSQL behavior belongs in `PostgresCheckpointDriver`.
- Shared connection parsing belongs in `src/Drivers/Concerns/ResolvesConnectionConfig.php` or another shared driver helper when the pattern is genuinely common.
- MySQL and PostgreSQL shell tools must use configured binary paths from `config('tyro-checkpoint.binaries')`.
- Process execution should go through `ProcessRunner`, never direct shell calls from commands.

## Install And Configuration

- Install/setup checks should validate required binaries for MySQL/PostgreSQL when those engines are selected.
- Config additions should mention engine applicability clearly.
- README examples should show or mention SQLite, MySQL, and PostgreSQL when a feature is engine-sensitive.
- Error messages should name the active connection/driver when that helps users fix configuration.

## Testing Expectations

- For engine-neutral service changes, include coverage that does not accidentally lock behavior to SQLite.
- For driver changes, cover each affected driver or explain the remaining test gap.
- Prefer faking `ProcessRunner` for MySQL/PostgreSQL command construction instead of requiring live database binaries.
- Re-run or update `tests/Unit/DriverManagerTest.php` when engine resolution changes.
