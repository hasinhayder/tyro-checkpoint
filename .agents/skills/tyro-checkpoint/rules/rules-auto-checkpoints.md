# Auto-Checkpoint Rules

## Design

- Auto-checkpoints are implemented with `Illuminate\Console\Events\CommandStarting`.
- The listener is `src/Listeners/CreateCheckpointBeforeRiskyCommand.php`.
- The service provider registers the listener in console mode.
- Prefer listener/event wiring for automatic pre-command checkpoints.

## Recursion And Scope

- Always skip `tyro-checkpoint:*` commands in the listener to avoid recursion.
- Keep a static in-process guard so nested Artisan calls do not create multiple checkpoints in one command run.
- Only run when `config('tyro-checkpoint.auto_checkpoint.enabled')` is truthy.
- Watched commands live in `config/tyro-checkpoint.php` under `auto_checkpoint.commands`.

## Behavior

- Auto-created checkpoint names use the configured prefix plus timestamp plus sanitized command name.
- The note should identify the Artisan command that triggered the checkpoint.
- Respect `auto_checkpoint.encrypt`.
- Respect `auto_checkpoint.stop_on_failure`: throw on failure when true, only report failure when false.
- Keep output short because it appears before the user's requested Artisan command.
