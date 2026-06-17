<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Console\Commands\Concerns\FormatsCheckpointTables;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * CheckpointEncryptCommand
 *
 * Encrypts an existing (unencrypted) database checkpoint in place.
 * The original unencrypted snapshot is removed and the metadata is updated
 * so the checkpoint remains restorable. No new checkpoint entry is added.
 *
 * Usage:
 *   php artisan tyro-checkpoint:encrypt
 *   php artisan tyro-checkpoint:encrypt 1
 *   php artisan tyro-checkpoint:encrypt my_checkpoint
 */
class CheckpointEncryptCommand extends Command {
    use FormatsCheckpointTables;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:encrypt {identifier? : Checkpoint ID or name to encrypt}';

    /**
     * The console command description.
     */
    protected $description = 'Encrypt an existing database checkpoint in place';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        try {
            // Get the identifier (ID or name)
            $identifier = $this->argument('identifier');

            // If no identifier provided, ask the user to select one
            if (! $identifier) {
                // Only show checkpoints which are not encrypted yet
                $checkpoints = $service->list()->reject(fn ($c) => $c->encrypted)->values();

                if ($checkpoints->isEmpty()) {
                    $this->info('No unencrypted checkpoints found.');

                    return self::SUCCESS;
                }

                $this->info('Unencrypted checkpoints:');
                $this->line('');

                // Display table of checkpoints
                $showDatabase = $this->isMixedDatabases($checkpoints);
                $headers = ['ID', 'Name'];
                if ($showDatabase) {
                    $headers[] = 'Database';
                }
                $headers = array_merge($headers, ['Note', 'Size', 'Created']);
                $rows = $this->formatTableRows($checkpoints, $service, $showDatabase);
                $this->table($headers, $rows);

                // Get available IDs for validation
                $availableIds = $checkpoints->pluck('id')->toArray();

                $this->line('');
                $input = $this->ask('Enter checkpoint ID to encrypt (0 to quit)');

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

            if ($checkpoint->encrypted) {
                // Idempotent: do not double-encrypt. Re-encrypting ciphertext
                // would make the checkpoint unrestorable, so treat this as a
                // successful no-op rather than an error.
                $this->info("✓ Checkpoint '{$checkpoint->name}' is already encrypted. Nothing to do.");

                return self::SUCCESS;
            }

            // Show checkpoint info and ask for confirmation
            $this->line('Checkpoint to encrypt:');
            $this->line("  ID:      {$checkpoint->id}");
            $this->line("  Name:    {$checkpoint->name}");
            $this->line('  Driver:  '.$checkpoint->driver);
            $this->line("  Size:    {$service->formatFileSize($checkpoint->size)}");
            $this->line("  Created: {$checkpoint->created_at->format('Y-m-d H:i:s')}");
            if ($checkpoint->note) {
                $this->line("  Note:    {$checkpoint->note}");
            }
            $this->line('');
            $this->line('The original unencrypted snapshot will be removed after encryption.');
            $this->line('The checkpoint will remain restorable and is auto-decrypted on restore.');
            $this->line('');

            // Ask for confirmation
            if (! $this->confirm('Do you want to proceed?', false)) {
                $this->info('Encrypt cancelled.');

                return self::SUCCESS;
            }

            // Encrypt the checkpoint in place
            $this->info('Encrypting checkpoint...');
            $encrypted = $service->encrypt((string) $identifier);

            // Success message
            $this->info("✓ Checkpoint '{$encrypted->name}' encrypted successfully!");
            $this->line('');
            $this->line("  ID:      {$encrypted->id}");
            $this->line("  Name:    {$encrypted->name}");
            $this->line('  Status:  <comment>Encrypted</comment>');
            $this->line("  Size:    {$service->formatFileSize($encrypted->size)}");
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
