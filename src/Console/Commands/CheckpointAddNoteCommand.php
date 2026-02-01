<?php

namespace TyroLabs\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use TyroLabs\TyroCheckpoint\Services\CheckpointService;
use TyroLabs\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointAddNoteCommand
 * 
 * Adds or updates a note for an existing checkpoint.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:add-note
 *   php artisan tyro-checkpoint:add-note {id}
 */
class CheckpointAddNoteCommand extends Command
{
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
    public function handle(CheckpointService $service): int
    {
        try {
            // Get all checkpoints for selection
            $checkpoints = $service->list();

            if ($checkpoints->isEmpty()) {
                $this->error('✗ No checkpoints found.');
                return self::FAILURE;
            }

            // Get the checkpoint identifier
            $identifier = $this->argument('id');

            if (!$identifier) {
                // Show checkpoints and ask for selection
                $this->info('Available checkpoints:');
                $this->line('');

                $options = [];
                $indexMap = [];
                $index = 1;
                foreach ($checkpoints as $checkpoint) {
                    $label = "#{$checkpoint->id} - {$checkpoint->name}";
                    if ($checkpoint->note) {
                        $label .= " (Current note: {$checkpoint->note})";
                    }
                    $options[$index] = $label;
                    $indexMap[$index] = $checkpoint->id;
                    $index++;
                }

                $selectedLabel = $this->choice(
                    'Select a checkpoint to add/update note (enter the number)',
                    $options
                );

                // Find the index that corresponds to the selected label
                $selectedIndex = array_search($selectedLabel, $options);
                $identifier = $indexMap[$selectedIndex];
            }

            // Find the checkpoint
            $checkpoint = $service->find($identifier);

            if (!$checkpoint) {
                $this->error("✗ Checkpoint not found: {$identifier}");
                return self::FAILURE;
            }

            // Show current note if exists
            if ($checkpoint->note) {
                $this->line('');
                $this->info('Current note: ' . $checkpoint->note);
            }

            // Ask for the new note
            $this->line('');
            $note = $this->ask('Enter the new note (leave empty to remove)');

            // Update the note
            $service->updateNote($identifier, $note ?: null);

            // Success message
            $this->line('');
            if ($note) {
                $this->info("✓ Note updated successfully for checkpoint: {$checkpoint->name}");
                $this->line("  Note: {$note}");
            } else {
                $this->info("✓ Note removed from checkpoint: {$checkpoint->name}");
            }
            $this->line('');

            return self::SUCCESS;

        } catch (CheckpointException $e) {
            $this->error("✗ {$e->getMessage()}");
            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error("✗ An unexpected error occurred: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
