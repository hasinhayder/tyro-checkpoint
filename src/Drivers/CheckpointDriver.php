<?php

namespace HasinHayder\TyroCheckpoint\Drivers;

use HasinHayder\TyroCheckpoint\Exceptions\BinaryNotFoundException;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;

interface CheckpointDriver {
    /**
     * Human-readable driver name (matches database config driver key).
     */
    public function name(): string;

    /**
     * File extension for checkpoint artifacts (including the dot).
     */
    public function fileExtension(): string;

    /**
     * Identity of the target database this driver snapshots.
     *
     * SQLite: the database file path.
     * MySQL/PostgreSQL: the configured database name.
     *
     * Used to record which database a checkpoint belongs to and to guard
     * against restoring a snapshot into a different database of the same engine.
     */
    public function databaseName(): ?string;

    /**
     * Verify that everything needed is available.
     *
     * @throws CheckpointException
     * @throws BinaryNotFoundException
     */
    public function assertReady(): void;

    /**
     * Write the raw (unencrypted) snapshot to $path.
     */
    public function createSnapshot(string $path): void;

    /**
     * Load the raw snapshot from $path back into the database (drop & import).
     */
    public function restoreSnapshot(string $path): void;
}
