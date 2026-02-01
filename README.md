# Tyro Checkpoint

**Database checkpoints for Laravel local development (SQLite only)**

Tyro Checkpoint is a simple Laravel package that provides Git-like checkpoint functionality for your SQLite database during local development. Create snapshots of your database state and restore them instantly when needed.

## Features

-  Create full database snapshots with a single command
-  List all available checkpoints with metadata
-  Restore any checkpoint to reset your database state
-  Delete old checkpoints to save disk space
-  **Lock checkpoints** to prevent accidental deletion
-  **Add notes** to checkpoints for better organization
-  SQLite only (perfect for local development)
-  Simple and production-safe
-  No configuration required

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x or 12.x
- SQLite database

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
- Check your SQLite database configuration
- Create the checkpoint storage directory
- Create the checkpoints metadata file (checkpoints.json)
- Optionally create an initial checkpoint

That's it! You're ready to create checkpoints.

**Note**: No database migrations are needed as checkpoint metadata is stored in a JSON file.

### Optional: Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan tyro-checkpoint:publish-config
```

This creates `config/tyro-checkpoint.php` where you can customize the storage path.

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

+----+---------------------------+---------+---------------------+--------+-------+
| ID | Name                      | Size    | Created At          | Locked | Note  |
+----+---------------------------+---------+---------------------+--------+-------+
| 3  | before_user_migration     | 2.48 MB | 2026-02-01 14:20:00 | No     |       |
| 2  | after_seeding             | 2.45 MB | 2026-02-01 12:15:30 | Yes    | Clean |
| 1  | clean_database            | 1.98 MB | 2026-02-01 10:00:00 | No     |       |
+----+---------------------------+---------+---------------------+--------+-------+
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
  Created: 2026-02-01 10:00:00

Do you want to proceed? (yes/no) [no]:
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

1. **Checkpoints are full snapshots**: Each checkpoint is a complete copy of your SQLite database file (no diffs or incrementals)
2. **Stored locally**: Checkpoint files are stored in `storage/tyro-checkpoints/`
3. **Metadata tracking**: Checkpoint metadata (ID, name, path, size, created_at, locked, note) is stored in `storage/tyro-checkpoints/checkpoints.json` - **outside the database** to prevent loss when restoring
4. **Restore process**: Restoring a checkpoint replaces your current database file with the selected checkpoint file
5. **Persistent checkpoints**: Checkpoints are NOT automatically deleted after restoration. You can restore the same checkpoint multiple times and must manually delete checkpoints when no longer needed.
6. **Safe restoration**: Because metadata is stored outside the database, you never lose track of any checkpoint, even when restoring to an earlier state
7. **Lock protection**: Locked checkpoints are protected from deletion to preserve important baseline states

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
];
```

## Important Notes

- **SQLite only**: This package only supports SQLite databases. It will throw an error if you try to use it with MySQL, PostgreSQL, or other database drivers.
- **Local development only**: This package should only be used in local development environments. Install it as a dev dependency with `--dev`.
- **Not for production**: Never use this package in production environments.
- **In-memory databases not supported**: SQLite `:memory:` databases cannot be checkpointed.
- **Metadata stored outside database**: Checkpoint metadata is stored in a JSON file, not in the database itself. This ensures you never lose track of checkpoints when restoring.
- **Checkpoints persist after restore**: Checkpoints are NOT automatically deleted when restored. This allows you to restore the same checkpoint multiple times to test different approaches.
- **Manual cleanup required**: Use `php artisan tyro-checkpoint:delete` or `php artisan tyro-checkpoint:flush` to remove checkpoints you no longer need.
- **Disk space**: Each checkpoint is a full copy of your database, so they can consume disk space. Delete old checkpoints you no longer need.
- **Locked checkpoints**: Locked checkpoints are protected from deletion. Use this to preserve important baseline states.

## Error Handling

The package includes comprehensive error handling:

- **Non-SQLite database**: Error if your database driver is not SQLite
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
