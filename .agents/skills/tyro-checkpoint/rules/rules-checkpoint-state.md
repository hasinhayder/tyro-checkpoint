# Checkpoint State Rules

Use these rules for features or fixes around flagging, unflagging, locking, unlocking, and encrypting existing checkpoints.

## Ownership

- Keep state transitions in `CheckpointService`: `flag`, `unflag`, `lock`, `unlock`, and `encrypt`.
- Keep command classes responsible for choosing/checking the identifier, displaying checkpoint lists, asking for confirmation, and formatting output.
- Do not duplicate metadata mutation logic in commands.
- Preserve atomic metadata writes through the service save path.

## Flag And Unflag

- Flags are attention markers, not safety locks.
- Flagging should not block restore, delete, encryption, or other operations unless the user explicitly asks for a new policy.
- `tyro-checkpoint:flag` and `tyro-checkpoint:unflag` should accept ID or name identifiers.
- When no identifier is passed, show the checkpoint list before prompting.
- Keep flag/unflag idempotent or harmless where possible: a checkpoint already in the requested state should not corrupt metadata or create duplicate state.
- Keep list/details output aware of flag state so users can see the marker after changing it.

## Lock And Unlock

- Locks protect checkpoints from accidental deletion and flush operations.
- Locking should not prevent restore, details, note updates, flag/unflag, or encryption unless the user explicitly asks for stricter behavior.
- `tyro-checkpoint:lock` and `tyro-checkpoint:unlock` should accept ID or name identifiers.
- When no identifier is passed, show the checkpoint list before prompting.
- Delete and flush paths must respect lock state; any bypass should be explicit and documented.
- Keep lock/unlock idempotent or harmless where possible.

## Existing-Checkpoint Encryption

- `tyro-checkpoint:encrypt` encrypts an existing checkpoint in place; it must not create a new checkpoint entry.
- It should update the existing metadata to encrypted, update file metadata as needed, and remove the original unencrypted snapshot.
- It must not double-encrypt. Already-encrypted checkpoints should be a successful no-op.
- When no identifier is passed, list only unencrypted checkpoints.
- Show checkpoint details and ask for confirmation before removing the original unencrypted file.
- Preserve restore compatibility: encrypted checkpoints must auto-decrypt through the existing restore path.
- Keep lock and flag metadata intact when encrypting.

## Documentation And Tests

- Update README examples when command behavior, prompts, or safety semantics change.
- Update `CHANGELOG.md` for user-visible state behavior changes.
- Prefer tests that assert metadata state and safety behavior: locked checkpoints cannot be deleted/flushed, flags remain visible, encryption is in-place and idempotent.
