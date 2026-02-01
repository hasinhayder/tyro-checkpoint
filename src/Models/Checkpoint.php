<?php

namespace TyroLabs\TyroCheckpoint\Models;

use Carbon\Carbon;

/**
 * Checkpoint Model
 *
 * Represents a database checkpoint with its metadata.
 * Each checkpoint is a full snapshot of the SQLite database file.
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
 * @property string|null $note
 */
class Checkpoint
{
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
     * Optional note for the checkpoint
     */
    public ?string $note;

    /**
     * Create a new Checkpoint instance from array data.
     */
    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->name = $data['name'];
        $this->path = $data['path'];
        $this->size = (int) $data['size'];
        $this->created_at = is_string($data['created_at'])
            ? Carbon::parse($data['created_at'])
            : $data['created_at'];
        $this->locked = $data['locked'] ?? false;
        $this->note = $data['note'] ?? null;
    }

    /**
     * Convert the checkpoint to an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'size' => $this->size,
            'created_at' => $this->created_at->toIso8601String(),
            'locked' => $this->locked,
            'note' => $this->note,
        ];
    }

    /**
     * Magic getter for property access.
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }
}
