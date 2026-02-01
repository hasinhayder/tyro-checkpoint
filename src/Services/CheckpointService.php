<?php

namespace HasinHayder\TyroCheckpoint\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use HasinHayder\TyroCheckpoint\Models\Checkpoint;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointService
 * 
 * Handles all checkpoint operations: create, list, restore, and delete.
 * Works exclusively with SQLite databases for local development.
 * 
 * Checkpoint metadata is stored in a JSON file outside the database
 * to prevent loss of checkpoint history when restoring.
 */
class CheckpointService
{
    /**
     * Get the path to the checkpoint storage directory.
     */
    public function getCheckpointStoragePath(): string
    {
        // Use the configured storage path, falling back to default if not set
        return config('tyro-checkpoint.storage_path', storage_path('tyro-checkpoints'));
    }

    /**
     * Get the path to the checkpoints metadata JSON file.
     */
    public function getCheckpointsFilePath(): string
    {
        return $this->getCheckpointStoragePath() . '/checkpoints.json';
    }

    /**
     * Load all checkpoints from the JSON file.
     */
    protected function loadCheckpoints(): array
    {
        $filePath = $this->getCheckpointsFilePath();
        
        if (!File::exists($filePath)) {
            return [];
        }

        $json = File::get($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CheckpointException(
                'Failed to parse checkpoints file: ' . json_last_error_msg()
            );
        }

        return $data ?? [];
    }

    /**
     * Save checkpoints to the JSON file.
     */
    protected function saveCheckpoints(array $checkpoints): void
    {
        $filePath = $this->getCheckpointsFilePath();
        $json = json_encode($checkpoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!File::put($filePath, $json)) {
            throw new CheckpointException(
                "Failed to save checkpoints file: {$filePath}"
            );
        }
    }

    /**
     * Ensure the checkpoint storage directory exists.
     */
    protected function ensureStorageDirectoryExists(): void
    {
        $path = $this->getCheckpointStoragePath();
        
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Get the current SQLite database file path.
     * 
     * @throws CheckpointException
     */
    public function getDatabasePath(): string
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        // Only SQLite is supported
        if ($driver !== 'sqlite') {
            throw new CheckpointException(
                "Tyro Checkpoint only supports SQLite databases. Current driver: {$driver}"
            );
        }

        $databasePath = config("database.connections.{$connection}.database");

        if (!$databasePath || $databasePath === ':memory:') {
            throw new CheckpointException(
                'In-memory SQLite databases are not supported for checkpoints.'
            );
        }

        if (!File::exists($databasePath)) {
            throw new CheckpointException(
                "Database file not found: {$databasePath}"
            );
        }

        return $databasePath;
    }

    /**
     * Create a new checkpoint.
     *
     * @param string|null $name Optional checkpoint name
     * @param string|null $note Optional note for the checkpoint
     * @return Checkpoint
     * @throws CheckpointException
     */
    public function create(?string $name = null, ?string $note = null): Checkpoint
    {
        $this->ensureStorageDirectoryExists();

        // Generate checkpoint name if not provided
        if (!$name) {
            $name = 'checkpoint_' . date('Y_m_d_His');
        }

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

        // Get the source database file path
        $sourcePath = $this->getDatabasePath();

        // Create checkpoint file path
        $checkpointPath = $this->getCheckpointStoragePath() . '/' . $name . '.sqlite';

        // Copy the database file to create the checkpoint
        if (!File::copy($sourcePath, $checkpointPath)) {
            throw new CheckpointException(
                "Failed to create checkpoint file: {$checkpointPath}"
            );
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
            'locked' => false, // New checkpoints are not locked by default
            'note' => $note,
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
     * @return \Illuminate\Support\Collection
     */
    public function list()
    {
        $checkpoints = $this->loadCheckpoints();

        // Convert to Checkpoint models and sort by created_at descending
        $collection = collect($checkpoints)
            ->map(fn($data) => new Checkpoint($data))
            ->sortByDesc('created_at')
            ->values();

        return $collection;
    }

    /**
     * Find a checkpoint by ID or name.
     * 
     * @param string|int $identifier Checkpoint ID or name
     * @return Checkpoint|null
     */
    public function find($identifier): ?Checkpoint
    {
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
     * @param string $identifier Checkpoint ID or name
     * @return Checkpoint
     * @throws CheckpointException
     */
    public function restore(string $identifier): Checkpoint
    {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (!$checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

        // Verify checkpoint file exists
        if (!File::exists($checkpoint->path)) {
            throw new CheckpointException(
                "Checkpoint file not found: {$checkpoint->path}"
            );
        }

        // Get current database path
        $databasePath = $this->getDatabasePath();

        // Close all database connections to unlock the file
        DB::disconnect();

        // Replace the current database with the checkpoint
        if (!File::copy($checkpoint->path, $databasePath)) {
            throw new CheckpointException(
                "Failed to restore checkpoint. Could not copy file to: {$databasePath}"
            );
        }

        // Reconnect to the database
        DB::reconnect();

        return $checkpoint;
    }

    /**
     * Delete a checkpoint and its associated file.
     *
     * @param string $identifier Checkpoint ID or name
     * @return bool
     * @throws CheckpointException
     */
    public function delete(string $identifier): bool
    {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (!$checkpoint) {
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

        // Delete the checkpoint file if it exists
        if (File::exists($checkpoint->path)) {
            File::delete($checkpoint->path);
        }

        // Load checkpoints and remove the matching one
        $checkpoints = $this->loadCheckpoints();
        $filtered = array_values(array_filter($checkpoints, function($data) use ($checkpoint) {
            return $data['id'] !== $checkpoint->id;
        }));

        // Save updated list
        $this->saveCheckpoints($filtered);

        return true;
    }

    /**
     * Lock a checkpoint to prevent deletion.
     *
     * @param string $identifier Checkpoint ID or name
     * @return Checkpoint
     * @throws CheckpointException
     */
    public function lock(string $identifier): Checkpoint
    {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (!$checkpoint) {
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

        if (!$updated) {
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
     * @param string $identifier Checkpoint ID or name
     * @return Checkpoint
     * @throws CheckpointException
     */
    public function unlock(string $identifier): Checkpoint
    {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (!$checkpoint) {
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

        if (!$updated) {
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
     * Update the note for a checkpoint.
     *
     * @param string $identifier Checkpoint ID or name
     * @param string|null $note The note to set (null to remove)
     * @return Checkpoint
     * @throws CheckpointException
     */
    public function updateNote(string $identifier, ?string $note): Checkpoint
    {
        // Find the checkpoint
        $checkpoint = $this->find($identifier);

        if (!$checkpoint) {
            throw new CheckpointException(
                "Checkpoint not found: {$identifier}"
            );
        }

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

        if (!$updated) {
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
     * Get human-readable file size.
     * 
     * @param int $bytes
     * @return string
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
