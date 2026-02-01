# Changelog

All notable changes to Tyro Checkpoint will be documented in this file.

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
