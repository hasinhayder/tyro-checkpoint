# Changelog

All notable changes to Tyro Checkpoint will be documented in this file.

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
