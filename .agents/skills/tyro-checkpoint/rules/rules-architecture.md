# Architecture Rules

## Core Boundaries

- `CheckpointService` owns create, restore, delete, flush, lock, flag, notes, encryption, metadata loading, metadata saving, and storage paths.
- Commands should call service methods rather than duplicating metadata or filesystem logic.
- Drivers own database dump/restore mechanics for their engine.
- `DriverManager` resolves the active driver from Laravel database configuration and package config.

## Metadata And Storage

- Checkpoint metadata lives in `checkpoints.json` outside the application database so restore operations do not erase checkpoint history.
- Keep metadata writes atomic and backup-aware. Existing `saveCheckpoints()` writes a temp file, validates JSON, renames it, and cleans up temp files.
- Validate checkpoint names and notes through the service-level constraints.
- Keep path traversal protections around checkpoint file paths.
- Preserve legacy compatibility when adding metadata fields; older checkpoints may not have newly introduced fields.

## Encryption

- Checkpoint encryption is opt-in unless a specific command/config says otherwise.
- Encrypted checkpoints should remain restorable and should not be double-encrypted.
- `tyro-checkpoint:encrypt` is an in-place operation for existing unencrypted checkpoints. It updates metadata and removes the original unencrypted file rather than creating a second checkpoint entry.
- Use `config('tyro-checkpoint.encryption_key')` and the existing Laravel encrypter pattern.
- Be explicit in docs and output when a command creates or converts encrypted checkpoint files.

## Safety Defaults

- Locked checkpoints should be protected from accidental deletion and flush operations.
- Restore should guard against restoring into a different database of the same engine unless the existing force/bypass convention is used.
- Avoid broad service rewrites for narrow command UX changes.
