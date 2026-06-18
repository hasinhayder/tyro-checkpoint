# Tyro Checkpoint

Database checkpoints for Laravel local development. Snapshot your database and restore it instantly. Supports SQLite, MySQL, and PostgreSQL.

> Local development only. Not for production.

## Requirements

- PHP 8.1+
- Laravel 10.x–13.x
- SQLite, MySQL 8+, or PostgreSQL 12+
- MySQL: `mysqldump`, `mysql`
- PostgreSQL: `pg_dump`, `psql`

## Install

```bash
composer require hasinhayder/tyro-checkpoint --dev
php artisan tyro-checkpoint:install
```

## Commands

| Command | Description |
| --- | --- |
| `tyro-checkpoint:create [name] [--encrypt] [--silent]` | Create a checkpoint |
| `tyro-checkpoint:list [id\|name]` | List checkpoints (or view one) |
| `tyro-checkpoint:details [id\|name]` | Show checkpoint details |
| `tyro-checkpoint:restore [id\|name]` | Restore a checkpoint |
| `tyro-checkpoint:delete [id\|name]` | Delete a checkpoint |
| `tyro-checkpoint:flush [--force]` | Delete all **unlocked** checkpoints |
| `tyro-checkpoint:lock [id\|name]` | Lock (prevent deletion) |
| `tyro-checkpoint:unlock [id\|name]` | Unlock |
| `tyro-checkpoint:flag [id\|name]` | Flag for attention (🚩) |
| `tyro-checkpoint:unflag [id\|name]` | Remove flag |
| `tyro-checkpoint:add-note [id\|name]` | Add a note |
| `tyro-checkpoint:encrypt [id\|name]` | Encrypt an existing checkpoint in place |
| `tyro-checkpoint:generate-key` | Generate encryption key into `.env` |
| `tyro-checkpoint:publish-config` | Publish config file |
| `tyro-checkpoint:install` | Run setup |
| `tyro-checkpoint:version` | Show version and system info |

Restore is non-destructive to checkpoints — you can restore the same one many times.

## Auto-checkpoints

Snapshot automatically before risky commands (migrations, seeders, `db:wipe`).

```env
TYRO_CHECKPOINT_AUTO_ENABLED=true
```

Default watched commands: `migrate`, `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:seed`, `db:wipe`.

## Configuration

Publish with `tyro-checkpoint:publish-config`. Defaults:

```php
return [
    'storage_path' => storage_path('tyro-checkpoints'),
    'encryption_key' => env('TYRO_CHECKPOINT_ENCRYPTION_KEY'),
    'process' => [
        'timeout' => env('TYRO_CHECKPOINT_PROCESS_TIMEOUT', 600),
    ],
    'auto_checkpoint' => [
        'enabled' => env('TYRO_CHECKPOINT_AUTO_ENABLED', false),
        'commands' => ['migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'db:seed', 'db:wipe'],
        'name_prefix' => env('TYRO_CHECKPOINT_AUTO_NAME_PREFIX', 'auto'),
        'encrypt' => env('TYRO_CHECKPOINT_AUTO_ENCRYPT', false),
        'stop_on_failure' => env('TYRO_CHECKPOINT_AUTO_STOP_ON_FAILURE', true),
    ],
];
```

## Storage

```
storage/tyro-checkpoints/
├── checkpoints.json     # Metadata (stored outside the DB)
├── name.sqlite          # Snapshot files
└── ...
```

Metadata lives in `checkpoints.json`, so restoring a snapshot never loses track of other checkpoints.

## Encryption

```bash
php artisan tyro-checkpoint:generate-key
php artisan tyro-checkpoint:create secure --encrypt
```

Encrypted checkpoints auto-decrypt on restore. Back up `TYRO_CHECKPOINT_ENCRYPTION_KEY` — losing it makes encrypted checkpoints unrestorable.

## Notes

- Full database snapshots (file copy for SQLite, SQL dump for MySQL/PostgreSQL).
- Locked checkpoints survive `flush`.
- In-memory SQLite (`:memory:`) is not supported.
- Delete unneeded checkpoints to save disk space.

## License

MIT — © [Hasin Hayder](https://hasinhayder.com)
