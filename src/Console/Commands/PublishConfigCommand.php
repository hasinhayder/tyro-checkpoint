<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * PublishConfigCommand
 *
 * Publishes the Tyro Checkpoint configuration file to the application's config directory.
 *
 * Usage:
 *   php artisan tyro-checkpoint:publish-config
 */
class PublishConfigCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:publish-config';

    /**
     * The console command description.
     */
    protected $description = 'Publish the Tyro Checkpoint configuration file';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        $sourcePath = __DIR__.'/../../config/checkpoint.php';
        $destinationPath = config_path('checkpoint.php');

        if (! File::exists($sourcePath)) {
            $this->error("Configuration file not found at: {$sourcePath}");

            return self::FAILURE;
        }

        if (File::exists($destinationPath)) {
            if (! $this->confirm('Configuration file already exists. Overwrite?')) {
                $this->info('Publication cancelled.');

                return self::SUCCESS;
            }
        }

        File::copy($sourcePath, $destinationPath);

        $this->info('');
        $this->info('  <fg=green>✓</> Tyro Checkpoint configuration file published successfully!');
        $this->info('');
        $this->line("  Location: <fg=cyan>{$destinationPath}</>");
        $this->info('');

        return self::SUCCESS;
    }
}
