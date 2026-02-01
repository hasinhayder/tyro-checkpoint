<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointDeleteCommand
 * 
 * Deletes a database checkpoint and its associated file.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:delete 1
 *   php artisan tyro-checkpoint:delete my_checkpoint
 */
class CheckpointDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:delete {identifier : Checkpoint ID or name to delete}';

    /**
     * The console command description.
     */
    protected $description = 'Delete a database checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int
    {
        try {
            // Get the identifier (ID or name)
            $identifier = $this->argument('identifier');

            // Find the checkpoint first to display info
            $checkpoint = $service->find($identifier);

            if (!$checkpoint) {
                $this->error("✗ Checkpoint not found: {$identifier}");
                $this->line('');
                $this->line('List available checkpoints with:');
                $this->line('  php artisan tyro-checkpoint:list');
                return self::FAILURE;
            }

            // Show checkpoint info and ask for confirmation
            $this->line("Checkpoint to delete:");
            $this->line("  ID:      {$checkpoint->id}");
            $this->line("  Name:    {$checkpoint->name}");
            $this->line("  Size:    {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created: {$checkpoint->created_at->format('Y-m-d H:i:s')}");
            $this->line('');

            // Ask for confirmation
            if (!$this->confirm('Are you sure you want to delete this checkpoint?', false)) {
                $this->info('Delete cancelled.');
                return self::SUCCESS;
            }

            // Delete the checkpoint
            $service->delete($identifier);

            // Success message
            $this->info("✓ Checkpoint '{$checkpoint->name}' deleted successfully!");
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
