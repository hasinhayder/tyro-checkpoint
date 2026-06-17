<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Console\Commands\Concerns\FormatsCheckpointTables;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * CheckpointAddNoteCommand
 *
 * Adds or updates a note for an existing checkpoint.
 *
 * Usage:
 *   php artisan tyro-checkpoint:add-note
 *   php artisan tyro-checkpoint:add-note {id}
 */
class CheckpointAddNoteCommand extends Command {
    use FormatsCheckpointTables;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:add-note {id? : The ID or name of the checkpoint}';

    /**
     * The console command description.
     */
    protected $description = 'Add or update a note for a checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        try {
            // Get all checkpoints for selection
            $checkpoints = $service->list();

            if ($checkpoints->isEmpty()) {
                $this->error('✗ No checkpoints found.');

                return self::FAILURE;
            }

            // Get the checkpoint identifier
            $identifier = $this->getCheckpointIdentifier($checkpoints);

            if (! $identifier) {
                $this->error('✗ No checkpoint selected.');

                return self::FAILURE;
            }

            // Validate identifier format
            if (! $this->isValidIdentifier($identifier)) {
                $this->error("✗ Invalid checkpoint identifier: {$identifier}");

                return self::FAILURE;
            }

            // Find the checkpoint
            $checkpoint = $service->find($identifier);

            if (! $checkpoint) {
                $this->error("✗ Checkpoint not found: {$identifier}");

                return self::FAILURE;
            }

            // Get the new note from user
            $note = $this->getNewNoteFromUser($checkpoint);

            // Validate note length if provided
            if ($note !== null && ! $this->isValidNote($note)) {
                $this->error('✗ Note exceeds maximum length of 1000 characters.');

                return self::FAILURE;
            }

            // Update the note
            $updatedCheckpoint = $service->updateNote($identifier, $note);

            // Display success message
            $this->displaySuccessMessage($updatedCheckpoint, $note);

            return self::SUCCESS;

        } catch (CheckpointException $e) {
            $this->error("✗ {$e->getMessage()}");

            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error("✗ An unexpected error occurred: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Get checkpoint identifier from argument or user selection.
     */
    private function getCheckpointIdentifier($checkpoints): ?string {
        $identifier = $this->argument('id');

        if ($identifier) {
            return $identifier;
        }

        // Show checkpoints and ask for selection
        $this->info('Available checkpoints:');
        $this->line('');

        $options = [];
        $indexMap = [];
        $index = 1;

        foreach ($checkpoints as $checkpoint) {
            $label = "#{$checkpoint->id} - {$checkpoint->name}";
            if ($checkpoint->note) {
                $label .= ' (Current note: '.$this->formatTableNote($checkpoint->note).')';
            }
            $options[$index] = $label;
            $indexMap[$index] = $checkpoint->id;
            $index++;
        }

        $headers = ['#', 'Name', 'Note'];
        $rows = $this->formatTableRows($checkpoints);
        $this->table($headers, $rows);

        $selectedLabel = $this->choice(
            'Select a checkpoint to add/update note',
            $options,
            null,
            null,
            false // Allow multiple selections to be disabled
        );

        // Find the index that corresponds to the selected label
        $selectedIndex = array_search($selectedLabel, $options);

        return $indexMap[$selectedIndex] ?? null;
    }

    /**
     * Get the new note from user input.
     */
    private function getNewNoteFromUser($checkpoint): ?string {
        // Show current note if exists
        if ($checkpoint->note) {
            $this->line('');
            $this->info('Current note: '.$this->formatTableNote($checkpoint->note));
        }

        // Ask for the new note
        $this->line('');
        $question = $checkpoint->note
            ? 'Enter the new note (leave empty to remove current note)'
            : 'Enter the new note (leave empty to skip)';

        $note = $this->ask($question);

        // Return null if empty to remove the note
        return $note ?: null;
    }

    /**
     * Display success message after updating the note.
     */
    private function displaySuccessMessage($checkpoint, ?string $note): void {
        $this->line('');
        if ($note) {
            $this->info("✓ Note updated successfully for checkpoint: {$checkpoint->name}");
            $this->line("  Note: {$note}");
        } else {
            $this->info("✓ Note removed from checkpoint: {$checkpoint->name}");
        }
        $this->line('');
    }

    /**
     * Validate checkpoint identifier format.
     */
    private function isValidIdentifier($identifier): bool {
        // Check if it's a numeric ID or a valid name
        if (is_numeric($identifier)) {
            return true;
        }

        // For names, ensure they match expected pattern (alphanumeric, underscores, hyphens, dots)
        return preg_match('/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*$/', $identifier) === 1;
    }

    /**
     * Validate note content.
     */
    private function isValidNote(string $note): bool {
        // Maximum length validation
        return strlen($note) <= 1000;
    }

    /**
     * Format checkpoints data for table display.
     */
    private function formatTableRows($checkpoints): array {
        $rows = [];
        foreach ($checkpoints as $checkpoint) {
            $row = [
                "#{$checkpoint->id}",
                $this->formatTableName($checkpoint),
            ];

            if ($checkpoint->note) {
                $row[] = $this->formatTableNote($checkpoint->note);
            } else {
                $row[] = '<comment>No note</comment>';
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
