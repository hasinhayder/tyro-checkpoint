<?php

namespace TyroLabs\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use TyroLabs\TyroCheckpoint\Services\CheckpointService;
use TyroLabs\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointRestoreCommand
 * 
 * Restores a database checkpoint by replacing the current database file.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:restore 1
 *   php artisan tyro-checkpoint:restore my_checkpoint
 */
class CheckpointRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:restore {identifier : Checkpoint ID or name to restore}';

    /**
     * The console command description.
     */
    protected $description = 'Restore a database checkpoint';

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
            $this->warn('⚠ WARNING: This will replace your current database!');
            $this->line('');
            $this->line("Checkpoint to restore:");
            $this->line("  ID:      {$checkpoint->id}");
            $this->line("  Name:    {$checkpoint->name}");
            $this->line("  Size:    {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created: {$checkpoint->created_at->format('Y-m-d H:i:s')}");
            $this->line('');

            // Ask for confirmation
            if (!$this->confirm('Do you want to proceed?', false)) {
                $this->info('Restore cancelled.');
                return self::SUCCESS;
            }

            // Restore the checkpoint
            $this->info('Restoring checkpoint...');
            $service->restore($identifier);

            // Success message
            $this->info("✓ Checkpoint '{$checkpoint->name}' restored successfully!");
            $this->line('');
            $this->line("💡 Note: This checkpoint is still available and can be restored again.");
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
