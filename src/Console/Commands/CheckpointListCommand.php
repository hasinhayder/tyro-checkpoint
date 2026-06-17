<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Console\Commands\Concerns\FormatsCheckpointTables;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * CheckpointListCommand
 *
 * Lists all available database checkpoints with their metadata.
 *
 * Usage:
 *   php artisan tyro-checkpoint:list
 *   php artisan tyro-checkpoint:list 1
 *   php artisan tyro-checkpoint:list my_checkpoint
 */
class CheckpointListCommand extends Command {
    use FormatsCheckpointTables;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:list {identifier? : Checkpoint ID or name to inspect}';

    /**
     * The console command description.
     */
    protected $description = 'List all available database checkpoints';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        try {
            $identifier = $this->argument('identifier');

            if ($identifier) {
                return $this->call('tyro-checkpoint:details', [
                    'identifier' => $identifier,
                ]);
            }

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
                return [
                    $checkpoint->id,
                    $this->formatTableName($checkpoint),
                    $this->formatTableNote($checkpoint->note),
                    $service->formatFileSize($checkpoint->size),
                    $this->formatTableCreated($checkpoint->created_at),
                    $this->formatTableEncrypted($checkpoint->encrypted),
                ];
            })->toArray();

            $this->table(
                ['ID', 'Name', 'Note', 'Size', 'Created', 'Enc'],
                $rows
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ An unexpected error occurred: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
