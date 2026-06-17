# CLI Rules

## Command Shape

- Place Artisan commands in `src/Console/Commands`.
- Register new commands in `src/TyroCheckpointServiceProvider.php` inside the `runningInConsole()` command list.
- Use the existing `tyro-checkpoint:*` naming convention and concise command descriptions.
- Keep command classes responsible for console IO, prompts, options, confirmation messages, and exit codes.
- Keep `CheckpointService` responsible for checkpoint state transitions and file/driver orchestration.

## Interactive And Scripted UX

- Preserve non-interactive paths for scripts, cron jobs, and package setup flows.
- `tyro-checkpoint:create --silent` means no follow-up note prompt when no note is passed.
- If another command internally creates a checkpoint for provisioning/setup, pass options that avoid surprise prompts.
- For destructive actions, keep confirmations or explicit force-style bypasses.
- When no identifier is provided for user-facing checkpoint selection, follow the existing list-and-prompt style used by details, restore, lock, unlock, flag, and encrypt flows.

## Output Conventions

- Keep success and failure output concise and actionable.
- Preserve the package's existing checkmark/cross style where nearby commands already use it.
- Show key checkpoint metadata after create/update operations: ID/name, size when relevant, created time, encrypted/locked/flag/note when relevant.
- Return `self::SUCCESS` and `self::FAILURE` from commands.

## Common Owner Files

- Create flow: `src/Console/Commands/CheckpointCreateCommand.php`.
- Install/setup flow: `src/Console/Commands/CheckpointInstallCommand.php`.
- Existing-checkpoint encryption: `src/Console/Commands/CheckpointEncryptCommand.php`.
- Details/list flows: `src/Console/Commands/CheckpointDetailsCommand.php`, `src/Console/Commands/CheckpointListCommand.php`.
- Lock/flag/note flows: `LockCommand`, `UnlockCommand`, `FlagCommand`, `UnflagCommand`, `CheckpointAddNoteCommand`.
