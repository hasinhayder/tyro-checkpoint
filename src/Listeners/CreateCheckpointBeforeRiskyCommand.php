<?php

namespace HasinHayder\TyroCheckpoint\Listeners;

use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use Illuminate\Console\Events\CommandStarting;

class CreateCheckpointBeforeRiskyCommand {
    private static bool $createdInThisProcess = false;

    public function __construct(
        private readonly CheckpointService $checkpoints
    ) {}

    public function handle(CommandStarting $event): void {
        if (self::$createdInThisProcess || ! config('tyro-checkpoint.auto_checkpoint.enabled', false)) {
            return;
        }

        $command = $event->command;

        if (! is_string($command) || $command === '' || str_starts_with($command, 'tyro-checkpoint:')) {
            return;
        }

        if (! in_array($command, config('tyro-checkpoint.auto_checkpoint.commands', []), true)) {
            return;
        }

        self::$createdInThisProcess = true;

        try {
            $checkpoint = $this->checkpoints->create(
                $this->buildCheckpointName($command),
                "Auto-created before running `php artisan {$command}`.",
                (bool) config('tyro-checkpoint.auto_checkpoint.encrypt', false)
            );

            $event->output?->writeln(
                "<info>✓ Auto checkpoint created before {$command}: {$checkpoint->name}</info>"
            );
        } catch (CheckpointException $e) {
            $event->output?->writeln(
                "<error>✗ Failed to create auto checkpoint before {$command}: {$e->getMessage()}</error>"
            );

            if (config('tyro-checkpoint.auto_checkpoint.stop_on_failure', true)) {
                throw $e;
            }
        }
    }

    private function buildCheckpointName(string $command): string {
        $prefix = (string) config('tyro-checkpoint.auto_checkpoint.name_prefix', 'auto');
        $safeCommand = preg_replace('/[^a-zA-Z0-9_-]+/', '_', str_replace(':', '_', $command));

        return trim($prefix, '_').'_'.date('Y_m_d_His').'_'.$safeCommand;
    }
}
