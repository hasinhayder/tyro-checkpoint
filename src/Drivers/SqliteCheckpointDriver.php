<?php

namespace HasinHayder\TyroCheckpoint\Drivers;

use HasinHayder\TyroCheckpoint\Drivers\Concerns\ResolvesConnectionConfig;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SqliteCheckpointDriver implements CheckpointDriver {
    use ResolvesConnectionConfig;

    public function __construct(
        private readonly string $connection,
    ) {}

    public function name(): string {
        return 'sqlite';
    }

    public function fileExtension(): string {
        return '.sqlite';
    }

    public function databaseName(): ?string {
        $path = $this->getConnectionDatabase($this->connection);

        // Use the basename as a stable identity so checkpoints survive project
        // relocation (cloning/moving the project directory). The full absolute
        // path would change between machines and falsely block restores.
        return $path ? basename($path) : null;
    }

    public function assertReady(): void {
        $driver = $this->getConnectionDriver($this->connection);

        if ($driver !== 'sqlite') {
            throw new CheckpointException(
                "Tyro Checkpoint only supports SQLite databases. Current driver: {$driver}"
            );
        }

        $databasePath = $this->getConnectionDatabase($this->connection);

        if (! $databasePath || $databasePath === ':memory:') {
            throw new CheckpointException(
                'In-memory SQLite databases are not supported for checkpoints.'
            );
        }

        if (! File::exists($databasePath)) {
            throw new CheckpointException(
                "Database file not found: {$databasePath}"
            );
        }
    }

    public function createSnapshot(string $path): void {
        $sourcePath = $this->getConnectionDatabase($this->connection);

        if (! File::copy($sourcePath, $path)) {
            throw new CheckpointException(
                "Failed to create checkpoint file: {$path}"
            );
        }
    }

    public function restoreSnapshot(string $path): void {
        $databasePath = $this->getConnectionDatabase($this->connection);

        DB::disconnect();

        if (! File::copy($path, $databasePath)) {
            throw new CheckpointException(
                "Failed to restore checkpoint. Could not copy file to: {$databasePath}"
            );
        }

        DB::reconnect();

        if (! File::exists($databasePath)) {
            throw new CheckpointException(
                "Database file not found after restore: {$databasePath}"
            );
        }
    }

    /**
     * Get the current SQLite database file path.
     */
    public function getDatabasePath(): string {
        return $this->getConnectionDatabase($this->connection);
    }
}
