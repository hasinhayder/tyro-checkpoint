<?php

namespace HasinHayder\TyroCheckpoint\Drivers;

use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Process\ProcessRunner;

class DriverManager {
    private const SUPPORTED_DRIVERS = ['sqlite', 'mysql', 'pgsql'];

    public function __construct(
        private readonly ProcessRunner $processRunner,
    ) {}

    /**
     * Resolve the active connection name.
     */
    public function connectionName(): string {
        return config('tyro-checkpoint.connection') ?: config('database.default', 'sqlite');
    }

    /**
     * Get the driver for the currently active connection.
     */
    public function driver(): CheckpointDriver {
        return $this->driverForConnection($this->connectionName());
    }

    /**
     * Build a driver for a specific connection name.
     */
    public function driverForConnection(string $connection): CheckpointDriver {
        $driver = config("database.connections.{$connection}.driver");

        return $this->driverForName($driver, $connection);
    }

    /**
     * Build a driver by driver name targeting a specific connection.
     * Used during restore when the driver is sourced from the checkpoint
     * metadata rather than the current connection config.
     *
     * @param  string  $driverName  'sqlite', 'mysql', or 'pgsql'
     * @param  string  $connection  The Laravel connection name
     */
    public function driverForName(string $driverName, string $connection): CheckpointDriver {
        if (! in_array($driverName, self::SUPPORTED_DRIVERS, true)) {
            throw new CheckpointException(
                "Unsupported database driver: {$driverName}. Tyro Checkpoint supports SQLite, MySQL, and PostgreSQL."
            );
        }

        return match ($driverName) {
            'sqlite' => new SqliteCheckpointDriver($connection),
            'mysql' => new MysqlCheckpointDriver($connection, $this->processRunner),
            'pgsql' => new PostgresCheckpointDriver($connection, $this->processRunner),
        };
    }
}
