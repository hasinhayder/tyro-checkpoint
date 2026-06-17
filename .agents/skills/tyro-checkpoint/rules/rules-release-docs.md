# Release And Docs Rules

## Version Source

- Runtime package version is displayed from `src/Console/Commands/VersionCommand.php`.
- When bumping a release, update both the `$version` value and the version-history comments at the bottom of `VersionCommand.php`.
- Do not treat `composer.json` as the runtime version source.

## Changelog

- Keep `CHANGELOG.md` in Keep a Changelog style with newest entries first.
- Add user-facing features under `Added`, behavior changes under `Changed`, fixes under `Fixed`, and removals under `Removed`.
- Use exact command names and config keys in backticks.
- If backfilling historical entries, use Git tags or existing version metadata for dates instead of guessing.

## SemVer Defaults

- New backward-compatible command features usually warrant a minor version bump.
- Bug fixes and docs-only corrections usually warrant patch-level treatment.
- If the user separates a feature into a new release, immediately move the version string and changelog entry together.

## README And Config

- Update `README.md` when adding or changing public commands, options, config keys, supported databases, install behavior, or safety semantics.
- Update `config/tyro-checkpoint.php` comments when adding config values; include env var names when applicable.
- Keep examples friendly for local development and scripts.

## Release Follow-Through

- Before finalizing release work, check these together:
  - `src/Console/Commands/VersionCommand.php`
  - `CHANGELOG.md`
  - `README.md`
  - `config/tyro-checkpoint.php` when config changed
  - Tests or docs examples for new command names/options
