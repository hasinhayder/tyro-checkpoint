<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * UnflagCommand
 *
 * Removes the flag from a checkpoint.
 *
 * Usage:
 *   php artisan tyro-checkpoint:unflag
 *   php artisan tyro-checkpoint:unflag {identifier}
 */
class UnflagCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:unflag {identifier? : The ID or name of the checkpoint to unflag}';

    /**
     * The console command description.
     */
    protected $description = 'Remove the flag from a checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        $identifier = $this->argument('identifier') ?: $this->ask('Enter checkpoint ID or name to unflag');

        if (! $identifier) {
            $this->error('✗ No checkpoint selected.');

            return self::FAILURE;
        }

        try {
            $checkpoint = $service->unflag((string) $identifier);

            $this->info('✓ Checkpoint unflagged successfully!');
            $this->line('');
            $this->line("ID: {$checkpoint->id}");
            $this->line("Name: {$checkpoint->name}");
            $this->line('Status: Unflagged');

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
