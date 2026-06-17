<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Console\Commands\Concerns\FormatsCheckpointTables;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

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
class CheckpointRestoreCommand extends Command {
    use FormatsCheckpointTables;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:restore {identifier? : Checkpoint ID or name to restore} {--force : Restore even if the database name does not match the checkpoint origin}';

    /**
     * The console command description.
     */
    protected $description = 'Restore a database checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        try {
            // Get the identifier (ID or name)
            $identifier = $this->argument('identifier');

            // If no identifier provided, ask the user to select one
            if (! $identifier) {
                $checkpoints = $service->list();

                if ($checkpoints->isEmpty()) {
                    $this->error('✗ No checkpoints found.');

                    return self::FAILURE;
                }

                $this->info('Available checkpoints:');
                $this->line('');

                // Display table of checkpoints
                $showDatabase = $this->isMixedDatabases($checkpoints);
                $headers = ['ID', 'Name'];
                if ($showDatabase) {
                    $headers[] = 'Database';
                }
                $headers = array_merge($headers, ['Note', 'Size', 'Created', 'Enc']);
                $rows = $this->formatTableRows($checkpoints, $service, $showDatabase);
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
                if (! is_numeric($input) || ! in_array((int) $input, $availableIds)) {
                    $this->error("✗ Invalid checkpoint ID: {$input}");

                    return self::FAILURE;
                }

                $identifier = (int) $input;
            }

            // Find the checkpoint first to display info
            $checkpoint = $service->find($identifier);

            if (! $checkpoint) {
                $this->error("✗ Checkpoint not found: {$identifier}");
                $this->line('');
                $this->line('List available checkpoints with:');
                $this->line('  php artisan tyro-checkpoint:list');

                return self::FAILURE;
            }

            // Show checkpoint info and ask for confirmation
            $this->warn('⚠ WARNING: This will replace your current database!');
            $this->line('');
            $this->line('Checkpoint to restore:');
            $this->line("  ID:      {$checkpoint->id}");
            $this->line("  Name:    {$checkpoint->name}");
            $this->line('  Driver:  '.$checkpoint->driver);
            if ($checkpoint->database) {
                $this->line('  Database:'.$checkpoint->database);
            }
            $this->line("  Size:    {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created: {$checkpoint->created_at->format('Y-m-d H:i:s')}");
            if ($checkpoint->encrypted) {
                $this->line('  Status:  <comment>Encrypted</comment>');
            }
            if ($checkpoint->note) {
                $this->line("  Note:    {$checkpoint->note}");
            }
            $this->line('');

            // Ask for confirmation
            if (! $this->confirm('Do you want to proceed?', false)) {
                $this->info('Restore cancelled.');

                return self::SUCCESS;
            }

            // Restore the checkpoint
            $this->info('Restoring checkpoint...');
            $service->restore((string) $identifier, (bool) $this->option('force'));

            // Success message
            $this->info("✓ Checkpoint '{$checkpoint->name}' restored successfully!");
            $this->line('');
            $this->line('Note: This checkpoint is still available and can be restored again.');
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
     * Format checkpoints data for table display.
     */
    private function formatTableRows($checkpoints, $service, bool $showDatabase = false): array {
        $rows = [];
        foreach ($checkpoints as $checkpoint) {
            $row = [
                $checkpoint->id,
                $this->formatTableName($checkpoint),
            ];

            if ($showDatabase) {
                $row[] = $this->formatDatabaseLabel($checkpoint);
            }

            $row[] = $this->formatTableNote($checkpoint);
            $row[] = $service->formatFileSize($checkpoint->size);
            $row[] = $this->formatTableCreated($checkpoint->created_at);
            $row[] = $this->formatTableEncrypted($checkpoint->encrypted);

            $rows[] = $row;
        }

        return $rows;
    }
}
