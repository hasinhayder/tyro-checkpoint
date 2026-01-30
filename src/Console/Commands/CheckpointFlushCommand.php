<?php

namespace TyroLabs\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use TyroLabs\TyroCheckpoint\Services\CheckpointService;
use TyroLabs\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointFlushCommand
 * 
 * Deletes all database checkpoints and their associated files.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:flush
 *   php artisan tyro-checkpoint:flush --force
 */
class CheckpointFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:flush {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Delete all database checkpoints';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int
    {
        try {
            // Get all checkpoints
            $checkpoints = $service->list();

            if ($checkpoints->isEmpty()) {
                $this->info('No checkpoints found.');
                return self::SUCCESS;
            }

            // Show checkpoint list
            $this->warn('⚠ WARNING: This will delete ALL checkpoints permanently!');
            $this->line('');
            $this->line("Checkpoints to be deleted:");
            $this->line('');

            // Display checkpoints in a table
            $headers = ['ID', 'Name', 'Size', 'Created'];
            $rows = $checkpoints->map(function ($checkpoint) use ($service) {
                return [
                    $checkpoint->id,
                    $checkpoint->name,
                    $service->formatFileSize($checkpoint->size),
                    $checkpoint->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();

            $this->table($headers, $rows);
            $this->line('');
            $this->line("Total: {$checkpoints->count()} checkpoint(s)");
            $this->line('');

            // Ask for confirmation (unless --force flag is used)
            if (!$this->option('force')) {
                if (!$this->confirm('Are you sure you want to delete ALL checkpoints?', false)) {
                    $this->info('Flush cancelled.');
                    return self::SUCCESS;
                }
            }

            // Delete all checkpoints
            $this->info('Deleting checkpoints...');
            $deletedCount = 0;

            foreach ($checkpoints as $checkpoint) {
                try {
                    $service->delete($checkpoint->id);
                    $deletedCount++;
                } catch (CheckpointException $e) {
                    $this->warn("Failed to delete '{$checkpoint->name}': {$e->getMessage()}");
                }
            }

            // Success message
            $this->line('');
            $this->info("✓ Flushed {$deletedCount} checkpoint(s) successfully!");
            
            if ($deletedCount < $checkpoints->count()) {
                $failedCount = $checkpoints->count() - $deletedCount;
                $this->warn("⚠ {$failedCount} checkpoint(s) could not be deleted.");
            }

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
