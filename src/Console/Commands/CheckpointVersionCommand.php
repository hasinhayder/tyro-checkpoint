<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * CheckpointVersionCommand
 * 
 * Displays the current version of Tyro Checkpoint and system information.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:version
 */
class CheckpointVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:version';

    /**
     * The console command description.
     */
    protected $description = 'Display the current version of Tyro Checkpoint';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $version = "1.0.0";
        
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║        Tyro Checkpoint                 ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');
        $this->info("  Version: <comment>{$version}</comment>");
        $this->info('  Laravel: <comment>' . app()->version() . '</comment>');
        $this->info('  PHP: <comment>' . PHP_VERSION . '</comment>');
        $this->info('');
        $this->info('  Database:');
        
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $databasePath = config("database.connections.{$connection}.database");
        
        $this->info("  - Driver: <comment>{$driver}</comment>");
        
        if ($driver === 'sqlite') {
            $this->info('  - Status: <comment>✓ SQLite configured correctly</comment>');
            if ($databasePath && $databasePath !== ':memory:' && File::exists($databasePath)) {
                $size = File::size($databasePath);
                $this->info('  - Database Size: <comment>' . $this->formatFileSize($size) . '</comment>');
            }
        } else {
            $this->warn('  - Status: <comment>⚠ Not using SQLite (Tyro Checkpoint only supports SQLite)</comment>');
        }
        
        $this->info('');
        $this->info('  Storage:');
        $checkpointPath = storage_path('tyro-checkpoints');
        
        if (File::exists($checkpointPath)) {
            $files = File::files($checkpointPath);
            $totalSize = array_reduce($files, function ($carry, $file) {
                return $carry + $file->getSize();
            }, 0);
            
            $this->info('  - Checkpoints Directory: <comment>✓ exists</comment>');
            $this->info('  - Total Checkpoint Files: <comment>' . count($files) . '</comment>');
            $this->info('  - Total Storage Used: <comment>' . $this->formatFileSize($totalSize) . '</comment>');
        } else {
            $this->info('  - Checkpoints Directory: <comment>not created yet</comment>');
        }
        
        $this->info('');
        $this->info('  Documentation: <comment>https://github.com/tyrolabs/tyro-checkpoint</comment>');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Get human-readable file size.
     * 
     * @param int $bytes
     * @return string
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
