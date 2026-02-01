<?php

namespace TyroLabs\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use TyroLabs\TyroCheckpoint\Services\CheckpointService;
use TyroLabs\TyroCheckpoint\Exceptions\CheckpointException;

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
    protected $signature = 'tyro-checkpoint:create {name? : Optional name for the checkpoint} {--note= : Optional note for the checkpoint}';

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

            // Show info message
            $this->info('Creating checkpoint...');

            // Create the checkpoint
            $checkpoint = $service->create($name, $note);

            // Success message
            $this->info("✓ Checkpoint created successfully!");
            $this->line('');
            $this->line("  ID:      {$checkpoint->id}");
            $this->line("  Name:    {$checkpoint->name}");
            $this->line("  Size:    {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created: {$checkpoint->created_at->format('Y-m-d H:i:s')}");
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
