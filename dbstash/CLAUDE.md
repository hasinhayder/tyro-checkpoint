# CLAUDE.md — dbstash Project Rules

> **Author:** Raju <rajuhosseng@gmail.com>
> **No co-authors.** All contributions are attributed to Raju unless explicitly changed by Raju.

## Project Overview

**dbstash** is a universal Python CLI tool that provides Git-like checkpoint/branching functionality for SQLite databases during local development. Works with Django, Flask, FastAPI, or any project using SQLite.

- **Language:** Python 3.9+
- **CLI Framework:** typer
- **Test Framework:** pytest
- **Linter/Formatter:** ruff
- **Type Checker:** mypy (strict mode)
- **Doc Framework:** MkDocs + Material theme
- **License:** MIT

---

## Rules

### Rule 1: Evidence-Based Output Only
Never guess. Every claim needs `file:line` evidence. If unverifiable, write `UNVERIFIED`.

### Rule 2: Write Decisions Immediately
Findings go to files within the same response. Do not defer writes.

### Rule 3: Single Source of Truth

| What | Where |
|---|---|
| Project plan & architecture | `PROJECT_PLAN.md` |
| Active bugs / known issues | `docs/known-issues.md` |
| Security findings | `docs/audit/security-risks.md` |
| Dead code findings | `docs/audit/dead-code.md` |
| Combined priorities | `docs/audit/combined-risk-summary.md` |
| Active task state | `docs/current-task.md` |
| Session history | `docs/session-log.md` |
| Changelog | `CHANGELOG.md` |

### Rule 4: Atomic Execution with Checkpoints
Tasks touching 3+ files: break into phases, confirm each phase before proceeding.

### Rule 5: Verify Before Claiming Fixed
After any change, re-read the file to confirm the edit was applied correctly.

### Rule 6: Active Task Fallback
Multi-step work → write current state to `docs/current-task.md`. Clear on completion.

### Rule 7: Session Log
End sessions by appending to `docs/session-log.md` (max 300 bytes per entry).

---

## Session Protocol

**Start:** Read this `CLAUDE.md` → check `docs/current-task.md` → if non-empty, resume.
**During:** Write decisions to files immediately. Keep `current-task.md` updated.
**End:** Clear `current-task.md` → append `session-log.md` → update `known-issues.md` if needed → update this file if key numbers changed.

---

## Authorship Rules

- **Author:** Raju <rajuhosseng@gmail.com>
- All `pyproject.toml`, `LICENSE`, package metadata, git commits, and documentation must list **only Raju** as the author.
- Do NOT add co-authors, contributors, or AI attribution to any file unless explicitly instructed by Raju.
- `CONTRIBUTING.md` may acknowledge community contributors separately, but authorship/ownership remains with Raju.

---

## Tech Stack (Locked)

| Component | Choice | Reason |
|---|---|---|
| Python version | 3.9+ | Oldest actively supported Python (EOL Oct 2025, but still widely used) |
| CLI framework | `typer` | Modern, auto-completion, used by FastAPI ecosystem |
| SQLite backup | `sqlite3.Connection.backup()` (stdlib) | WAL-safe, hot backup, zero deps |
| File copy | `shutil.copy2()` | Preserves metadata, stdlib |
| Metadata storage | JSON file (`stashes.json`) | Proven pattern from tyro-checkpoint |
| Encryption | `cryptography` (Fernet/AES-256) | Industry standard, well-maintained |
| Diffing | SQLite `ATTACH DATABASE` + SQL queries | Zero external deps |
| Testing | `pytest` + `pytest-cov` | Industry standard |
| Linting/Formatting | `ruff` | Replaces black + flake8 + isort in one tool |
| Type checking | `mypy` (strict) | Catch bugs before runtime |
| Docs | MkDocs + Material for MkDocs | Used by typer, ruff, uv, FastAPI |

---

## Project Structure

```
dbstash/
├── CLAUDE.md                  # This file — AI rules and project memory
├── PROJECT_PLAN.md            # Architecture, milestones, design decisions
├── CHANGELOG.md               # Version history
├── CONTRIBUTING.md             # How to contribute
├── SECURITY.md                 # Vulnerability reporting
├── CODE_OF_CONDUCT.md          # Community standards
├── LICENSE                     # MIT
├── README.md                   # Landing page for PyPI and GitHub
├── pyproject.toml              # Single source of truth for project config
├── mkdocs.yml                  # Docs site config
├── docs/                       # User-facing documentation (MkDocs)
│   ├── index.md
│   ├── installation.md
│   ├── quickstart.md
│   ├── cli-reference.md
│   ├── frameworks/
│   │   ├── django.md
│   │   ├── flask.md
│   │   └── fastapi.md
│   ├── features/
│   │   ├── diff.md
│   │   ├── git-integration.md
│   │   └── encryption.md
│   ├── known-issues.md         # Active bugs
│   ├── current-task.md         # Active task state (cleared on completion)
│   ├── session-log.md          # Session history
│   └── audit/                  # Internal audit findings
│       ├── security-risks.md
│       ├── dead-code.md
│       └── combined-risk-summary.md
├── src/
│   └── dbstash/
│       ├── __init__.py         # Package version and exports
│       ├── cli.py              # typer CLI commands
│       ├── core.py             # StashService (framework-agnostic engine)
│       ├── models.py           # Stash dataclass
│       ├── diff.py             # Schema & data diffing via ATTACH
│       ├── detect.py           # Framework auto-detection
│       ├── hooks.py            # Git hook management
│       ├── config.py           # .dbstash.toml reader
│       ├── encryption.py       # Encryption/decryption
│       ├── exceptions.py       # Custom exceptions
│       └── pytest_plugin.py    # pytest fixture integration
├── tests/
│   ├── conftest.py             # Shared fixtures
│   ├── test_core.py            # StashService tests
│   ├── test_cli.py             # CLI integration tests
│   ├── test_diff.py            # Diff tests
│   ├── test_detect.py          # Framework detection tests
│   ├── test_hooks.py           # Git hook tests
│   ├── test_config.py          # Config tests
│   ├── test_encryption.py      # Encryption tests
│   └── test_models.py          # Model tests
└── .github/
    ├── workflows/
    │   ├── ci.yml              # Test + lint on every PR
    │   ├── publish.yml         # Auto-publish to PyPI on release
    │   └── docs.yml            # Auto-deploy docs to GitHub Pages
    ├── ISSUE_TEMPLATE/
    │   ├── bug.yml
    │   └── feature.yml
    └── PULL_REQUEST_TEMPLATE.md
```

---

## Coding Standards

### Naming Conventions

| What | Convention | Example |
|---|---|---|
| Files | `snake_case.py` | `stash_service.py` |
| Classes | `PascalCase` | `StashService` |
| Functions/methods | `snake_case` | `create_stash()` |
| Constants | `UPPER_SNAKE_CASE` | `MAX_NAME_LENGTH` |
| Private | Leading underscore | `_validate_name()` |
| CLI commands | kebab-case (typer default) | `dbstash create`, `dbstash list` |

### Code Rules

1. **No magic numbers.** Use named constants.
2. **No wildcard imports.** Always explicit: `from x import y`.
3. **No mutable default arguments.** Use `None` + assignment pattern.
4. **Type hints on all public functions.** No `Any` unless truly unavoidable.
5. **Docstrings on all public classes and functions.** Google style.
6. **Max function length:** 50 lines. If longer, refactor.
7. **Max file length:** 500 lines. If longer, split.
8. **No print().** Use `typer.echo()` for CLI output, `logging` for internal.
9. **No bare except.** Always catch specific exceptions.
10. **No TODO comments in merged code.** Use GitHub Issues instead.

### Error Handling

1. All user-facing errors must use `DbstashException` or subclasses.
2. CLI commands catch `DbstashException` and display user-friendly messages via `typer.echo()`.
3. Never expose stack traces to users unless `--verbose` flag is set.
4. Validate at system boundaries (user input, file I/O). Trust internal code.

### File I/O Safety

1. Atomic writes: write to temp file, then `os.rename()` to target.
2. Always validate paths with `pathlib.Path.resolve()` to prevent path traversal.
3. Check file existence before operations; raise clear errors if missing.
4. Use `Connection.backup()` for WAL-mode databases, `shutil.copy2()` otherwise.

### Git Commit Messages

Format:
```
<type>: <short description>

<optional body>
```

Types: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `ci`

Examples:
- `feat: add dbstash diff command`
- `fix: handle WAL mode during snapshot`
- `docs: add Django quickstart guide`
- `test: add StashService restore tests`

---

## Testing Standards

### Coverage Requirements

- **Minimum:** 90% line coverage
- **Core module (`core.py`):** 95%+ coverage
- **CLI module (`cli.py`):** 85%+ coverage (integration tests)

### Test Organization

| Test type | Location | Naming |
|---|---|---|
| Unit tests | `tests/test_<module>.py` | `test_<function>_<scenario>()` |
| Integration tests | `tests/test_cli.py` | `test_<command>_<scenario>()` |
| Fixtures | `tests/conftest.py` | Shared across all tests |

### Test Rules

1. Every public function gets at least one test.
2. Test the happy path AND at least one error path.
3. Use `tmp_path` fixture for all file operations (no writing to real filesystem).
4. No network calls in tests. Mock external I/O.
5. Tests must be independent — no shared state between tests.
6. Use `pytest.raises()` for expected exceptions.
7. Test names describe behavior: `test_create_stash_with_duplicate_name_raises_error`.

### Running Tests

```bash
# Run all tests
pytest

# Run with coverage
pytest --cov=src/dbstash --cov-report=term-missing

# Run specific test file
pytest tests/test_core.py

# Run specific test
pytest tests/test_core.py::test_create_stash_success

# Type check
mypy src/

# Lint and format
ruff check src/ tests/
ruff format src/ tests/
```

---

## Release Versioning

Follow [Semantic Versioning](https://semver.org/):
- **MAJOR** (1.0.0): Breaking changes to CLI or Python API
- **MINOR** (0.x.0): New features, backward compatible
- **PATCH** (0.0.x): Bug fixes

Version is defined in ONE place: `src/dbstash/__init__.py` → `__version__`
Referenced by `pyproject.toml` via `dynamic = ["version"]`.

---

## Key Design Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Metadata outside DB | JSON file (`stashes.json`) | Metadata survives DB restore (proven by tyro-checkpoint) |
| Full snapshots, not incremental | Complete file copy | Simplicity > space efficiency for dev DBs |
| CLI-first, library-second | `typer` CLI as primary interface | Users interact via terminal; programmatic API is bonus |
| src layout | `src/dbstash/` | Prevents accidental imports from project root (Python packaging best practice) |
| Framework detection is optional | Falls back to `--db` flag or `.dbstash.toml` | Never fail because detection didn't work |

---

## What NOT To Do

1. **Do NOT add features not in PROJECT_PLAN.md** without explicit approval from Raju.
2. **Do NOT change the author** in any file. Author is Raju <rajuhosseng@gmail.com>.
3. **Do NOT add emoji** to code, docs, or commits unless Raju requests it.
4. **Do NOT create files outside the project structure** defined above without approval.
5. **Do NOT use `os.system()` or `subprocess`** for file operations. Use stdlib.
6. **Do NOT store secrets** (encryption keys, passwords) in code or config files.
7. **Do NOT add dependencies** not listed in the tech stack without approval.
8. **Do NOT skip tests.** Every feature needs tests before it's considered done.
9. **Do NOT guess at framework configs.** Only detect what we can verify (file existence + parseable format).
10. **Do NOT support databases other than SQLite.** This is a deliberate scope limit.
