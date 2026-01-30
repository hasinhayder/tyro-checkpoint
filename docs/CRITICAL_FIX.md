# Critical Fix: Checkpoint Metadata Storage

## Problem Identified

In version 1.0.x, checkpoint metadata was stored in the `tyro_checkpoints` table **inside the database**. This caused a critical issue:

### The Issue

1. Create checkpoint A (metadata saved in database)
2. Create checkpoint B (metadata saved in database)
3. Restore checkpoint A (entire database replaced)
4. **Result**: Checkpoint B's metadata is lost because the database was replaced with checkpoint A's version

This made it impossible to track checkpoints created after the point you restore to.

## Solution Implemented (v1.1.0)

Checkpoint metadata is now stored in **`storage/tyro-checkpoints/checkpoints.json`** - a JSON file **outside the database**.

### Benefits

✅ **Checkpoint history survives restoration** - Restoring any checkpoint never loses track of other checkpoints
✅ **Restore multiple times** - You can restore the same checkpoint repeatedly
✅ **Complete checkpoint visibility** - All checkpoints are always visible in the list
✅ **No data loss** - Checkpoint metadata is never affected by database operations

## Changes Made

### Files Modified

1. **CheckpointService.php** - Complete rewrite to use JSON file storage
   - Added `loadCheckpoints()` and `saveCheckpoints()` methods
   - All CRUD operations now work with JSON file

2. **Checkpoint.php** - Converted from Eloquent model to simple data object
   - No longer extends `Model`
   - Simple constructor that accepts array data
   - Added `toArray()` method for serialization

3. **Migration** - Deprecated but kept for backward compatibility
   - No longer creates database table
   - Includes note explaining the change

4. **CheckpointFlushCommand.php** - New command to delete all checkpoints
   - Lists all checkpoints before deletion
   - Requires confirmation (unless --force)
   - Shows progress of deletion

5. **CheckpointInstallCommand.php** - Updated to create JSON file
   - No longer runs database migration
   - Creates empty `checkpoints.json` on install

6. **README.md** - Complete documentation update
   - Explains JSON storage
   - Documents storage location structure
   - Added upgrade guide

7. **CHANGELOG.md** - New file documenting all changes

### Storage Structure

```
storage/tyro-checkpoints/
├── checkpoints.json          # Metadata for all checkpoints
├── checkpoint_name_1.sqlite  # Database snapshot 1
├── checkpoint_name_2.sqlite  # Database snapshot 2
└── ...
```

### JSON Format

```json
[
  {
    "id": 1,
    "name": "checkpoint_2026_01_30_123456",
    "path": "/path/to/storage/tyro-checkpoints/checkpoint_2026_01_30_123456.sqlite",
    "size": 2457600,
    "created_at": "2026-01-30T12:34:56+00:00"
  }
]
```

## Backward Compatibility

- Checkpoint files (`.sqlite`) from v1.0.x are **preserved**
- Old metadata in database is **ignored** (but not deleted)
- Users can continue using old checkpoints by creating new ones with the same names
- No breaking changes to command interface

## Testing Checklist

- [x] Create checkpoint - saves to JSON
- [x] List checkpoints - reads from JSON
- [x] Restore checkpoint - doesn't lose other checkpoints
- [x] Delete checkpoint - removes from JSON
- [x] Flush checkpoints - removes all from JSON
- [x] Install command - creates JSON file
- [x] Version command - shows 1.1.0

## Migration Path

For users upgrading from v1.0.x:

1. Pull latest code
2. Run `php artisan tyro-checkpoint:install` (optional)
3. Old checkpoints will not appear in list (metadata was in old database)
4. Start creating new checkpoints - they will be tracked properly
5. Optionally delete old `.sqlite` files you don't need

## Key Takeaway

**The fundamental issue is now fixed**: Checkpoints are tracked independently of the database they're protecting, ensuring you never lose track of any checkpoint regardless of how many times you restore.
