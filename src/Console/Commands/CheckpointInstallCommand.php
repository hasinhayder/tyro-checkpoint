<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * CheckpointInstallCommand
 * 
 * Handles the installation and setup of Tyro Checkpoint package.
 * 
 * Usage:
 *   php artisan tyro-checkpoint:install
 */
class CheckpointInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:install';

    /**
     * The console command description.
     */
    protected $description = 'Install Tyro Checkpoint package and setup database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║     Tyro Checkpoint Installation       ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');

        // Check if using SQLite
        $this->info('Checking database configuration...');
        
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        if ($driver !== 'sqlite') {
            $this->error('   ✗ Current database driver is not SQLite: ' . $driver);
            $this->error('   Tyro Checkpoint only supports SQLite databases.');
            $this->error('   Please configure SQLite in your .env file:');
            $this->line('');
            $this->line('   DB_CONNECTION=sqlite');
            $this->line('   DB_DATABASE=' . database_path('database.sqlite'));
            $this->line('');
            return self::FAILURE;
        }
        
        $databasePath = config("database.connections.{$connection}.database");
        
        if ($databasePath === ':memory:') {
            $this->error('   ✗ In-memory SQLite databases are not supported');
            $this->error('   Please configure a file-based SQLite database.');
            return self::FAILURE;
        }
        
        if (!File::exists($databasePath)) {
            $this->warn('   ⚠ Database file does not exist: ' . $databasePath);
            if ($this->confirm('Would you like to create it now?', true)) {
                File::put($databasePath, '');
                $this->info('   ✓ Database file created');
            } else {
                $this->error('   Installation cancelled.');
                return self::FAILURE;
            }
        } else {
            $this->info('   ✓ SQLite database configured correctly');
        }

        $this->info('');

        // Create checkpoint storage directory
        $this->info('Setting up checkpoint storage...');
        $checkpointPath = storage_path('tyro-checkpoints');
        
        if (!File::exists($checkpointPath)) {
            File::makeDirectory($checkpointPath, 0755, true);
            $this->info('   ✓ Created checkpoint storage directory: ' . $checkpointPath);
        } else {
            $this->info('   ✓ Checkpoint storage directory already exists');
        }

        // Create checkpoints.json if it doesn't exist
        $checkpointsFile = $checkpointPath . '/checkpoints.json';
        if (!File::exists($checkpointsFile)) {
            File::put($checkpointsFile, '[]');
            $this->info('   ✓ Created checkpoints metadata file: checkpoints.json');
        } else {
            $this->info('   ✓ Checkpoints metadata file already exists');
        }

        $this->info('');

        // Offer to create initial checkpoint
        if ($this->confirm('Would you like to create an initial checkpoint now?', true)) {
            $this->info('');
            $this->call('tyro-checkpoint:create', [
                'name' => 'initial_checkpoint',
            ]);
        }

        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║   Tyro Checkpoint installed!           ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');
        $this->info('  Quick Start Guide:');
        $this->info('  ─────────────────────────────────────────');
        $this->info('');
        $this->info('  Create a checkpoint:');
        $this->info('    <comment>php artisan tyro-checkpoint:create</comment>');
        $this->info('    <comment>php artisan tyro-checkpoint:create my_checkpoint</comment>');
        $this->info('');
        $this->info('  List all checkpoints:');
        $this->info('    <comment>php artisan tyro-checkpoint:list</comment>');
        $this->info('');
        $this->info('  Restore a checkpoint:');
        $this->info('    <comment>php artisan tyro-checkpoint:restore 1</comment>');
        $this->info('    <comment>php artisan tyro-checkpoint:restore my_checkpoint</comment>');
        $this->info('');
        $this->info('  Delete a checkpoint:');
        $this->info('    <comment>php artisan tyro-checkpoint:delete 1</comment>');
        $this->info('    <comment>php artisan tyro-checkpoint:delete my_checkpoint</comment>');
        $this->info('');
        $this->info('  Delete all checkpoints:');
        $this->info('    <comment>php artisan tyro-checkpoint:flush</comment>');
        $this->info('');
        $this->info('  Show version info:');
        $this->info('    <comment>php artisan tyro-checkpoint:version</comment>');
        $this->info('');
        $this->info('  💡 Tip: Checkpoints persist after restore - you can restore them multiple times!');
        $this->info('');

        return self::SUCCESS;
    }
}
