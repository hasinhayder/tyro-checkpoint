# Tyro Checkpoint

**Database checkpoints for Laravel local development (SQLite only)**

Tyro Checkpoint is a simple Laravel package that provides Git-like checkpoint functionality for your SQLite database during local development. Create snapshots of your database state and restore them instantly when needed.

## Features

- ✅ Create full database snapshots with a single command
- ✅ List all available checkpoints with metadata
- ✅ Restore any checkpoint to reset your database state
- ✅ Delete old checkpoints to save disk space
- ✅ SQLite only (perfect for local development)
- ✅ Simple and production-safe
- ✅ No configuration required

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- SQLite database (local development only)

## Installation

Install the package via Composer:

```bash
composer require tyrolabs/tyro-checkpoint --dev
```

The package will automatically register itself via Laravel's package discovery.

Run the installation command to setup everything:

```bash
php artisan tyro-checkpoint:install
```

This will:
- Check your SQLite database configuration
- Create the checkpoint storage directory
- Create the checkpoints metadata file (checkpoints.json)
- Optionally create an initial checkpoint

That's it! You're ready to create checkpoints.

**Note**: No database migrations are needed as checkpoint metadata is stored in a JSON file.

## Usage

### Create a Checkpoint

Create a checkpoint with an auto-generated name:

```bash
php artisan tyro-checkpoint:create
```

Create a checkpoint with a custom name:

```bash
php artisan tyro-checkpoint:create my_feature_before_changes
```

Example output:
```
Creating checkpoint...
✓ Checkpoint created successfully!

  ID:      1
  Name:    my_feature_before_changes
  Size:    2.45 MB
  Created: 2026-01-23 10:30:15
```

### List All Checkpoints

View all available checkpoints:

```bash
php artisan tyro-checkpoint:list
```

Example output:
```
Found 3 checkpoint(s):

+----+---------------------------+---------+---------------------+
| ID | Name                      | Size    | Created At          |
+----+---------------------------+---------+---------------------+
| 3  | before_user_migration     | 2.48 MB | 2026-01-23 14:20:00 |
| 2  | after_seeding             | 2.45 MB | 2026-01-23 12:15:30 |
| 1  | clean_database            | 1.98 MB | 2026-01-23 10:00:00 |
+----+---------------------------+---------+---------------------+
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

You'll be asked to confirm before the restore happens:

```
⚠ WARNING: This will replace your current database!

Checkpoint to restore:
  ID:      1
  Name:    clean_database
  Size:    1.98 MB
  Created: 2026-01-23 10:00:00

Do you want to proceed? (yes/no) [no]:
```

**Important**: Checkpoints are NOT deleted after restoration. You can restore the same checkpoint multiple times, allowing you to experiment with different approaches and always return to the same state.

### Delete a Checkpoint

Delete a checkpoint by ID:

```bash
php artisan tyro-checkpoint:delete 1
```

Or delete by name:

```bash
php artisan tyro-checkpoint:delete clean_database
```

You'll be asked to confirm before deletion:

```
Checkpoint to delete:
  ID:      1
  Name:    clean_database
  Size:    1.98 MB
  Created: 2026-01-23 10:00:00

Are you sure you want to delete this checkpoint? (yes/no) [no]:
```

### Delete All Checkpoints

Delete all checkpoints at once with the flush command:

```bash
php artisan tyro-checkpoint:flush
```

You'll see a list of all checkpoints and be asked to confirm:

```
⚠ WARNING: This will delete ALL checkpoints permanently!

Checkpoints to be deleted:

+----+---------------------------+---------+---------------------+
| ID | Name                      | Size    | Created At          |
+----+---------------------------+---------+---------------------+
| 3  | before_user_migration     | 2.48 MB | 2026-01-23 14:20:00 |
| 2  | after_seeding             | 2.45 MB | 2026-01-23 12:15:30 |
| 1  | clean_database            | 1.98 MB | 2026-01-23 10:00:00 |
+----+---------------------------+---------+---------------------+

Total: 3 checkpoint(s)

Are you sure you want to delete ALL checkpoints? (yes/no) [no]:
```

Skip the confirmation prompt with the `--force` flag:

```bash
php artisan tyro-checkpoint:flush --force
```

## How It Works

1. **Checkpoints are full snapshots**: Each checkpoint is a complete copy of your SQLite database file (no diffs or incrementals)
2. **Stored locally**: Checkpoint files are stored in `storage/tyro-checkpoints/`
3. **Metadata tracking**: Checkpoint metadata (ID, name, path, size, created_at) is stored in `storage/tyro-checkpoints/checkpoints.json` - **outside the database** to prevent loss when restoring
4. **Restore process**: Restoring a checkpoint replaces your current database file with the selected checkpoint file
5. **Persistent checkpoints**: Checkpoints are NOT automatically deleted after restoration. You can restore the same checkpoint multiple times and must manually delete checkpoints when no longer needed.
6. **Safe restoration**: Because metadata is stored outside the database, you never lose track of any checkpoint, even when restoring to an earlier state

## Common Use Cases

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

### Cleanup Old Checkpoints

```bash
# Delete a specific checkpoint when done
php artisan tyro-checkpoint:delete old_checkpoint

# Or delete all checkpoints at once
php artisan tyro-checkpoint:flush
```

## Additional Commands

### Check Version and Status

Display package version and system information:

```bash
php artisan tyro-checkpoint:version
```

This shows:
- Package version
- Laravel and PHP versions
- Database driver and configuration
- Checkpoint storage statistics

### Installation Command

Re-run the installation setup:

```bash
php artisan tyro-checkpoint:install
```

Useful when:
- Setting up the package on a new environment
- Verifying your SQLite configuration
- Creating the checkpoint storage directory

## Upgrading from v1.0.x

If you're upgrading from an earlier version that stored checkpoint metadata in the database:

1. **Your existing checkpoint files are safe** - They're still in `storage/tyro-checkpoints/`
2. **Checkpoint list will be empty** - The old metadata was in the database
3. **No action required** - Just start creating new checkpoints with the new version
4. **Optional cleanup** - You can manually delete old checkpoint `.sqlite` files if you no longer need them

The new version stores metadata in `checkpoints.json` which survives database restores.

## Storage Location

Checkpoint files and metadata are stored at:
```
storage/tyro-checkpoints/
├── checkpoints.json          # Metadata for all checkpoints
├── checkpoint_name_1.sqlite  # Database snapshot 1
├── checkpoint_name_2.sqlite  # Database snapshot 2
└── ...
```

- **checkpoints.json**: Contains metadata (ID, name, path, size, created_at) for all checkpoints
- **{checkpoint_name}.sqlite**: The actual database snapshot files

**Important**: The metadata is stored in a JSON file (not in the database) so that restoring a checkpoint doesn't cause you to lose track of other checkpoints.

## Important Notes

- **SQLite only**: This package only supports SQLite databases. It will throw an error if you try to use it with MySQL, PostgreSQL, or other database drivers.
- **Local development only**: This package should only be used in local development environments. Install it as a dev dependency with `--dev`.
- **Not for production**: Never use this package in production environments.
- **In-memory databases not supported**: SQLite `:memory:` databases cannot be checkpointed.
- **Metadata stored outside database**: Checkpoint metadata is stored in a JSON file, not in the database itself. This ensures you never lose track of checkpoints when restoring.
- **Checkpoints persist after restore**: Checkpoints are NOT automatically deleted when restored. This allows you to restore the same checkpoint multiple times to test different approaches.
- **Manual cleanup required**: Use `php artisan tyro-checkpoint:delete` or `php artisan tyro-checkpoint:flush` to remove checkpoints you no longer need.
- **Disk space**: Each checkpoint is a full copy of your database, so they can consume disk space. Delete old checkpoints you no longer need.

## Error Handling

The package includes comprehensive error handling:

- **Non-SQLite database**: Error if your database driver is not SQLite
- **In-memory database**: Error if using `:memory:` SQLite database
- **Missing database file**: Error if the database file doesn't exist
- **Duplicate name**: Error if creating a checkpoint with an existing name
- **Missing checkpoint**: Error if trying to restore/delete a non-existent checkpoint
- **File operations**: Error if checkpoint file operations fail

## Package Structure

```
tyro-checkpoint/
├── src/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CheckpointCreateCommand.php
│   │       ├── CheckpointListCommand.php
│   │       ├── CheckpointRestoreCommand.php
│   │       ├── CheckpointDeleteCommand.php
│   │       ├── CheckpointFlushCommand.php
│   │       ├── CheckpointVersionCommand.php
│   │       └── CheckpointInstallCommand.php
│   ├── Exceptions/
│   │   └── CheckpointException.php
│   ├── Models/
│   │   └── Checkpoint.php
│   ├── Services/
│   │   └── CheckpointService.php
│   └── TyroCheckpointServiceProvider.php
├── database/
│   └── migrations/
│       └── 2026_01_23_000000_create_tyro_checkpoints_table.php
├── composer.json
├── LICENSE
└── README.md
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Created by [Tyro Labs](https://tyrolabs.com)

## Support

If you encounter any issues or have questions, please open an issue on GitHub.
