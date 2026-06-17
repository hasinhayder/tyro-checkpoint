# Changelog

All notable changes to Tyro Checkpoint will be documented in this file.

## [1.7.0] - 2026-06-17

### Added

- Add `tyro-checkpoint:encrypt` command to encrypt an existing unencrypted checkpoint in place. The command lists only unencrypted checkpoints, prompts for an ID, encrypts the snapshot, removes the original unencrypted file, and updates the metadata so the checkpoint remains restorable. No new checkpoint entry is created. Running it on an already-encrypted checkpoint is an idempotent no-op (it will not double-encrypt).
- Add `CheckpointService::encrypt()` for in-place encryption of an existing checkpoint.
- Add configurable `tyro-checkpoint.process.timeout` (env: `TYRO_CHECKPOINT_PROCESS_TIMEOUT`, default 600s) so large databases don't time out during snapshot/restore operations.

### Changed

- `tyro-checkpoint:encrypt` is now idempotent: running it on an already-encrypted checkpoint is a successful no-op rather than an error.
- `SymfonyProcessRunner` now reads the process timeout from config instead of a hardcoded 600 seconds.

## [1.6.0] - 2026-06-17

### Added

- Add MySQL and PostgreSQL support via driver abstraction.
- Add `DriverManager` with `SqliteCheckpointDriver`, `MysqlCheckpointDriver`, and `PostgresCheckpointDriver`.
- Add `ProcessRunner` abstraction (default: `SymfonyProcessRunner`) for testability and credential safety.
- Add `connection` and `binaries` configuration options.
- Add `driver` field to checkpoint metadata for forward compatibility.
- Add `database` field to checkpoint metadata to track which database each checkpoint belongs to. Legacy checkpoints without this field are treated as SQLite.
- Restore now guards against restoring a checkpoint into a different database of the same engine; bypass with the `--force` flag.
- `tyro-checkpoint:details` and `tyro-checkpoint:list` now show the checkpoint driver and database.
- `tyro-checkpoint:install` supports MySQL and PostgreSQL connections with binary validation.

### Changed

- `CheckpointService` delegates snapshot creation and restoration to the resolved driver.
- `getDatabasePath()` is now deprecated and SQLite-only.
- `composer.json` description no longer states "SQLite only".
- Add explicit `symfony/process` dependency.

## [1.5.0] - 2026-06-17

### Added

- Add opt-in auto-checkpoints before risky Artisan commands like migrations, seeders, and database wipes.
- Add `tyro-checkpoint:details` command to inspect a checkpoint by ID or name.
- Add `tyro-checkpoint:list {identifier}` as a shortcut for checkpoint details.
- Add `tyro-checkpoint:flag` and `tyro-checkpoint:unflag` commands to mark checkpoints with a flag icon.
- `tyro-checkpoint:flag` and `tyro-checkpoint:unflag` now display the full checkpoint list before prompting for an identifier.
- `tyro-checkpoint:lock` and `tyro-checkpoint:unlock` now ask for the checkpoint identifier interactively when none is provided.

## [1.4.0] - 2026-06-17

### Added

- Add `--silent` option to `tyro-checkpoint:create` for non-interactive checkpoint creation in cron jobs and scripts.

## [1.3.1] - 2026-03-18

### Changed

- Update version metadata for Laravel 13 compatibility.

## [1.3.0] - 2026-03-18

### Changed

- Add compatibility with Laravel 13.

## [1.2.0] - 2026-02-15

### Changed

- Improve security checks around checkpoint operations.
- Improve delete command user experience.

## [1.1.0] - 2026-02-10

### Added

- Add encrypted checkpoint support.

## [1.0.0] - 2026-02-01

### Added

- **Note**: Add descriptive notes to checkpoints with `tyro-checkpoint:add-note {id}`
- **Lock**: Protect checkpoints from accidental deletion with `tyro-checkpoint:lock {id}` and `tyro-checkpoint:unlock {id}`
- Create database checkpoints with custom or auto-generated names
- List all checkpoints with metadata (ID, name, size, created date, note, lock status)
- Restore checkpoints to reset database state
- Delete individual checkpoints
- Flush all unlocked checkpoints at once with `tyro-checkpoint:flush`
- Publish configuration file with `tyro-checkpoint:publish-config`
- SQLite-only support for local development
- Checkpoint metadata stored externally in JSON file (survives database restoration)
