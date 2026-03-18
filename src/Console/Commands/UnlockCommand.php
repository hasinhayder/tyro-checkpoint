<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * UnlockCommand
 *
 * Unlocks a checkpoint to allow deletion.
 *
 * Usage:
 *   php artisan tyro-checkpoint:unlock {identifier}
 */
class UnlockCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:unlock {identifier : The ID or name of the checkpoint to unlock}';

    /**
     * The console command description.
     */
    protected $description = 'Unlock a checkpoint to allow deletion';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        $identifier = $this->argument('identifier');

        try {
            // Attempt to unlock the checkpoint
            $checkpoint = $service->unlock($identifier);

            $this->info('✓ Checkpoint unlocked successfully!');
            $this->line('');
            $this->line("ID: {$checkpoint->id}");
            $this->line("Name: {$checkpoint->name}");
            $this->line('Status: Unlocked ✅');

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
