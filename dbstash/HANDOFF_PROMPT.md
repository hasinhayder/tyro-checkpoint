# dbstash — New Session Handoff Prompt

> Copy everything below this line and paste it as your first message in a new Claude Code session pointed at the `raju-h/dbstash` repo.

---

## Context

I've created a new repo `raju-h/dbstash` on GitHub (empty, no commits yet). All foundation files are ready locally at `/home/user/tyro-checkpoint/dbstash/`. I need you to:

### Step 1: Initialize and Push Foundation Files

The following files need to be committed and pushed to `main` branch of `raju-h/dbstash`:

1. **`.gitignore`** — Python, IDE, OS, testing, dbstash runtime ignores
2. **`CLAUDE.md`** — AI rules, coding/testing standards, session protocol, authorship rules
3. **`LICENSE`** — MIT, author: Raju
4. **`PROJECT_PLAN.md`** — Architecture, 5 milestones, feature breakdown, risk register
5. **`pyproject.toml`** — Project config with ruff/mypy/pytest/coverage settings
6. **`docs/current-task.md`** — Active task tracker (empty)
7. **`docs/session-log.md`** — Session history
8. **`docs/known-issues.md`** — Bug tracker (empty)
9. **`docs/audit/security-risks.md`** — Security findings (empty)
10. **`docs/audit/dead-code.md`** — Dead code findings (empty)
11. **`docs/audit/combined-risk-summary.md`** — Combined risk summary (empty)

If these files don't exist locally, create them using the content from the CLAUDE.md rules below.

Commit message: `feat: initial project foundation`

### Step 2: Read CLAUDE.md and Follow All Rules

After pushing, read `CLAUDE.md` thoroughly. Key rules:
- **Author:** Raju <rajuhosseng@gmail.com> — NO co-authors ever
- **Rule 1:** Evidence-based output only, never guess
- **Rule 2:** Write decisions to files immediately
- **Rule 3:** Single source of truth (see table in CLAUDE.md)
- **Rule 4:** Atomic execution with checkpoints for 3+ file tasks
- **Rule 5:** Verify before claiming fixed
- **Rule 6:** Track active tasks in `docs/current-task.md`
- **Rule 7:** Session log at end of session

### Step 3: Create Phase 3 Community Docs

Create these files following the standards in CLAUDE.md:

1. **`README.md`** — Landing page for PyPI/GitHub:
   - Project name, tagline (`git stash` for your database), badges
   - Install: `pip install dbstash`
   - Quick example (5 commands: init, save, list, restore, diff)
   - Feature list with framework support (Django/Flask/FastAPI)
   - Link to docs site (placeholder: `https://raju-h.github.io/dbstash/`)
   - License (MIT) and author (Raju)
   - Keep it SHORT like sqlite-utils/poetry READMEs — link to docs for details

2. **`CONTRIBUTING.md`** — How to contribute:
   - Fork + clone + branch workflow
   - Dev setup: `pip install -e ".[dev]"`
   - Run tests: `pytest`, lint: `ruff check`, types: `mypy src/`
   - Commit message format (from CLAUDE.md)
   - PR process

3. **`CHANGELOG.md`** — Start with:
   ```
   # Changelog
   All notable changes will be documented here. Format: [Keep a Changelog](https://keepachangelog.com/).
   ## [Unreleased]
   ### Added
   - Project foundation (CLAUDE.md, pyproject.toml, PROJECT_PLAN.md)
   ```

4. **`SECURITY.md`** — Vulnerability reporting instructions

5. **`CODE_OF_CONDUCT.md`** — Contributor Covenant v2.1

### Step 4: Create Project Scaffolding (Milestone 1 Start)

Create the `src/` layout:

```
src/dbstash/__init__.py      →  __version__ = "0.1.0"
src/dbstash/exceptions.py    →  DbstashException base class
src/dbstash/models.py        →  Stash dataclass
src/dbstash/cli.py           →  typer app skeleton (init, save, restore, list, delete)
src/dbstash/core.py          →  StashService class skeleton
src/dbstash/detect.py        →  Framework detection skeleton
src/dbstash/config.py        →  Config reader skeleton
src/dbstash/diff.py          →  Diff skeleton
src/dbstash/hooks.py         →  Git hooks skeleton
src/dbstash/encryption.py    →  Encryption skeleton
src/dbstash/pytest_plugin.py →  pytest plugin skeleton
tests/conftest.py            →  Shared fixtures
tests/test_core.py           →  Empty test file
```

Each skeleton should have the class/function signatures with docstrings but minimal implementation (just `pass` or `raise NotImplementedError`). This establishes the architecture before filling in logic.

### Key Technical Decisions (Locked)

- **CLI:** typer
- **Backup:** `sqlite3.Connection.backup()` for WAL, `shutil.copy2()` otherwise
- **Metadata:** JSON file (`stashes.json`) in `.dbstash/` directory
- **Encryption:** `cryptography` library (Fernet)
- **Diff:** SQLite ATTACH DATABASE + SQL queries
- **Min Python:** 3.9+
- **Layout:** `src/dbstash/`

### What This Project Is

**dbstash** is a universal Python CLI tool for Git-like SQLite database snapshots during local development. It works with Django, Flask, FastAPI, or any project using SQLite.

Key differentiators vs tyro-checkpoint (Laravel):
- Universal Python CLI (not framework-locked)
- Schema + data diffing between stashes
- Git branch auto-save/restore via hooks
- pytest integration
- Drift detection
- Framework auto-detection (Django/Flask/FastAPI)

### What NOT To Do

- Do NOT change author from Raju <rajuhosseng@gmail.com>
- Do NOT add features not in PROJECT_PLAN.md
- Do NOT add dependencies not in pyproject.toml
- Do NOT support databases other than SQLite
- Do NOT add emoji unless asked
- Do NOT skip tests

---
