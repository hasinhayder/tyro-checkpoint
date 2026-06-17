# Tyro Checkpoint

**Database checkpoints for Laravel local development**

Tyro Checkpoint is a simple Laravel package that provides Git-like checkpoint functionality for your database during local development. Supports SQLite, MySQL, and PostgreSQL. Create snapshots of your database state and restore them instantly when needed.

## Features

-  Create full database snapshots with a single command
-  List all available checkpoints with metadata
-  Restore any checkpoint to reset your database state
-  Delete old checkpoints to save disk space
-  **Lock checkpoints** to prevent accidental deletion
-  **Add notes** to checkpoints for better organization
-  **Encryption support** to secure your database snapshots
-  **Auto-checkpoints** before risky Artisan commands like migrations and seeders
-  Supports SQLite, MySQL, and PostgreSQL
-  Simple and production-safe
-  No configuration required

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, 12.x and 13.x
- SQLite, MySQL 8+, or PostgreSQL 12+
- **MySQL:** `mysqldump` and `mysql` CLI tools (install `mysql-client`)
- **PostgreSQL:** `pg_dump` and `psql` CLI tools (install `postgresql-client`)

## Installation

Install the package via Composer:

```bash
composer require hasinhayder/tyro-checkpoint --dev
```

The package will automatically register itself via Laravel's package discovery.

Run the installation command to setup everything:

```bash
php artisan tyro-checkpoint:install
```

This will:
- Check your database configuration
- Validate required CLI binaries for MySQL/PostgreSQL
- Create the checkpoint storage directory
- Create the checkpoints metadata file (checkpoints.json)
- Optionally create an initial checkpoint

That's it! You're ready to create checkpoints.

**Note**: No database migrations are needed as checkpoint metadata is stored in a JSON file.

### Optional: Encryption

If you want to secure your checkpoints, you can generate an encryption key:

```bash
php artisan tyro-checkpoint:generate-key
```

This will add a secure `TYRO_CHECKPOINT_ENCRYPTION_KEY` to your `.env` file. If a key already exists, the command will warn you before replacing it.

### Optional: Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan tyro-checkpoint:publish-config
```

This creates `config/tyro-checkpoint.php` where you can customize the storage path.

## Usage

### Auto-Checkpoints Before Risky Commands

Enable auto-checkpoints to create a safety snapshot before risky Artisan commands run:

```env
TYRO_CHECKPOINT_AUTO_ENABLED=true
```

By default, Tyro Checkpoint watches these commands:

```text
migrate
migrate:fresh
migrate:refresh
migrate:reset
migrate:rollback
db:seed
db:wipe
```

When enabled, running a risky command creates a checkpoint automatically:

```bash
php artisan migrate:fresh --seed
```

Example output:

```text
✓ Auto checkpoint created before migrate:fresh: auto_2026_06_17_153000_migrate_fresh
```

### Create a Checkpoint

Create a checkpoint with an auto-generated name:

```bash
php artisan tyro-checkpoint:create
```

Create a checkpoint with a custom name:

```bash
php artisan tyro-checkpoint:create my_feature_before_changes
```

Create an **encrypted** checkpoint:

```bash
php artisan tyro-checkpoint:create secure_snapshot --encrypt
```

Create a checkpoint without interactive note prompts, useful for cron jobs and scripts:

```bash
php artisan tyro-checkpoint:create nightly_backup --silent
```

Example output:
```
Creating checkpoint...
✓ Checkpoint created successfully!

  ID:      1
  Name:    my_feature_before_changes
  Size:    2.45 MB
  Created: 2026-02-01 10:30:15
```

### List All Checkpoints

View all available checkpoints:

```bash
php artisan tyro-checkpoint:list
```

Example output:
```
Found 3 checkpoint(s):

+----+---------------------------+---------+---------------------+--------+-----------+-------+
| ID | Name                      | Size    | Created At          | Locked | Encrypted | Note  |
+----+---------------------------+---------+---------------------+--------+-----------+-------+
| 3  | before_user_migration     | 2.48 MB | 2026-02-01 14:20:00 | No     | No        |       |
| 2  | after_seeding             | 2.45 MB | 2026-02-01 12:15:30 | Yes    | Yes       | Clean |
| 1  | clean_database            | 1.98 MB | 2026-02-01 10:00:00 | No     | No        |       |
+----+---------------------------+---------+---------------------+--------+-----------+-------+
```

### View Checkpoint Details

Inspect a checkpoint by ID or name:

```bash
php artisan tyro-checkpoint:details 1
php artisan tyro-checkpoint:details clean_database
```

You can also use `tyro-checkpoint:list` with an ID or name as a shortcut:

```bash
php artisan tyro-checkpoint:list 1
php artisan tyro-checkpoint:list clean_database
```

If no identifier is provided, you can choose from the checkpoint list:

```bash
php artisan tyro-checkpoint:details
```

### Restore a Checkpoint

Restore a checkpoint by ID:

```bash
php artisan tyro-checkpoint:restore 1
```

Or restore by name:

```bash
php artisan tyro-checkpoint:restore clean_database
```

You'll be asked to confirm before the restore happens. If no identifier is provided, you can choose from a list:

```
Available checkpoints:

+----+---------------------------+---------+---------------------+-----------+-------+
| ID | Name                      | Size    | Created At          | Encrypted | Note  |
+----+---------------------------+---------+---------------------+-----------+-------+
| 2  | after_seeding             | 2.45 MB | 2026-02-01 12:15:30 | Yes       | Clean |
| 1  | clean_database            | 1.98 MB | 2026-02-01 10:00:00 | No        |       |
+----+---------------------------+---------+---------------------+-----------+-------+

Select a checkpoint to restore (enter 0 to quit):
  [0] Quit
  [1] #2 - after_seeding (encrypted) (Note: Clean)
  [2] #1 - clean_database
 > 
```

**Important**: Checkpoints are NOT deleted after restoration. You can restore the same checkpoint multiple times, allowing you to experiment with different approaches and always return to the same state.

### Add a Note to a Checkpoint

Add a descriptive note to help you remember what a checkpoint represents:

```bash
php artisan tyro-checkpoint:add-note 1
```

You'll be prompted to enter your note:
```
Enter note for checkpoint #1 (or press Enter to remove existing note):
> Clean state with seeded users
```

The note will be displayed when you list checkpoints.

### Lock/Unlock a Checkpoint

Lock a checkpoint to prevent accidental deletion:

```bash
php artisan tyro-checkpoint:lock 1
```

Unlock a checkpoint to allow deletion:

```bash
php artisan tyro-checkpoint:unlock 1
```

Locked checkpoints cannot be deleted individually or via the flush command. This is useful for preserving important baseline states.

### Flag/Unflag a Checkpoint

Flag a checkpoint to mark it for attention:

```bash
php artisan tyro-checkpoint:flag 1
```

Unflag a checkpoint when it no longer needs attention:

```bash
php artisan tyro-checkpoint:unflag 1
```

If no identifier is provided, both commands ask for the checkpoint ID or name. Flagged checkpoints show a 🚩 marker in checkpoint tables.

### Delete a Checkpoint

Delete a checkpoint by ID:

```bash
php artisan tyro-checkpoint:delete 1
```

Or delete by name:

```bash
php artisan tyro-checkpoint:delete clean_database
```

You'll be asked to confirm before deletion. Note that locked checkpoints cannot be deleted:

```
Checkpoint to delete:
  ID:      2
  Name:    after_seeding
  Size:    2.45 MB
  Created: 2026-02-01 12:15:30
  Note:    Clean state

Are you sure you want to delete this checkpoint? (yes/no) [no]:
```

### Delete All Checkpoints

Delete all **unlocked** checkpoints at once with the flush command:

```bash
php artisan tyro-checkpoint:flush
```

Locked checkpoints are preserved. You'll see a list of checkpoints to be deleted:

```
⚠ WARNING: This will delete ALL unlocked checkpoints permanently!

Checkpoints to be deleted:

+----+---------------------------+---------+---------------------+--------+
| ID | Name                      | Size    | Created At          | Locked |
+----+---------------------------+---------+---------------------+--------+
| 3  | before_user_migration     | 2.48 MB | 2026-02-01 14:20:00 | No     |
| 1  | clean_database            | 1.98 MB | 2026-02-01 10:00:00 | No     |
+----+---------------------------+---------+---------------------+--------+

Total: 2 checkpoint(s) (1 locked checkpoint will be preserved)

Are you sure you want to delete ALL unlocked checkpoints? (yes/no) [no]:
```

Skip the confirmation prompt with the `--force` flag:

```bash
php artisan tyro-checkpoint:flush --force
```

## How It Works

1. **Checkpoints are full snapshots**: Each checkpoint is a complete copy of your database (SQLite: direct file copy; MySQL/PostgreSQL: SQL dump)
2. **Stored locally**: Checkpoint files are stored in `storage/tyro-checkpoints/`
3. **Metadata tracking**: Checkpoint metadata (ID, name, path, size, created_at, locked, note, driver) is stored in `storage/tyro-checkpoints/checkpoints.json` - **outside the database** to prevent loss when restoring
4. **Restore process**: Restoring a checkpoint replaces your current database with the selected checkpoint (drop & import for MySQL/PostgreSQL; file replacement for SQLite)
5. **Persistent checkpoints**: Checkpoints are NOT automatically deleted after restoration. You can restore the same checkpoint multiple times and must manually delete checkpoints when no longer needed.
6. **Safe restoration**: Because metadata is stored outside the database, you never lose track of any checkpoint, even when restoring to an earlier state
7. **Lock protection**: Locked checkpoints are protected from deletion to preserve important baseline states
8. **Automatic Decryption**: If a checkpoint is encrypted, the restore command will automatically decrypt it using your `TYRO_CHECKPOINT_ENCRYPTION_KEY` before replacing your database.

## Common Use Cases

### Protect Important Baselines with Lock

```bash
# Create and lock a clean baseline
php artisan tyro-checkpoint:create clean_baseline
php artisan tyro-checkpoint:lock clean_baseline
php artisan tyro-checkpoint:add-note clean_baseline
# Enter: "Fresh install with migrations and seeders"

# Now you can safely flush all other checkpoints
php artisan tyro-checkpoint:flush
# Your locked baseline is preserved!

# Restore to baseline anytime
php artisan tyro-checkpoint:restore clean_baseline
```

### Before Running Migrations

```bash
php artisan tyro-checkpoint:create before_migration
php artisan migrate
# If something goes wrong:
php artisan tyro-checkpoint:restore before_migration
# The checkpoint is still available for future restores
```

### Testing with Fresh Data

```bash
php artisan tyro-checkpoint:create clean_state
php artisan db:seed
# Test your application
php artisan tyro-checkpoint:restore clean_state
# Test again with fresh data - checkpoint is preserved
php artisan tyro-checkpoint:restore clean_state
# Can restore as many times as needed
```

### Experimenting with Database Changes

```bash
php artisan tyro-checkpoint:create before_experiment
# Make manual database changes
# Test your changes
php artisan tyro-checkpoint:restore before_experiment
# Try a different approach
php artisan tyro-checkpoint:restore before_experiment
# Try yet another approach - same checkpoint, multiple restores
```

## Additional Commands

### Check Version and Status

Display package version and system information:

```bash
php artisan tyro-checkpoint:version
```

This shows:
- Package version and history
- Laravel and PHP versions
- Documentation and GitHub links

### Installation Command

Re-run the installation setup:

```bash
php artisan tyro-checkpoint:install
```

Useful when:
- Setting up the package on a new environment
- Verifying your SQLite configuration
- Creating the checkpoint storage directory

### Publish Configuration

Publish the configuration file:

```bash
php artisan tyro-checkpoint:publish-config
```

This creates `config/tyro-checkpoint.php` where you can customize:
- Checkpoint storage path

## Storage Location

Checkpoint files and metadata are stored at:
```
storage/tyro-checkpoints/
├── checkpoints.json          # Metadata for all checkpoints
├── checkpoint_name_1.sqlite  # Database snapshot 1
├── checkpoint_name_2.sqlite  # Database snapshot 2
└── ...
```

- **checkpoints.json**: Contains metadata (ID, name, path, size, created_at, locked, note) for all checkpoints
- **{checkpoint_name}.sqlite**: The actual database snapshot files

**Important**: The metadata is stored in a JSON file (not in the database) so that restoring a checkpoint doesn't cause you to lose track of other checkpoints.

## Configuration

Publish the configuration file to customize settings:

```bash
php artisan tyro-checkpoint:publish-config
```

The published configuration file (`config/tyro-checkpoint.php`) allows you to customize:

```php
return [
    // Custom storage path for checkpoints
    'storage_path' => storage_path('tyro-checkpoints'),

    // Encryption key (automatically set via artisan tyro-checkpoint:generate-key)
    'encryption_key' => env('TYRO_CHECKPOINT_ENCRYPTION_KEY'),

    // Auto checkpoints before risky Artisan commands
    'auto_checkpoint' => [
        'enabled' => env('TYRO_CHECKPOINT_AUTO_ENABLED', false),
        'commands' => [
            'migrate',
            'migrate:fresh',
            'migrate:refresh',
            'migrate:reset',
            'migrate:rollback',
            'db:seed',
            'db:wipe',
        ],
        'name_prefix' => env('TYRO_CHECKPOINT_AUTO_NAME_PREFIX', 'auto'),
        'encrypt' => env('TYRO_CHECKPOINT_AUTO_ENCRYPT', false),
        'stop_on_failure' => env('TYRO_CHECKPOINT_AUTO_STOP_ON_FAILURE', true),
    ],
];
```

## Important Notes

- **Local development only**: This package should only be used in local development environments. Install it as a dev dependency with `--dev`.
- **Not for production**: Never use this package in production environments.
- **In-memory databases not supported**: SQLite `:memory:` databases cannot be checkpointed.
- **CLI binaries required**: MySQL and PostgreSQL drivers require native CLI tools (`mysqldump`/`mysql`, `pg_dump`/`psql`).
- **Metadata stored outside database**: Checkpoint metadata is stored in a JSON file, not in the database itself. This ensures you never lose track of checkpoints when restoring.
- **Checkpoints persist after restore**: Checkpoints are NOT automatically deleted when restored. This allows you to restore the same checkpoint multiple times to test different approaches.
- **Manual cleanup required**: Use `php artisan tyro-checkpoint:delete` or `php artisan tyro-checkpoint:flush` to remove checkpoints you no longer need.
- **Disk space**: Each checkpoint is a full copy of your database, so they can consume disk space. Delete old checkpoints you no longer need.
- **Locked checkpoints**: Locked checkpoints are protected from deletion. Use this to preserve important baseline states.
- **Encryption**: Encrypting checkpoints secures the database snapshots. Ensure you back up your `TYRO_CHECKPOINT_ENCRYPTION_KEY`, as losing it will make all encrypted checkpoints impossible to restore. Replacing a key will also invalidate older encrypted checkpoints.

## Error Handling

The package includes comprehensive error handling:

- **Unsupported database driver**: Error if your database driver is not SQLite, MySQL, or PostgreSQL
- **Missing CLI binary**: Error if MySQL/PostgreSQL CLI tools are not installed
- **In-memory database**: Error if using `:memory:` SQLite database
- **Missing database file**: Error if the database file doesn't exist
- **Duplicate name**: Error if creating a checkpoint with an existing name
- **Missing checkpoint**: Error if trying to restore/delete a non-existent checkpoint
- **Locked checkpoint**: Error if trying to delete a locked checkpoint
- **File operations**: Error if checkpoint file operations fail


## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Created by [Hasin Hayder](https://hasinhayder.com)

## Support

If you encounter any issues or have questions, please open an issue on GitHub.
