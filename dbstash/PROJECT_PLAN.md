# dbstash — Project Plan

> **Author:** Raju <rajuhosseng@gmail.com>
> **Status:** Pre-development
> **Last Updated:** 2026-03-26

---

## 1. Vision

A universal Python CLI tool that brings Git-like database snapshots to local development. One tool, every Python framework, zero config.

**Tagline:** `git stash` for your database.

---

## 2. Architecture

### 2.1 High-Level Data Flow

```
User CLI Command
       │
       ▼
   cli.py (typer)          ← Parse args, validate input, display output
       │
       ▼
   core.py (StashService)  ← Business logic: create/restore/list/delete/lock
       │
       ├──► models.py      ← Stash dataclass (id, name, path, size, etc.)
       ├──► diff.py        ← ATTACH-based schema/data comparison
       ├──► detect.py      ← Framework auto-detection (Django/Flask/FastAPI)
       ├──► hooks.py       ← Git hook install/uninstall
       ├──► config.py      ← .dbstash.toml reader
       ├──► encryption.py  ← Fernet encrypt/decrypt
       └──► exceptions.py  ← DbstashException hierarchy
```

### 2.2 Storage Layout

```
.dbstash/                      ← Created in project root
├── stashes.json               ← Metadata (name, timestamp, size, locked, note, encrypted)
├── stashes.json.bak           ← Auto-backup of metadata
├── my-checkpoint.sqlite       ← Full database snapshot
├── before-migration.sqlite    ← Another snapshot
└── branch__main.sqlite        ← Auto-saved branch state (dbstash auto)
```

### 2.3 Key Design Decisions

| # | Decision | Choice | Why |
|---|---|---|---|
| 1 | Metadata storage | JSON file outside DB | Metadata survives DB restore. Proven by tyro-checkpoint |
| 2 | Snapshot strategy | Full file copy | Simple, fast for dev DBs (<100MB). No incremental complexity |
| 3 | WAL handling | `Connection.backup()` when WAL detected, `shutil.copy2()` otherwise | WAL-safe without over-engineering |
| 4 | Package layout | `src/` layout | Prevents accidental imports from project root |
| 5 | CLI framework | typer | Auto-completion, type hints, used by FastAPI ecosystem |
| 6 | Framework detection | Optional, fallback to `--db` or `.dbstash.toml` | Never fail because detection broke |
| 7 | Diff engine | SQLite ATTACH + SQL queries | Zero external deps, works with any SQLite file |
| 8 | Git integration | post-checkout hook | Auto-save/restore per branch name |
| 9 | Stash directory | `.dbstash/` in project root | Matches `.git/` convention, easy to gitignore |
| 10 | Encryption | Fernet (AES-128-CBC + HMAC) via `cryptography` | Simple API, authenticated encryption, industry standard |

---

## 3. Feature Breakdown

### 3.1 Core Features (v0.1.0)

| Feature | Command | Description | Priority |
|---|---|---|---|
| Create stash | `dbstash save [name]` | Snapshot current DB to `.dbstash/` | P0 |
| Restore stash | `dbstash restore <name>` | Replace current DB with stash | P0 |
| List stashes | `dbstash list` | Show all stashes with metadata | P0 |
| Delete stash | `dbstash delete <name>` | Remove a stash | P0 |
| Init project | `dbstash init` | Create `.dbstash/` dir, detect DB | P0 |
| Framework detect | Automatic | Find SQLite path from Django/Flask/FastAPI config | P1 |

### 3.2 Management Features (v0.2.0)

| Feature | Command | Description | Priority |
|---|---|---|---|
| Lock stash | `dbstash lock <name>` | Prevent accidental deletion | P1 |
| Unlock stash | `dbstash unlock <name>` | Allow deletion of locked stash | P1 |
| Add note | `dbstash note <name> "text"` | Attach description to stash | P1 |
| Diff stashes | `dbstash diff <a> [b]` | Compare two stashes or stash vs current DB | P1 |
| Status | `dbstash status` | Show drift between current DB and a stash | P1 |

### 3.3 Git Integration (v0.3.0)

| Feature | Command | Description | Priority |
|---|---|---|---|
| Auto-save | `dbstash auto --on` | Install git post-checkout hook | P1 |
| Auto-disable | `dbstash auto --off` | Remove git hook | P1 |
| Branch list | `dbstash branches` | Show stashes per git branch | P2 |

### 3.4 Advanced Features (v0.4.0+)

| Feature | Command | Description | Priority |
|---|---|---|---|
| Encrypt stash | `dbstash save --encrypt` | AES-encrypt the snapshot | P2 |
| Schema-only | `dbstash save --schema-only` | Structure without data | P2 |
| Export/share | `dbstash export <name>` | Portable `.dbstash` archive | P2 |
| Import | `dbstash import <file>` | Load shared stash | P2 |
| pytest plugin | `@pytest.fixture` | Restore stash before tests | P2 |
| Flush | `dbstash flush` | Delete all unlocked stashes | P2 |
| Version info | `dbstash version` | Show version + system info | P2 |

---

## 4. Milestones

### Milestone 1: Core Engine (v0.1.0)
- [ ] Project scaffolding (pyproject.toml, src layout, tests/)
- [ ] `StashService` class with create/restore/list/delete
- [ ] `Stash` dataclass model
- [ ] JSON metadata persistence with atomic writes
- [ ] WAL-safe backup via `Connection.backup()`
- [ ] Path validation and security checks
- [ ] Framework auto-detection (Django, Flask, FastAPI)
- [ ] CLI commands: `init`, `save`, `restore`, `list`, `delete`
- [ ] Tests: 90%+ coverage on core.py
- [ ] README with install + quickstart

### Milestone 2: Management & Diff (v0.2.0)
- [ ] Lock/unlock functionality
- [ ] Notes on stashes
- [ ] `diff` command via ATTACH DATABASE
- [ ] `status` command (drift detection)
- [ ] Tests for all new features

### Milestone 3: Git Integration (v0.3.0)
- [ ] Git hook installer/uninstaller
- [ ] Auto-save/restore on branch switch
- [ ] Branch-aware stash listing
- [ ] Tests with mock git operations

### Milestone 4: Advanced + Polish (v0.4.0)
- [ ] Encryption (Fernet)
- [ ] Schema-only snapshots
- [ ] Export/import for team sharing
- [ ] pytest plugin
- [ ] MkDocs site deployed to GitHub Pages
- [ ] PyPI release

### Milestone 5: v1.0.0
- [ ] Copy-on-write / reflink support
- [ ] Django management command adapter
- [ ] Flask CLI adapter
- [ ] Full documentation site
- [ ] CI/CD pipelines (test, lint, publish, docs)
- [ ] Stability + community feedback incorporated

---

## 5. Non-Goals (Explicit Scope Limits)

1. **No PostgreSQL/MySQL support.** SQLite only. This is deliberate.
2. **No production use.** Development tool only.
3. **No incremental/delta snapshots.** Full copies keep it simple.
4. **No GUI.** CLI-first. Always.
5. **No cloud storage.** Local filesystem only (export/import for sharing).
6. **No ORM integration.** Operates at the file level, not model level.

---

## 6. Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| WAL mode corruption on naive copy | High | High | Use `Connection.backup()` when WAL detected |
| Framework config parsing breaks on new versions | Medium | Low | Detection is optional; fallback to `--db` or `.dbstash.toml` |
| Large DB snapshots fill disk | Low | Medium | Warn on stash size; `flush` command for cleanup |
| PyPI name `dbstash` gets taken before release | Low | High | Register name early with placeholder package |
| `cryptography` dep adds install complexity | Low | Low | Make encryption optional (extras_require) |

---

## 7. Competitive Advantage Summary

| What tyro-checkpoint does | What dbstash adds |
|---|---|
| create/restore/list/delete | Same |
| lock/unlock/notes/encrypt | Same |
| Laravel only | **Django/Flask/FastAPI/any** |
| PHP only | **Python (universal CLI)** |
| No diffing | **Schema + data diff** |
| No git integration | **Auto-save per branch** |
| No test integration | **pytest plugin** |
| No drift detection | **Status command** |
| No team sharing | **Export/import** |
| No schema-only mode | **Schema-only snapshots** |
