<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands\Concerns;

trait FormatsCheckpointTables {
    private function formatTableName($checkpoint): string {
        $name = $checkpoint->name;

        if ($checkpoint->locked) {
            $name .= ' 🔒';
        }

        if ($checkpoint->flagged) {
            $name .= ' 🚩';
        }

        if ($this->isAutoCheckpoint($checkpoint)) {
            return "<comment>{$name}</comment>";
        }

        if ($checkpoint->encrypted) {
            return "<info>{$name}</info>";
        }

        if ($checkpoint->locked) {
            return "<fg=red>{$name}</>";
        }

        return $name;
    }

    private function formatTableEncrypted(bool $encrypted): string {
        return $encrypted ? '<info>Yes</info>' : 'No';
    }

    private function formatTableLocked(bool $locked): string {
        return $locked ? '<comment>Yes</comment>' : 'No';
    }

    private function formatTableNote($checkpoint, int $maxLength = 40): string {
        // Auto-created checkpoints show a concise label in tables; the full
        // note is still rendered by the details command.
        if ($this->isAutoCheckpoint($checkpoint)) {
            return 'Auto-created';
        }

        $note = $checkpoint->note;

        if (! $note) {
            return '-';
        }

        if (strlen($note) <= $maxLength) {
            return $note;
        }

        return substr($note, 0, $maxLength).'...';
    }

    private function formatTableCreated($createdAt): string {
        return $createdAt->format('d/m/y g:ia');
    }

    /**
     * Human-readable database label for table cells.
     *
     * SQLite -> "SQLite"; MySQL/PostgreSQL -> "<Label> (<database>)".
     * Also doubles as the identity used by isMixedDatabases().
     */
    private function formatDatabaseLabel($checkpoint): string {
        $driver = $checkpoint->driver ?? 'sqlite';
        $database = $checkpoint->database;

        return match ($driver) {
            'mysql' => $database ? "MySQL ({$database})" : 'MySQL',
            'pgsql' => $database ? "PG ({$database})" : 'PG',
            default => 'SQLite',
        };
    }

    /**
     * True when the checkpoint set spans more than one database
     * (by display label). Used to decide whether to show the Database column.
     *
     * @param  iterable  $checkpoints
     */
    private function isMixedDatabases($checkpoints): bool {
        $labels = [];

        foreach ($checkpoints as $checkpoint) {
            $labels[$this->formatDatabaseLabel($checkpoint)] = true;
        }

        return count($labels) > 1;
    }

    private function isAutoCheckpoint($checkpoint): bool {
        $prefix = trim((string) config('tyro-checkpoint.auto_checkpoint.name_prefix', 'auto'), '_');

        return str_starts_with($checkpoint->name, "{$prefix}_")
            || str_starts_with((string) $checkpoint->note, 'Auto-created before running');
    }
}
