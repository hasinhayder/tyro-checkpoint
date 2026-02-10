<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CheckpointGenerateKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:generate-key';

    /**
     * The console command description.
     */
    protected $description = 'Generate a new encryption key for database checkpoints';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = base_path('.env');
        
        if (File::exists($path)) {
            $content = File::get($path);
            if (Str::contains($content, 'TYRO_CHECKPOINT_ENCRYPTION_KEY=')) {
                $this->warn('⚠ WARNING: An encryption key already exists in your .env file.');
                $this->line('  Replacing it will make previously encrypted checkpoints impossible to restore.');
                $this->line('');
                
                if (!$this->confirm('Do you really want to generate a new key and replace the existing one?', false)) {
                    $this->info('Operation cancelled.');
                    return self::SUCCESS;
                }
            }
        }

        $key = Str::random(32);

        if ($this->writeNewEnvironmentFileWith($key)) {
            $this->info("✓ Encryption key generated and saved to .env file successfully.");
            $this->line("  Key: {$key}");
        } else {
            $this->error("✗ Failed to save encryption key to .env file.");
            $this->line("  Please add the following line manually to your .env file:");
            $this->line("  TYRO_CHECKPOINT_ENCRYPTION_KEY=\"{$key}\"");
        }

        return self::SUCCESS;
    }

    /**
     * Write a new environment file with the given key.
     */
    protected function writeNewEnvironmentFileWith(string $key): bool
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            return false;
        }

        $content = File::get($path);

        if (Str::contains($content, 'TYRO_CHECKPOINT_ENCRYPTION_KEY=')) {
            $content = preg_replace(
                '/TYRO_CHECKPOINT_ENCRYPTION_KEY=.*/',
                "TYRO_CHECKPOINT_ENCRYPTION_KEY=\"{$key}\"",
                $content
            );
        } else {
            $content .= "\nTYRO_CHECKPOINT_ENCRYPTION_KEY=\"{$key}\"\n";
        }

        return File::put($path, $content) !== false;
    }
}
