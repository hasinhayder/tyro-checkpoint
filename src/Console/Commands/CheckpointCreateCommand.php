<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointCreateCommand
 * 
 * Creates a new database checkpoint by copying the current SQLite database file.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:create
 *   php artisan tyro-checkpoint:create my_checkpoint
 */
class CheckpointCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:create {name? : Optional name for the checkpoint} {--note= : Optional note for the checkpoint} {--encrypt : Encrypt the checkpoint}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new database checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int
    {
        try {
            // Get the checkpoint name from argument
            $name = $this->argument('name');
            $note = $this->option('note');
            $encrypt = $this->option('encrypt');

            // Show info message
            $this->info($encrypt ? 'Creating encrypted checkpoint...' : 'Creating checkpoint...');

            // Create the checkpoint
            $checkpoint = $service->create($name, $note, $encrypt);

            // Success message
            $this->info("✓ Checkpoint created successfully!");
            $this->line('');
            $this->line("  ID:      {$checkpoint->id}");
            $this->line("  Name:    {$checkpoint->name}");
            $this->line("  Size:    {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created: {$checkpoint->created_at->format('Y-m-d H:i:s')}");
            if ($checkpoint->encrypted) {
                $this->line("  Status:  <comment>Encrypted</comment>");
            }

            // If no note was provided via option, ask if user wants to add one now
            if (!$note) {
                $this->line('');
                if ($this->confirm('Would you like to add a note to this checkpoint?', false)) {
                    $newNote = $this->ask('Enter the note for this checkpoint');

                    if ($newNote) {
                        // Update the note for the newly created checkpoint
                        $checkpoint = $service->updateNote($checkpoint->id, $newNote);

                        $this->info("✓ Note added successfully!");
                        $this->line("  Note: {$newNote}");
                    } else {
                        $this->info('No note added.');
                    }
                } else {
                    $this->info('No note added.');
                }
            } else {
                // Show the note that was added via the option
                $this->line("  Note: {$note}");
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
