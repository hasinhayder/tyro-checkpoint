# Driver And Process Rules

## Driver Support

- Current engines are SQLite, MySQL, and PostgreSQL.
- Driver implementations live in `src/Drivers`.
- Do not reintroduce SQLite-only assumptions in README, config, tests, or service code.
- Keep database identity metadata intact so restore can detect mismatched target databases.

## Process Execution

- External commands run through the `ProcessRunner` abstraction.
- The default implementation is `src/Process/SymfonyProcessRunner.php`.
- Keep process execution testable by injecting/faking `ProcessRunner` rather than shelling out directly in drivers.
- Use configured binary paths from `config('tyro-checkpoint.binaries')` for MySQL/PostgreSQL tools.
- Process timeout comes from `tyro-checkpoint.process.timeout` / `TYRO_CHECKPOINT_PROCESS_TIMEOUT`.

## Credentials And Output

- Avoid leaking database credentials in exception messages, command output, or tests.
- Prefer argument arrays and environment handling over shell string interpolation.
- Keep binary validation in install/setup flows and helper classes rather than scattering checks across commands.

## Tests To Look At

- `tests/Unit/DriverManagerTest.php` for driver resolution.
- `tests/Unit/SymfonyProcessRunnerTest.php` for process abstraction behavior.
- `tests/Unit/FormatsCheckpointTablesTest.php` for user-facing command table behavior.
