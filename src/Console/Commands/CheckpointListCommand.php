<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;

/**
 * CheckpointListCommand
 * 
 * Lists all available database checkpoints with their metadata.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:list
 */
class CheckpointListCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:list';

    /**
     * The console command description.
     */
    protected $description = 'List all available database checkpoints';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int
    {
        try {
            // Get all checkpoints
            $checkpoints = $service->list();

            // Check if there are any checkpoints
            if ($checkpoints->isEmpty()) {
                $this->info('No checkpoints found.');
                $this->line('');
                $this->line('Create your first checkpoint with:');
                $this->line('  php artisan tyro-checkpoint:create');
                return self::SUCCESS;
            }

            // Display checkpoints in a table
            $this->info("Found {$checkpoints->count()} checkpoint(s):");
            $this->line('');

            $rows = $checkpoints->map(function ($checkpoint) use ($service) {
                $name = $checkpoint->name;
                if ($checkpoint->locked) {
                    $name .= ' 🔒'; // Add lock symbol for locked checkpoints
                }
                return [
                    $checkpoint->id,
                    $name,
                    $checkpoint->note ?? '-',
                    $service->formatFileSize($checkpoint->size),
                    $checkpoint->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Note', 'Size', 'Created At'],
                $rows
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ An unexpected error occurred: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
