<?php

namespace HasinHayder\TyroCheckpoint\Models;

use Carbon\Carbon;

/**
 * Checkpoint Model
 *
 * Represents a database checkpoint with its metadata.
 * Each checkpoint is a full snapshot of the database.
 *
 * This is a simple data object (not an Eloquent model) since checkpoint
 * metadata is stored in a JSON file outside the database to prevent loss
 * when restoring checkpoints.
 *
 * @property int $id
 * @property string $name
 * @property string $path
 * @property int $size
 * @property Carbon $created_at
 * @property bool $locked
 * @property bool $flagged
 * @property string|null $note
 * @property bool $encrypted
 * @property string $driver
 * @property string|null $database
 */
class Checkpoint {
    /**
     * Checkpoint ID
     */
    public int $id;

    /**
     * Checkpoint name
     */
    public string $name;

    /**
     * Path to checkpoint file
     */
    public string $path;

    /**
     * File size in bytes
     */
    public int $size;

    /**
     * Creation timestamp
     */
    public Carbon $created_at;

    /**
     * Whether the checkpoint is locked
     */
    public bool $locked;

    /**
     * Whether the checkpoint is flagged
     */
    public bool $flagged;

    /**
     * Optional note for the checkpoint
     */
    public ?string $note;

    /**
     * Whether the checkpoint is encrypted
     */
    public bool $encrypted;

    /**
     * Database driver used to create this checkpoint ('sqlite', 'mysql', 'pgsql')
     */
    public string $driver;

    /**
     * Identity of the database this checkpoint belongs to
     * (SQLite: file path, MySQL/PostgreSQL: database name).
     * Null for legacy checkpoints created before this field existed.
     */
    public ?string $database;

    /**
     * Create a new Checkpoint instance from array data.
     */
    public function __construct(array $data) {
        $this->id = (int) $data['id'];
        $this->name = $data['name'];
        $this->path = $data['path'];
        $this->size = (int) $data['size'];
        $this->created_at = is_string($data['created_at'])
            ? Carbon::parse($data['created_at'])
            : $data['created_at'];
        $this->locked = $data['locked'] ?? false;
        $this->flagged = $data['flagged'] ?? false;
        $this->note = $data['note'] ?? null;
        $this->encrypted = $data['encrypted'] ?? false;
        $this->driver = $data['driver'] ?? 'sqlite';
        $this->database = $data['database'] ?? null;
    }

    /**
     * Convert the checkpoint to an array.
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'size' => $this->size,
            'created_at' => $this->created_at->toIso8601String(),
            'locked' => $this->locked,
            'flagged' => $this->flagged,
            'note' => $this->note,
            'encrypted' => $this->encrypted,
            'driver' => $this->driver,
            'database' => $this->database,
        ];
    }

    /**
     * Magic getter for property access.
     */
    public function __get(string $name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return null;
    }
}
