# dbstash - Feasibility & Feature Analysis

> **Verdict: BUILD IT.** The gap is real, demand is validated, and no Python competitor exists.

## 1. What is dbstash?

A universal Python CLI tool that provides Git-like checkpoint/branching functionality for SQLite databases during local development. Works with Django, Flask, FastAPI, or any project using SQLite.

**Name availability:** `dbstash` is free on PyPI, npm, GitHub, and has no commercial conflicts.

---

## 2. Competitive Landscape (Facts)

| Tool | Ecosystem | SQLite? | Stars | Status | Gap vs dbstash |
|---|---|---|---|---|---|
| [spatie/laravel-db-snapshots](https://github.com/spatie/laravel-db-snapshots) | Laravel/PHP | Yes | 1,200 | Active (Mar 2026) | No locking, notes, encryption, diff, or git integration. 2M+ Packagist downloads |
| [Stellar](https://github.com/fastmonkeys/stellar) | Python | No | **3,900** | **Dead** (last PyPI 2018) | PostgreSQL/MySQL only. Most popular tool in space but abandoned |
| [django-dbbackup](https://github.com/jazzband/django-dbbackup) | Django | Yes | 1,096 | Active (Feb 2026) | Production backup tool, uses SQL dumps (slow). No checkpoint UX. 32K weekly PyPI downloads |
| [flask-alchemydumps](https://github.com/cuducos/alchemydumps) | Flask | Via ORM | 116 | **Archived** | Dead, ORM-level serialization |
| [sqlite-backup](https://pypi.org/project/sqlite-backup/) | Python CLI | Yes | Low | Low activity | Raw copy utility. No metadata, naming, locking, CLI UX |
| [dksnap](https://github.com/kelda/dksnap) | Docker | Via containers | 152 | **Dead** (2020) | Docker-based, heavyweight approach |
| [LiteTree](https://github.com/aergoio/litetree) | C (SQLite fork) | Yes | 1,250 | Unmaintained | Requires modified SQLite engine |
| **dbstash** | **Python (universal)** | **Yes** | — | **Doesn't exist yet** | **First mover in an empty space** |

### Key insight
Stellar proved demand with 3,900 stars and then died. No one picked up where it left off. The Python ecosystem has **zero tools** offering named SQLite snapshots with diff, git integration, and a polished CLI.

---

## 3. Real-World Pain Points (Evidence-Based)

### Pain Point 1: Database Breaks on Git Branch Switch
- **Problem:** Switching branches with different migrations breaks the DB. Developers drop tables or maintain separate DBs per branch.
- **Evidence:** [DjangoCon US 2023 talk](https://2023.djangocon.us/talks/strategies-for-handling-conflicts-and-rollbacks-in-django-database-migrations/) on migration conflicts; [django-linear-migrations](https://github.com/adamchainz/django-linear-migrations) package exists solely for this; [git-migration-hook](https://github.com/richardbrinkman/git-migration-hook) project built to auto-run migrations on branch switch; [HN thread (Jul 2025)](https://news.ycombinator.com/item?id=44732277) cites "merge conflicts across branches" and "broken audit trails."
- **Solvable by dbstash?** **YES** - auto-save/restore per branch via git hooks.

### Pain Point 2: Slow Database Resets
- **Problem:** Resetting the DB during dev takes 20-90+ seconds.
- **Evidence:** [Supabase Discussion #14598](https://github.com/orgs/supabase/discussions/14598) — dev reports 21s resets, another reports 90s; [Redgate SQL Clone](https://www.red-gate.com/hub/product-learning/sql-clone/reset-development-database-seconds-using-sql-clone) — "developers end up in an agonizingly long iteration cycle."
- **Solvable by dbstash?** **YES** - file copy is milliseconds for typical dev DBs.

### Pain Point 3: Slow Test Database Setup
- **Problem:** Test suites re-create the DB from scratch each run.
- **Evidence:** [Laravel issue #19824](https://github.com/laravel/framework/issues/19824) — 37 tests taking 16s; one project went from 350s to 20s with `--reuse-db` optimization; [awesome-pytest-speedup](https://github.com/zupo/awesome-pytest-speedup) lists DB setup as top bottleneck; [Adapt package](https://github.com/code-distortion/adapt) was built specifically to snapshot test DBs.
- **Solvable by dbstash?** **YES** - pre-built snapshot + file copy is orders of magnitude faster than migrations.

### Pain Point 4: Fixture/Seed Maintenance Burden
- **Problem:** Seed/fixture files must stay synchronized with every schema change. They break silently.
- **Evidence:** [Django docs](https://docs.djangoproject.com/en/5.1/topics/db/fixtures/) warn fixtures must contain all required fields; [Real Python](https://realpython.com/django-pytest-fixtures/) states "Keeping Django fixtures updated can become a burden"; [Django ticket #14610](https://code.djangoproject.com/ticket/14610) open since 2010; Neon blog: "Maintenance overhead is significant because seed files must stay synchronized with schema changes."
- **Solvable by dbstash?** **YES** - snapshot a known-good DB state. Always schema-consistent by definition.

### Pain Point 5: Data Loss When Reverting Migrations
- **Problem:** Running migrations backward doesn't restore data from dropped columns/tables.
- **Evidence:** [Django docs](https://docs.djangoproject.com/en/6.0/topics/migrations/): "unapplying that migration will re-create the column, but it won't bring back the data"; [django-deprecate-fields](https://github.com/3YOURMIND/django-deprecate-fields) was created to make field removal safer.
- **Solvable by dbstash?** **YES** - file snapshot preserves both schema AND data.

### Pain Point 6: No Local Database Branching
- **Problem:** Cloud services (PlanetScale, Neon) offer DB branching, but local dev has no equivalent.
- **Evidence:** PlanetScale branching is a [flagship feature](https://planetscale.com/features/branching); [Neon](https://neon.com/docs/introduction/branching) (21.3K stars) creates copy-on-write branches in ~1s; [Show HN: Pgbranch](https://news.ycombinator.com/item?id=46162321) (Dec 2025) built because "sometimes your migration will corrupt data"; [LiteTree](https://github.com/aergoio/litetree) (1,250 stars) tried SQLite-level branching.
- **Solvable by dbstash?** **YES** - strongest use case. SQLite is a file; branching = copying a file.

---

## 4. Feature Plan: What dbstash Adds Beyond tyro-checkpoint

### Baseline (from tyro-checkpoint):
`create` | `restore` | `list` | `delete` | `lock/unlock` | `notes` | `encrypt`

### New features with evidence:

| Feature | Command | Real Problem It Solves | Evidence of Demand |
|---|---|---|---|
| **Schema & data diff** | `dbstash diff <a> <b>` | "What changed since my last checkpoint?" | [Atlas](https://github.com/ariga/atlas) (8.2K stars), [sqldiff](https://sqlite.org/sqldiff.html) (official SQLite tool), PlanetScale/Neon both built diffing as core |
| **Git branch auto-save** | `dbstash auto` | DB breaks on branch switch | [DjangoCon 2023 talk](https://2023.djangocon.us/talks/strategies-for-handling-conflicts-and-rollbacks-in-django-database-migrations/), [git-migration-hook](https://github.com/richardbrinkman/git-migration-hook), [Supabase #14598](https://github.com/orgs/supabase/discussions/14598) (21s-90s resets) |
| **Team sharing** | `dbstash push/pull` | Onboarding devs need same DB state | [Redgate SQL Clone](https://www.red-gate.com/products/sql-clone/) (commercial product for this), [shared DB problems](https://www.red-gate.com/simple-talk/databases/sql-server/tools-sql-server/the-unnecessary-evil-of-the-shared-development-database/) |
| **pytest integration** | `@pytest.fixture` | Test DB setup is slow | [awesome-pytest-speedup](https://github.com/zupo/awesome-pytest-speedup), pytest-django `--reuse-db`, [Adapt](https://github.com/code-distortion/adapt) |
| **Drift detection** | `dbstash status` | "Is my DB still in sync with a known state?" | [Atlas schema diff](https://atlasgo.io/declarative/diff), PlanetScale deploy requests, Liquibase changelog tracking |
| **Hot backup (WAL-safe)** | Automatic | Copying WAL-mode SQLite can corrupt | [SQLite WAL docs](https://www.sqlite.org/wal.html), [Laravel #53040](https://github.com/laravel/framework/issues/53040), Python `Connection.backup()` since 3.7 |
| **Schema-only snapshots** | `dbstash save --schema-only` | Data contains sensitive/personal info | PlanetScale branches are schema-only by default, GDPR concerns |
| **Copy-on-write / reflinks** | Automatic | Large DBs (500MB+) waste disk with full copies | Neon's architecture is built on CoW, APFS/Btrfs/XFS support reflinks natively |
| **Framework auto-detection** | Automatic | Avoid passing `--db` every time | Detect Django `settings.py`, Flask `SQLALCHEMY_DATABASE_URI`, `.env` files |

---

## 5. Technical Implementation

### Core stack:
- **CLI:** `typer` (modern Python CLI framework with auto-completion)
- **Backup:** Python stdlib `sqlite3.Connection.backup()` for WAL safety, `shutil.copy2()` for speed
- **Metadata:** JSON file (same approach as tyro-checkpoint, proven pattern)
- **Encryption:** `cryptography` library (Fernet/AES-256)
- **Diff:** SQLite `ATTACH DATABASE` + SQL set-difference queries (zero external deps)
- **Reflinks:** `os.copy_file_range()` on Linux 4.5+, `fcopyfile` with COPYFILE_CLONE on macOS

### Framework detection:
| Framework | Detection method |
|---|---|
| Django | Parse `settings.py` → `DATABASES['default']['NAME']` |
| Flask/SQLAlchemy | Parse `SQLALCHEMY_DATABASE_URI` from config |
| FastAPI/SQLModel | Same as Flask (uses SQLAlchemy under the hood) |
| Laravel | Parse `.env` → `DB_DATABASE` |
| Rails | Parse `config/database.yml` → `development.database` |
| Generic | Scan for `*.sqlite3` / `*.db` files, or use `.dbstash.toml` config |

### Package structure:
```
dbstash/
├── __init__.py
├── cli.py              # typer CLI commands
├── core.py             # CheckpointService (framework-agnostic)
├── models.py           # Stash dataclass
├── diff.py             # Schema & data diffing via ATTACH
├── detect.py           # Framework auto-detection
├── hooks.py            # Git hook management
├── config.py           # .dbstash.toml reader
├── pytest_plugin.py    # pytest fixture integration
└── integrations/
    ├── django.py       # Django management command adapter
    ├── flask.py        # Flask CLI adapter
    └── fastapi.py      # FastAPI/Typer adapter
```

---

## 6. Release Plan

| Version | Features | Rationale |
|---|---|---|
| **v0.1.0** | create, restore, list, delete, lock, notes, framework detection, CLI | Core value — functional parity with tyro-checkpoint |
| **v0.2.0** | diff, status (drift detection) | First differentiator — no other tool does this |
| **v0.3.0** | git branch auto-save (`dbstash auto`) | Killer feature — solves #1 pain point |
| **v0.4.0** | pytest plugin, hot backup (WAL-safe) | Developer workflow integration |
| **v0.5.0** | schema-only snapshots, encryption | Security & privacy |
| **v1.0.0** | push/pull sharing, reflinks, Django/Flask management commands | Team collaboration + polish |

---

## 7. Why Build vs. Not Build

### FOR:
| Fact | Source |
|---|---|
| No Python competitor exists | Searched PyPI, GitHub, npm — confirmed |
| Stellar proved demand (3.9K stars) and died | [GitHub](https://github.com/fastmonkeys/stellar) |
| Supabase devs wait 21-90s for DB resets | [Discussion #14598](https://github.com/orgs/supabase/discussions/14598) |
| Django migration branch conflicts are a known pain | [DjangoCon 2023](https://2023.djangocon.us/talks/strategies-for-handling-conflicts-and-rollbacks-in-django-database-migrations/), [django-linear-migrations](https://github.com/adamchainz/django-linear-migrations) |
| PlanetScale/Neon validate the "DB branching" concept | 21.3K stars (Neon), major commercial product (PlanetScale) |
| Test DB setup is a documented bottleneck | [awesome-pytest-speedup](https://github.com/zupo/awesome-pytest-speedup), pytest-django `--reuse-db` |
| Core logic is ~500-800 lines | Low risk, buildable in days |
| Python's stdlib has `Connection.backup()` | Better than PHP's file-copy approach |

### AGAINST:
| Fact | Impact |
|---|---|
| SQLite-only limits audience | Mitigated: SQLite is Django's default dev DB; "SQLite Renaissance" is growing |
| `cp db.sqlite3 backup.sqlite3` exists | True, but that's like saying `git` isn't needed because you can `cp -r project/ project-backup/` |
| Maintenance across Django/Flask/FastAPI config changes | Low — detection is a thin layer, can fall back to `.dbstash.toml` |

---

## 8. Conclusion

**dbstash fills a validated, empty gap in the Python ecosystem.** The demand is proven by Stellar's 3.9K stars (dead), Neon's 21.3K stars (branching), and PlanetScale's commercial success. The implementation is low-risk (~800 lines core). The key differentiators (diff, git-branch-auto, pytest integration) solve real, documented pain points that no existing tool addresses.
