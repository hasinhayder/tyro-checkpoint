<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;

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
            $headers = ['ID', 'Name', 'Size', 'Created', 'Locked'];
            $rows = $checkpoints->map(function ($checkpoint) use ($service) {
                return [
                    $checkpoint->id,
                    $checkpoint->name,
                    $service->formatFileSize($checkpoint->size),
                    $checkpoint->created_at->format('Y-m-d H:i:s'),
                    $checkpoint->locked ? 'Yes' : 'No',
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

            // Delete all checkpoints (skip locked ones)
            $this->info('Deleting checkpoints...');
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($checkpoints as $checkpoint) {
                // Skip locked checkpoints
                if ($checkpoint->locked) {
                    $this->warn("Skipping locked checkpoint: '{$checkpoint->name}' (ID: {$checkpoint->id})");
                    $skippedCount++;
                    continue;
                }

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

            if ($skippedCount > 0) {
                $this->warn("⚠ {$skippedCount} locked checkpoint(s) were skipped and not deleted.");
            }

            if ($deletedCount < ($checkpoints->count() - $skippedCount)) {
                $failedCount = $checkpoints->count() - $deletedCount - $skippedCount;
                $this->warn("⚠ {$failedCount} checkpoint(s) could not be deleted due to other errors.");
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
