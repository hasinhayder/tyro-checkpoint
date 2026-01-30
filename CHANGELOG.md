# Changelog

All notable changes to Tyro Checkpoint will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-01-30

### Changed

**BREAKING**: Checkpoint metadata storage moved from database to JSON file

- Checkpoint metadata is now stored in `storage/tyro-checkpoints/checkpoints.json` instead of the `tyro_checkpoints` database table
- This prevents loss of checkpoint history when restoring to an earlier database state
- The Checkpoint model is no longer an Eloquent model, but a simple data object

### Added

- New `CheckpointFlushCommand` to delete all checkpoints at once
- `--force` flag for flush command to skip confirmation
- Better documentation about checkpoint persistence after restoration
- Upgrade guide for users migrating from v1.0.x

### Fixed

- **Critical**: Restoring a checkpoint no longer causes loss of checkpoint metadata for checkpoints created after that point
- Checkpoint list now survives database restoration

### Migration Notes

If upgrading from v1.0.x:
- Your existing checkpoint files (`.sqlite`) are preserved
- The checkpoint list will appear empty (old metadata was in the database)
- Simply start creating new checkpoints - they will be tracked in the new JSON file
- Optionally delete old checkpoint files you no longer need

## [1.0.0] - 2026-01-23

### Added

- Initial release
- Create database checkpoints with custom or auto-generated names
- List all checkpoints with metadata
- Restore checkpoints to reset database state
- Delete individual checkpoints
- SQLite-only support for local development
- Full error handling and validation
- Installation command for easy setup
- Version command for system information
