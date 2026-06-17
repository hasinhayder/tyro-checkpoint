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

    private function formatTableNote(?string $note, int $maxLength = 40): string {
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

    private function isAutoCheckpoint($checkpoint): bool {
        $prefix = trim((string) config('tyro-checkpoint.auto_checkpoint.name_prefix', 'auto'), '_');

        return str_starts_with($checkpoint->name, "{$prefix}_")
            || str_starts_with((string) $checkpoint->note, 'Auto-created before running');
    }
}
