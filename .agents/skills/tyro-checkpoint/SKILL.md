---
name: tyro-checkpoint
description: "Use this skill when working in the hasinhayder/tyro-checkpoint Laravel package on checkpoint commands, services, drivers, auto-checkpoints, encryption, release/version metadata, README/CHANGELOG updates, or package tests. It provides repo-specific rules for adding features safely and keeping command UX, docs, and version history aligned."
---

# Tyro Checkpoint

## Start Here

Use this skill for feature work, bug fixes, docs, and release follow-through in the Tyro Checkpoint package.

Before editing, inspect the live files related to the request. This package changes quickly; prefer current source over memory.

Read only the rule files that match the task:

- Command UX, prompts, command registration, or non-interactive behavior: read [rules-cli.md](rules/rules-cli.md).
- Checkpoint storage, metadata JSON, encryption, locking, flags, or service behavior: read [rules-architecture.md](rules/rules-architecture.md).
- Flag, unflag, lock, unlock, or existing-checkpoint encryption behavior: read [rules-checkpoint-state.md](rules/rules-checkpoint-state.md).
- Database engine behavior, engine-specific compatibility, or SQLite/MySQL/PostgreSQL feature impact: read [rules-database-engines.md](rules/rules-database-engines.md).
- SQLite/MySQL/PostgreSQL support, binaries, connection config, process execution, or timeouts: read [rules-drivers.md](rules/rules-drivers.md).
- Auto-checkpoints before risky commands or listener/event behavior: read [rules-auto-checkpoints.md](rules/rules-auto-checkpoints.md).
- README, CHANGELOG, version bumps, tags, or release notes: read [rules-release-docs.md](rules/rules-release-docs.md).
- Tests, formatting, or validation commands: read [rules-verification.md](rules/rules-verification.md).

## Default Workflow

1. Identify the owner files before changing behavior. Commands live in `src/Console/Commands`, core orchestration lives in `src/Services/CheckpointService.php`, DB snapshot implementations live in `src/Drivers`, and package bootstrapping lives in `src/TyroCheckpointServiceProvider.php`.
2. Keep package changes additive and backward compatible unless the user explicitly asks for a breaking change.
3. Preserve local-development ergonomics: commands should be clear, safe around destructive actions, and friendly for both interactive use and scripts.
4. Update config, README examples, tests, and release metadata when the user-facing feature surface changes.
5. Prefer focused unit tests and syntax checks over broad app-level commands unless the user asks for runtime integration testing.

## Package Facts

- Package namespace: `HasinHayder\TyroCheckpoint`.
- Composer package: `hasinhayder/tyro-checkpoint`.
- Supported PHP/Laravel range is defined in `composer.json`; do not narrow it casually.
- Checkpoint metadata is stored outside the database in `checkpoints.json` under `config('tyro-checkpoint.storage_path')`.
- Runtime version display is controlled by `src/Console/Commands/VersionCommand.php`, not `composer.json`.
- The command prefix is `tyro-checkpoint:*`.

## Guardrails

- Do not move interactive prompt logic into `CheckpointService` without a strong reason; keep CLI interaction in command classes.
- Do not assume SQLite-only behavior. Current support includes SQLite, MySQL, and PostgreSQL via driver abstraction; new features should be checked against all three engines.
- Do not create checkpoints from inside `tyro-checkpoint:*` listener flows; avoid recursion.
- Do not invent release dates. Use `CHANGELOG.md`, Git tags, or the user-provided date.
- Do not run destructive database commands in the user's app unless explicitly requested.
