<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Command;

/**
 * FlagCommand
 *
 * Flags a checkpoint for attention.
 *
 * Usage:
 *   php artisan tyro-checkpoint:flag
 *   php artisan tyro-checkpoint:flag {identifier}
 */
class FlagCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:flag {identifier? : The ID or name of the checkpoint to flag}';

    /**
     * The console command description.
     */
    protected $description = 'Flag a checkpoint for attention';

    /**
     * Execute the console command.
     */
    public function handle(CheckpointService $service): int {
        $identifier = $this->argument('identifier') ?: $this->ask('Enter checkpoint ID or name to flag');

        if (! $identifier) {
            $this->error('✗ No checkpoint selected.');

            return self::FAILURE;
        }

        try {
            $checkpoint = $service->flag((string) $identifier);

            $this->info('✓ Checkpoint flagged successfully!');
            $this->line('');
            $this->line("ID: {$checkpoint->id}");
            $this->line("Name: {$checkpoint->name}");
            $this->line('Status: Flagged 🚩');

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
