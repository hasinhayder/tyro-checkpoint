<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Console\Commands\Concerns\FormatsCheckpointTables;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * CheckpointDetailsCommand
 *
 * Shows detailed information for a database checkpoint.
 *
 * Usage:
 *   php artisan tyro-checkpoint:details
 *   php artisan tyro-checkpoint:details 1
 *   php artisan tyro-checkpoint:details my_checkpoint
 */
class CheckpointDetailsCommand extends Command {
    use FormatsCheckpointTables;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:details {identifier? : Checkpoint ID or name to inspect}';

    /**
     * The console command description.
     */
    protected $description = 'Show detailed information for a checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        try {
            $identifier = $this->argument('identifier');

            if (! $identifier) {
                $identifier = $this->askForCheckpointIdentifier($service);

                if (! $identifier) {
                    return self::SUCCESS;
                }
            }

            $checkpoint = $service->find($identifier);

            if (! $checkpoint) {
                $this->error("✗ Checkpoint not found: {$identifier}");
                $this->line('');
                $this->line('List available checkpoints with:');
                $this->line('  php artisan tyro-checkpoint:list');

                return self::FAILURE;
            }

            $this->info('Checkpoint details:');
            $this->line('');
            $this->line("  ID:        {$checkpoint->id}");
            $this->line("  Name:      {$checkpoint->name}");
            $this->line('  Driver:    '.$checkpoint->driver);
            $this->line("  Size:      {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created:   {$checkpoint->created_at->format('Y-m-d H:i:s')}");
            $this->line('  Locked:    '.($checkpoint->locked ? 'Yes' : 'No'));
            $this->line('  Flagged:   '.($checkpoint->flagged ? 'Yes 🚩' : 'No'));
            $this->line('  Encrypted: '.($checkpoint->encrypted ? 'Yes' : 'No'));
            $this->line("  Path:      {$checkpoint->path}");
            $this->line('  Note:      '.($checkpoint->note ?: '-'));
            $this->line('');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ An unexpected error occurred: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function askForCheckpointIdentifier(CheckpointService $service): int|string|null {
        $checkpoints = $service->list();

        if ($checkpoints->isEmpty()) {
            $this->error('✗ No checkpoints found.');

            return null;
        }

        $this->info('Available checkpoints:');
        $this->line('');

        $this->table(
            ['ID', 'Name', 'Note', 'Size', 'Created', 'Enc'],
            $checkpoints->map(function ($checkpoint) use ($service) {
                return [
                    $checkpoint->id,
                    $this->formatTableName($checkpoint),
                    $this->formatTableNote($checkpoint->note),
                    $service->formatFileSize($checkpoint->size),
                    $this->formatTableCreated($checkpoint->created_at),
                    $this->formatTableEncrypted($checkpoint->encrypted),
                ];
            })->toArray()
        );

        $availableIds = $checkpoints->pluck('id')->toArray();

        $this->line('');
        $input = $this->ask('Enter checkpoint ID to inspect (0 to quit)');

        if ($input === '0' || $input === 0) {
            $this->info('Operation cancelled.');

            return null;
        }

        if (! is_numeric($input) || ! in_array((int) $input, $availableIds, true)) {
            $this->error("✗ Invalid checkpoint ID: {$input}");

            return null;
        }

        return (int) $input;
    }
}
