<?php

namespace HasinHayder\TyroCheckpoint\Services;

use HasinHayder\TyroCheckpoint\Drivers\DriverManager;
use HasinHayder\TyroCheckpoint\Drivers\SqliteCheckpointDriver;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Models\Checkpoint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * CheckpointService
 *
 * Handles all checkpoint operations: create, list, restore, and delete.
 * Supports SQLite, MySQL, and PostgreSQL via driver abstraction.
 *
 * Checkpoint metadata is stored in a JSON file outside the database
 * to prevent loss of checkpoint history when restoring.
 */
class CheckpointService {
    public function __construct(
        private readonly DriverManager $driverManager,
    ) {}

    /**
     * Maximum allowed length for checkpoint names.
     */
    public const MAX_NAME_LENGTH = 255;

    /**
     * Maximum allowed length for checkpoint notes.
     */
    public const MAX_NOTE_LENGTH = 1000;

    /**
     * Validate a checkpoint name for security.
     *
     * @throws CheckpointException
     */
    protected function validateCheckpointName(string $name): void {
        // Check length
        if (strlen($name) > self::MAX_NAME_LENGTH) {
            throw new CheckpointException(
                'Checkpoint name exceeds maximum length of '.self::MAX_NAME_LENGTH.' characters.'
            );
        }

        // Whitelist validation: only alphanumeric, underscores, and hyphens
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new CheckpointException(
                "Invalid checkpoint name '{$name}'. Use only letters, numbers, underscores, and hyphens."
            );
        }
    }

    /**
     * Validate a checkpoint note for security.
     *
     * @throws CheckpointException
     */
    protected function validateCheckpointNote(?string $note): void {
        if ($note !== null && strlen($note) > self::MAX_NOTE_LENGTH) {
            throw new CheckpointException(
                'Checkpoint note exceeds maximum length of '.self::MAX_NOTE_LENGTH.' characters.'
            );
        }
    }

    /**
     * Validate that a checkpoint path is within the allowed storage directory.
     * This prevents path traversal attacks.
     *
     * @throws CheckpointException
     */
    protected function validateCheckpointPath(string $checkpointPath): void {
        $storagePath = realpath($this->getCheckpointStoragePath());
        $realCheckpointPath = realpath(dirname($checkpointPath));

        // If storage directory doesn't exist yet, allow it (will be created)
        if ($storagePath === false) {
            return;
        }

        // Check if the checkpoint path is within storage
        if ($realCheckpointPath !== false && ! str_starts_with($realCheckpointPath, $storagePath)) {
            throw new CheckpointException(
                'Invalid checkpoint path detected. Path traversal attempt blocked.'
            );
        }
    }

    /**
     * Get the path to the checkpoint storage directory.
     */
    public function getCheckpointStoragePath(): string {
        // Use the configured storage path, falling back to default if not set
        return config('tyro-checkpoint.storage_path', storage_path('tyro-checkpoints'));
    }

    /**
     * Get the path to the checkpoints metadata JSON file.
     */
    public function getCheckpointsFilePath(): string {
        return $this->getCheckpointStoragePath().'/checkpoints.json';
    }

    /**
     * Load all checkpoints from the JSON file.
     * Automatically attempts to restore from backup if the main file is corrupted.
     *
     * @throws CheckpointException
     */
    protected function loadCheckpoints(): array {
        $filePath = $this->getCheckpointsFilePath();

        if (! File::exists($filePath)) {
            return [];
        }

        $json = File::get($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Main file is corrupted - try to restore from backup
            $backupPath = $this->getBackupFilePath();

            if (File::exists($backupPath)) {
                $backupJson = File::get($backupPath);
                $backupData = json_decode($backupJson, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Backup is valid - restore it
                    File::copy($backupPath, $filePath);

                    return $backupData ?? [];
                }
            }

            // No valid backup available
            throw new CheckpointException(
                'Checkpoints file is corrupted and no valid backup exists. '.
                'You may need to delete the corrupted file and start fresh.'
            );
        }

        return $data ?? [];
    }

    /**
     * Save checkpoints to the JSON file using atomic write.
     * This ensures the JSON file is never left in a corrupted state.
     *
     * @throws CheckpointException
     */
    protected function saveCheckpoints(array $checkpoints): void {
        $filePath = $this->getCheckpointsFilePath();

        // Create backup before modification (if original exists)
        $this->createBackup();

        // Encode to JSON
        $json = json_encode($checkpoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new CheckpointException(
                'Failed to encode checkpoints to JSON: '.json_last_error_msg()
            );
        }

        // Validate that the encoded JSON can be decoded back (sanity check)
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CheckpointException(
                'JSON validation failed: encoded data is not valid JSON'
            );
        }

        // Use atomic write: write to temp file first, then rename
        $tempFile = $filePath.'.tmp.'.uniqid();

        try {
            // Write to temporary file
            if (File::put($tempFile, $json) === false) {
                throw new CheckpointException(
                    "Failed to write temporary checkpoints file: {$tempFile}"
                );
            }

            // Atomic rename (on most filesystems, rename is atomic)
            if (! rename($tempFile, $filePath)) {
                throw new CheckpointException(
                    "Failed to save checkpoints file: could not rename temp file to {$filePath}"
                );
            }
        } finally {
            // Clean up temp file if it still exists (in case of failure)
            if (File::exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Create a backup of the current checkpoints file before modification.
     */
    protected function createBackup(): void {
        $filePath = $this->getCheckpointsFilePath();
        $backupPath = $this->getBackupFilePath();

        // Only backup if original file exists and is valid
        if (! File::exists($filePath)) {
            return;
        }

        // Verify the current file is valid JSON before backing up
        $content = File::get($filePath);
        json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Current file is corrupted - don't backup, let operation proceed
            // which will overwrite with valid data
            return;
        }

        // Create backup (overwrites previous backup)
        File::copy($filePath, $backupPath);
    }

    /**
     * Get the path to the backup file.
     */
    protected function getBackupFilePath(): string {
        return $this->getCheckpointStoragePath().'/checkpoints.json.bak';
    }

    /**
     * Restore checkpoints from backup if available.
     * This is a public method that can be called for recovery.
     *
     * @return bool True if restored successfully, false if no backup exists
     *
     * @throws CheckpointException
     */
    public function restoreFromBackup(): bool {
        $backupPath = $this->getBackupFilePath();
        $filePath = $this->getCheckpointsFilePath();

        if (! File::exists($backupPath)) {
            return false;
        }

        // Validate backup is valid JSON
        $content = File::get($backupPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CheckpointException(
                'Backup file is corrupted and cannot be restored.'
            );
        }

        // Restore from backup
        File::copy($backupPath, $filePath);

        return true;
    }

    /**
     * Check if a backup exists.
     */
    public function hasBackup(): bool {
        return File::exists($this->getBackupFilePath());
    }

    /**
     * Ensure the checkpoint storage directory exists.
     */
    protected function ensureStorageDirectoryExists(): void {
        $path = $this->getCheckpointStoragePath();

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Get the current SQLite database file path.
     *
     * @deprecated 1.6.0 Use the driver directly instead. This method is SQLite-only
     *             and will throw a CheckpointException for non-SQLite connections.
     *
     * @throws CheckpointException
     */
    public function getDatabasePath(): string {
        $driver = $this->driverManager->driver();

        if ($driver->name() !== 'sqlite') {
            throw new CheckpointException(
                'getDatabasePath() is only available for SQLite connections.'
            );
        }

        return (new SqliteCheckpointDriver(
            $this->driverManager->connectionName()
        ))->getDatabasePath();
    }

    /**
     * Create a new checkpoint.
     *
     * @param  string|null  $name  Optional checkpoint name
     * @param  string|null  $note  Optional note for the checkpoint
     * @param  bool  $encrypt  Whether to encrypt the checkpoint
     *
     * @throws CheckpointException
     */
    public function create(?string $name = null, ?string $note = null, bool $encrypt = false): Checkpoint {
        $this->ensureStorageDirectoryExists();

        $driver = $this->driverManager->driver();

        // Check if encryption is requested
        if ($encrypt) {
            $encryptionKey = $this->getEncryptionKey();
            if (! $encryptionKey) {
                throw new CheckpointException(
                    "Encryption key not found in the config or env file. Please run 'php artisan tyro-checkpoint:generate-key' first."
                );
            }
        }

        // Generate checkpoint name if not provided
        if (! $name) {
            $name = 'checkpoint_'.date('Y_m_d_His');
        }

        // Validate checkpoint name for security (prevents path traversal)
        $this->validateCheckpointName($name);

        // Validate note length
        $this->validateCheckpointNote($note);

        // Load existing checkpoints
        $checkpoints = $this->loadCheckpoints();

        // Check if checkpoint with this name already exists
        foreach ($checkpoints as $existing) {
            if ($existing['name'] === $name) {
                throw new CheckpointException(
                    "A checkpoint with the name '{$name}' already exists."
                );
            }
        }

        // Create checkpoint file path (extension from driver)
        $extension = $driver->fileExtension();
        $checkpointPath = $this->getCheckpointStoragePath().'/'.$name.$extension;

        // Validate the checkpoint path is within storage (defense in depth)
        $this->validateCheckpointPath($checkpointPath);

        // Write raw snapshot via driver to a temp file under sys_get_temp_dir
        $tempDir = sys_get_temp_dir();
        $tempPath = $tempDir.'/tyro_checkpoint_'.uniqid().$extension;

        try {
            $driver->createSnapshot($tempPath);

            if ($encrypt) {
                if (! $this->encryptFile($tempPath, $checkpointPath)) {
                    throw new CheckpointException(
                        "Failed to create encrypted checkpoint file: {$checkpointPath}"
                    );
                }
            } else {
                if (! rename($tempPath, $checkpointPath)) {
                    throw new CheckpointException(
                        "Failed to move checkpoint file to: {$checkpointPath}"
                    );
                }
            }
        } finally {
            if (File::exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        // Get file size
        $size = File::size($checkpointPath);

        // Generate new ID (max existing ID + 1)
        $maxId = 0;
        foreach ($checkpoints as $existing) {
            if ($existing['id'] > $maxId) {
                $maxId = $existing['id'];
            }
        }
        $newId = $maxId + 1;

        // Create checkpoint data
        $checkpointData = [
            'id' => $newId,
            'name' => $name,
            'path' => $checkpointPath,
            'size' => $size,
            'created_at' => now()->toIso8601String(),
            'locked' => false,
            'flagged' => false,
            'note' => $note,
            'encrypted' => $encrypt,
            'driver' => $driver->name(),
            'database' => $driver->databaseName(),
        ];

        // Add to checkpoints array
        $checkpoints[] = $checkpointData;

        // Save to JSON file
        $this->saveCheckpoints($checkpoints);

        // Return as Checkpoint model
        return new Checkpoint($checkpointData);
    }

    /**
     * Get all checkpoints ordered by creation date (newest first).
     *
     * @return Collection
     */
    public function list() {
        $checkpoints = $this->loadCheckpoints();

        // Convert to Checkpoint models and sort by created_at descending
        $collection = collect($checkpoints)
            ->map(fn ($data) => new Checkpoint($data))
            ->sortByDesc('created_at')
            ->values();

        return $collection;
    }

    /**
     * Find a checkpoint by ID or name.
     *
     * @param  string|int  $identifier  Checkpoint ID or name
     */
    public function find($identifier): ?Checkpoint {
        $checkpoints = $this->loadCheckpoints();

        foreach ($checkpoints as $data) {
            // Try to match by ID (if numeric) or name
            if ((is_numeric($identifier) && $data['id'] == $identifier) || $data['name'] === $identifier) {
                return new Checkpoint($data);
            }
        }

        return null;
    }

    /**
     * Restore a checkpoint by replacing the current database with it.
     *
     * @param  string  $identifier  Checkpoint ID or name
     * @param  bool  $force  Bypass the database-identity guard
     *
     * @throws CheckpointException
     */
    public function restore(string $identifier, bool $force = false): Checkpoint {
        $checkpoint = $this->find($identifier);

        if (! $checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        if (! File::exists($checkpoint->path)) {
            throw new CheckpointException(
                "Checkpoint file not found: {$checkpoint->path}"
            );
        }

        $this->validateCheckpointPath($checkpoint->path);

        // Pick driver from the checkpoint's stored driver field (default sqlite)
        $storedDriver = $checkpoint->driver ?? 'sqlite';
        $currentConnection = $this->driverManager->connectionName();
        $currentConnectionDriver = config("database.connections.{$currentConnection}.driver");

        // Engine mismatch guard: can't restore a MySQL dump into SQLite, etc.
        if ($storedDriver !== $currentConnectionDriver) {
            throw new CheckpointException(
                "Cannot restore a '{$storedDriver}' checkpoint while the active connection is '{$currentConnectionDriver}'. ".
                'Switch your database connection or use a matching checkpoint.'
            );
        }

        $driver = $this->driverManager->driverForName($storedDriver, $currentConnection);

        // Database-identity guard: prevents restoring a snapshot from one database
        // into another of the same engine (e.g. two MySQL databases).
        // Skipped for legacy checkpoints that have no recorded database (treated as sqlite).
        $currentDatabase = $driver->databaseName();

        if (! $force && $checkpoint->database !== null && $currentDatabase === null) {
            throw new CheckpointException(
                "Cannot verify the checkpoint origin: the active connection '{$currentConnection}' ".
                'has no configured database name. Set a database name or use --force.'
            );
        }

        if (! $force && $checkpoint->database !== null && $currentDatabase !== null
            && $checkpoint->database !== $currentDatabase) {
            throw new CheckpointException(
                "Cannot restore a checkpoint from database '{$checkpoint->database}' into database '{$currentDatabase}'. ".
                'Use the --force flag to override.'
            );
        }

        if ($checkpoint->encrypted) {
            $tempPath = sys_get_temp_dir().'/tyro_restore_'.uniqid().$driver->fileExtension();

            try {
                if (! $this->decryptFile($checkpoint->path, $tempPath)) {
                    throw new CheckpointException(
                        'Failed to decrypt checkpoint for restore.'
                    );
                }
                $driver->restoreSnapshot($tempPath);
            } finally {
                if (File::exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        } else {
            $driver->restoreSnapshot($checkpoint->path);
        }

        return $checkpoint;
    }

    /**
     * Delete a checkpoint and its associated file.
     *
     * @param  string  $identifier  Checkpoint ID or name
     *
     * @throws CheckpointException
     */
    public function delete(string $identifier): bool {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (! $checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        // Check if the checkpoint is locked
        if ($checkpoint->locked) {
            throw new CheckpointException(
                "Cannot delete locked checkpoint: {$identifier}. Please unlock it first."
            );
        }

        // Validate the checkpoint path is within storage directory (security check)
        $this->validateCheckpointPath($checkpoint->path);

        // Delete the checkpoint file if it exists
        if (File::exists($checkpoint->path)) {
            File::delete($checkpoint->path);
        }

        // Load checkpoints and remove the matching one
        $checkpoints = $this->loadCheckpoints();
        $filtered = array_values(array_filter($checkpoints, function ($data) use ($checkpoint) {
            return $data['id'] !== $checkpoint->id;
        }));

        // Save updated list
        $this->saveCheckpoints($filtered);

        return true;
    }

    /**
     * Lock a checkpoint to prevent deletion.
     *
     * @param  string  $identifier  Checkpoint ID or name
     *
     * @throws CheckpointException
     */
    public function lock(string $identifier): Checkpoint {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (! $checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        // Load checkpoints and update the matching one
        $checkpoints = $this->loadCheckpoints();
        $updated = false;

        foreach ($checkpoints as &$data) {
            if ($data['id'] === $checkpoint->id) {
                $data['locked'] = true;
                $updated = true;
                break;
            }
        }

        if (! $updated) {
            throw new CheckpointException(
                "Failed to lock checkpoint: {$identifier}"
            );
        }

        // Save updated list
        $this->saveCheckpoints($checkpoints);

        // Return updated checkpoint
        return new Checkpoint($checkpoints[array_search($checkpoint->id, array_column($checkpoints, 'id'))]);
    }

    /**
     * Unlock a checkpoint to allow deletion.
     *
     * @param  string  $identifier  Checkpoint ID or name
     *
     * @throws CheckpointException
     */
    public function unlock(string $identifier): Checkpoint {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (! $checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        // Load checkpoints and update the matching one
        $checkpoints = $this->loadCheckpoints();
        $updated = false;

        foreach ($checkpoints as &$data) {
            if ($data['id'] === $checkpoint->id) {
                $data['locked'] = false;
                $updated = true;
                break;
            }
        }

        if (! $updated) {
            throw new CheckpointException(
                "Failed to unlock checkpoint: {$identifier}"
            );
        }

        // Save updated list
        $this->saveCheckpoints($checkpoints);

        // Return updated checkpoint
        return new Checkpoint($checkpoints[array_search($checkpoint->id, array_column($checkpoints, 'id'))]);
    }

    /**
     * Flag a checkpoint for attention.
     *
     * @param  string  $identifier  Checkpoint ID or name
     *
     * @throws CheckpointException
     */
    public function flag(string $identifier): Checkpoint {
        return $this->setFlagged($identifier, true);
    }

    /**
     * Unflag a checkpoint.
     *
     * @param  string  $identifier  Checkpoint ID or name
     *
     * @throws CheckpointException
     */
    public function unflag(string $identifier): Checkpoint {
        return $this->setFlagged($identifier, false);
    }

    /**
     * Set the flagged status for a checkpoint.
     *
     * @param  string  $identifier  Checkpoint ID or name
     *
     * @throws CheckpointException
     */
    private function setFlagged(string $identifier, bool $flagged): Checkpoint {
        $checkpoint = $this->find($identifier);

        if (! $checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        $checkpoints = $this->loadCheckpoints();
        $updated = false;

        foreach ($checkpoints as &$data) {
            if ($data['id'] === $checkpoint->id) {
                $data['flagged'] = $flagged;
                $updated = true;
                break;
            }
        }

        if (! $updated) {
            throw new CheckpointException(
                "Failed to update flagged status for checkpoint: {$identifier}"
            );
        }

        $this->saveCheckpoints($checkpoints);

        return new Checkpoint($checkpoints[array_search($checkpoint->id, array_column($checkpoints, 'id'))]);
    }

    /**
     * Update the note for a checkpoint.
     *
     * @param  string  $identifier  Checkpoint ID or name
     * @param  string|null  $note  The note to set (null to remove)
     *
     * @throws CheckpointException
     */
    public function updateNote(string $identifier, ?string $note): Checkpoint {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (! $checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        // Validate note length
        $this->validateCheckpointNote($note);

        // Load checkpoints and update the matching one
        $checkpoints = $this->loadCheckpoints();
        $updated = false;

        foreach ($checkpoints as &$data) {
            if ($data['id'] === $checkpoint->id) {
                $data['note'] = $note;
                $updated = true;
                break;
            }
        }

        if (! $updated) {
            throw new CheckpointException(
                "Failed to update note for checkpoint: {$identifier}"
            );
        }

        // Save updated list
        $this->saveCheckpoints($checkpoints);

        // Return updated checkpoint
        return new Checkpoint($checkpoints[array_search($checkpoint->id, array_column($checkpoints, 'id'))]);
    }

    /**
     * Get the encryption key from config.
     */
    protected function getEncryptionKey(): ?string {
        return config('tyro-checkpoint.encryption_key');
    }

    /**
     * Encrypt a file.
     */
    protected function encryptFile(string $sourcePath, string $destinationPath): bool {
        $key = $this->getEncryptionKey();
        $encrypter = new Encrypter($key, config('app.cipher', 'AES-256-CBC'));

        $content = File::get($sourcePath);
        $encryptedContent = $encrypter->encrypt($content);

        return File::put($destinationPath, $encryptedContent) !== false;
    }

    /**
     * Decrypt a file.
     */
    protected function decryptFile(string $sourcePath, string $destinationPath): bool {
        $key = $this->getEncryptionKey();

        if (! $key) {
            throw new CheckpointException(
                'Encryption key not found. Cannot decrypt checkpoint.'
            );
        }

        $encrypter = new Encrypter($key, config('app.cipher', 'AES-256-CBC'));

        $content = File::get($sourcePath);

        try {
            $decryptedContent = $encrypter->decrypt($content);
        } catch (\Exception $e) {
            throw new CheckpointException(
                'Failed to decrypt checkpoint. The encryption key might be incorrect.'
            );
        }

        return File::put($destinationPath, $decryptedContent) !== false;
    }

    /**
     * Get human-readable file size.
     */
    public function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
