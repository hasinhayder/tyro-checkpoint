<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;

/**
 * CheckpointRestoreCommand
 *
 * Restores a database checkpoint by replacing the current database file.
 *
 * Usage:
 *   php artisan tyro-checkpoint:restore
 *   php artisan tyro-checkpoint:restore 1
 *   php artisan tyro-checkpoint:restore my_checkpoint
 */
class CheckpointRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:restore {identifier? : Checkpoint ID or name to restore}';

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

            // If no identifier provided, ask the user to select one
            if (!$identifier) {
                $checkpoints = $service->list();

                if ($checkpoints->isEmpty()) {
                    $this->error('✗ No checkpoints found.');
                    return self::FAILURE;
                }

                $this->info('Available checkpoints:');
                $this->line('');

                // Display table of checkpoints
                $headers = ['ID', 'Name', 'Note', 'Size', 'Created', 'Encrypted'];
                $rows = $this->formatTableRows($checkpoints, $service);
                $this->table($headers, $rows);

                // Get available IDs for validation
                $availableIds = $checkpoints->pluck('id')->toArray();

                $this->line('');
                $input = $this->ask('Enter checkpoint ID to restore (0 to quit)');

                // Handle quit
                if ($input === '0' || $input === 0) {
                    $this->info('Operation cancelled.');
                    return self::SUCCESS;
                }

                // Validate input is a valid ID
                if (!is_numeric($input) || !in_array((int) $input, $availableIds)) {
                    $this->error("✗ Invalid checkpoint ID: {$input}");
                    return self::FAILURE;
                }

                $identifier = (int) $input;
            }

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
            if ($checkpoint->encrypted) {
                $this->line("  Status:  <comment>Encrypted</comment>");
            }
            if ($checkpoint->note) {
                $this->line("  Note:    {$checkpoint->note}");
            }
            $this->line('');

            // Ask for confirmation
            if (!$this->confirm('Do you want to proceed?', false)) {
                $this->info('Restore cancelled.');
                return self::SUCCESS;
            }

            // Restore the checkpoint
            $this->info('Restoring checkpoint...');
            $service->restore((string) $identifier);

            // Success message
            $this->info("✓ Checkpoint '{$checkpoint->name}' restored successfully!");
            $this->line('');
            $this->line("Note: This checkpoint is still available and can be restored again.");
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

    /**
     * Truncate note for display purposes.
     */
    private function truncateNote(string $note, int $maxLength = 20): string
    {
        if (strlen($note) <= $maxLength) {
            return $note;
        }

        return substr($note, 0, $maxLength) . '...';
    }

    /**
     * Format checkpoints data for table display.
     */
    private function formatTableRows($checkpoints, $service): array
    {
        $rows = [];
        foreach ($checkpoints as $checkpoint) {
            $name = $checkpoint->name;
            if ($checkpoint->locked) {
                $name .= ' 🔒';
            }
            $row = [
                $checkpoint->id,
                $name,
                $checkpoint->note ? $this->truncateNote($checkpoint->note) : '-',
                $service->formatFileSize($checkpoint->size),
                $checkpoint->created_at->format('Y-m-d H:i:s'),
                $checkpoint->encrypted ? 'Yes' : 'No',
            ];

            $rows[] = $row;
        }

        return $rows;
    }
}
