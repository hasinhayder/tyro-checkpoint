<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * LockCommand
 *
 * Locks a checkpoint to prevent accidental deletion.
 *
 * Usage:
 *   php artisan tyro-checkpoint:lock {identifier}
 */
class LockCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:lock {identifier : The ID or name of the checkpoint to lock}';

    /**
     * The console command description.
     */
    protected $description = 'Lock a checkpoint to prevent deletion';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        $identifier = $this->argument('identifier');

        try {
            // Attempt to lock the checkpoint
            $checkpoint = $service->lock($identifier);

            $this->info('✓ Checkpoint locked successfully!');
            $this->line('');
            $this->line("ID: {$checkpoint->id}");
            $this->line("Name: {$checkpoint->name}");
            $this->line('Status: Locked 🔒');

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
