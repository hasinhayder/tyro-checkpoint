# Test Scenario: Checkpoint Metadata Persistence

## Scenario: Restoring an Earlier Checkpoint

This scenario demonstrates that checkpoint metadata is preserved when restoring to an earlier database state.

### Steps

1. **Initial State** - Clean database
   ```bash
   php artisan tyro-checkpoint:create clean_start
   ```
   
   Result: checkpoint #1 created

2. **Add Some Data** - Make database changes
   ```bash
   # Add users, posts, etc.
   php artisan db:seed
   ```

3. **Create Second Checkpoint**
   ```bash
   php artisan tyro-checkpoint:create after_seeding
   ```
   
   Result: checkpoint #2 created

4. **Make More Changes**
   ```bash
   # More database operations
   php artisan migrate:fresh
   php artisan db:seed --class=AdvancedSeeder
   ```

5. **Create Third Checkpoint**
   ```bash
   php artisan tyro-checkpoint:create with_advanced_data
   ```
   
   Result: checkpoint #3 created

6. **List All Checkpoints**
   ```bash
   php artisan tyro-checkpoint:list
   ```
   
   Expected output:
   ```
   +----+----------------------+---------+---------------------+
   | ID | Name                 | Size    | Created At          |
   +----+----------------------+---------+---------------------+
   | 3  | with_advanced_data   | 3.45 MB | 2026-01-30 14:20:00 |
   | 2  | after_seeding        | 2.80 MB | 2026-01-30 14:15:00 |
   | 1  | clean_start          | 1.98 MB | 2026-01-30 14:10:00 |
   +----+----------------------+---------+---------------------+
   ```

7. **Restore to Checkpoint #1** (the earliest one)
   ```bash
   php artisan tyro-checkpoint:restore clean_start
   ```
   
   Database is now back to clean state

8. **List Checkpoints Again**
   ```bash
   php artisan tyro-checkpoint:list
   ```
   
   Expected output (ALL checkpoints still visible):
   ```
   +----+----------------------+---------+---------------------+
   | ID | Name                 | Size    | Created At          |
   +----+----------------------+---------+---------------------+
   | 3  | with_advanced_data   | 3.45 MB | 2026-01-30 14:20:00 |
   | 2  | after_seeding        | 2.80 MB | 2026-01-30 14:15:00 |
   | 1  | clean_start          | 1.98 MB | 2026-01-30 14:10:00 |
   +----+----------------------+---------+---------------------+
   ```
   
   ✅ **Success!** All checkpoints are still visible

9. **Restore to Checkpoint #3**
   ```bash
   php artisan tyro-checkpoint:restore with_advanced_data
   ```
   
   Database now has advanced data again

10. **Verify All Checkpoints Still Exist**
    ```bash
    php artisan tyro-checkpoint:list
    ```
    
    ✅ All 3 checkpoints still available

## What This Proves

### Before (v1.0.x) - BROKEN ❌

When you restored checkpoint #1, the database was replaced with the old version that only knew about checkpoint #1. Checkpoints #2 and #3 would **disappear from the list** because their metadata was stored in the database.

### After (v1.1.0) - FIXED ✅

When you restore checkpoint #1, only the database is replaced. The checkpoint metadata remains in `checkpoints.json` (outside the database), so **all checkpoints remain visible** regardless of which one you restore to.

## Real-World Example

### Development Workflow

```bash
# Morning: Start with clean data
php artisan tyro-checkpoint:create morning_clean

# Add feature A
# ... code changes ...
php artisan tyro-checkpoint:create feature_a_complete

# Add feature B
# ... code changes ...
php artisan tyro-checkpoint:create feature_b_complete

# Oops! Feature B has a bug, go back to feature A
php artisan tyro-checkpoint:restore feature_a_complete

# Try a different approach for feature B
# ... different code ...
php artisan tyro-checkpoint:create feature_b_v2

# Test both versions by switching between them
php artisan tyro-checkpoint:restore feature_a_complete
php artisan tyro-checkpoint:restore feature_b_v2
php artisan tyro-checkpoint:restore morning_clean

# ALL checkpoints remain available throughout!
```

## Verification Checklist

- [x] Create multiple checkpoints in sequence
- [x] Restore to earliest checkpoint
- [x] Verify all checkpoints still listed
- [x] Restore to latest checkpoint
- [x] Verify all checkpoints still listed
- [x] Restore to middle checkpoint
- [x] Verify all checkpoints still listed
- [x] Delete a specific checkpoint
- [x] Verify only deleted checkpoint removed from list
- [x] Flush all checkpoints
- [x] Verify list is empty
- [x] Create new checkpoint after flush
- [x] Verify new checkpoint appears in list

## File System State

During the entire process, these files exist:

```
storage/tyro-checkpoints/
├── checkpoints.json              # Always has ALL checkpoint metadata
├── clean_start.sqlite           # Checkpoint #1 file
├── after_seeding.sqlite         # Checkpoint #2 file
└── with_advanced_data.sqlite    # Checkpoint #3 file
```

No matter which checkpoint you restore, `checkpoints.json` is **never modified** (unless you explicitly delete checkpoints).
