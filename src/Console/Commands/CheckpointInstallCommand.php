<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use HasinHayder\TyroCheckpoint\Drivers\DriverManager;
use HasinHayder\TyroCheckpoint\Exceptions\BinaryNotFoundException;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckpointInstallCommand extends Command {
    protected $signature = 'tyro-checkpoint:install';

    protected $description = 'Install Tyro Checkpoint package and setup database';

    public function handle(DriverManager $driverManager): int {
        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║     Tyro Checkpoint Installation       ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');

        $this->info('Checking database configuration...');

        $connection = $driverManager->connectionName();
        $driverName = config("database.connections.{$connection}.driver");

        if (! in_array($driverName, ['sqlite', 'mysql', 'pgsql'], true)) {
            $this->error("   ✗ Unsupported database driver: {$driverName}");
            $this->error('   Tyro Checkpoint supports SQLite, MySQL, and PostgreSQL.');

            return self::FAILURE;
        }

        if ($driverName === 'sqlite') {
            if (! $this->setupSqlite($connection)) {
                return self::FAILURE;
            }
        } else {
            if (! $this->setupServerDb($connection, $driverManager)) {
                return self::FAILURE;
            }
        }

        $this->info('');

        $this->info('Setting up checkpoint storage...');
        $checkpointPath = storage_path('tyro-checkpoints');

        if (! File::exists($checkpointPath)) {
            File::makeDirectory($checkpointPath, 0755, true);
            $this->info('   ✓ Created checkpoint storage directory: '.$checkpointPath);
        } else {
            $this->info('   ✓ Checkpoint storage directory already exists');
        }

        $checkpointsFile = $checkpointPath.'/checkpoints.json';

        if (! File::exists($checkpointsFile)) {
            File::put($checkpointsFile, '[]');
            $this->info('   ✓ Created checkpoints metadata file: checkpoints.json');
        } else {
            $this->info('   ✓ Checkpoints metadata file already exists');
        }

        $this->info('');

        if ($this->confirm('Would you like to create an initial checkpoint now?', true)) {
            $this->info('');
            $this->call('tyro-checkpoint:create', [
                'name' => 'initial_checkpoint',
                '--silent' => true,
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
        $this->info('  View checkpoint details:');
        $this->info('    <comment>php artisan tyro-checkpoint:details 1</comment>');
        $this->info('    <comment>php artisan tyro-checkpoint:details my_checkpoint</comment>');
        $this->info('');
        $this->info('  Flag a checkpoint:');
        $this->info('    <comment>php artisan tyro-checkpoint:flag 1</comment>');
        $this->info('    <comment>php artisan tyro-checkpoint:unflag 1</comment>');
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

    private function setupSqlite(string $connection): bool {
        $databasePath = config("database.connections.{$connection}.database");

        if ($databasePath === ':memory:') {
            $this->error('   ✗ In-memory SQLite databases are not supported');
            $this->error('   Please configure a file-based SQLite database.');

            return false;
        }

        if (! File::exists($databasePath)) {
            $this->warn('   ⚠ Database file does not exist: '.$databasePath);

            if ($this->confirm('Would you like to create it now?', true)) {
                File::put($databasePath, '');
                $this->info('   ✓ Database file created');
            } else {
                $this->error('   Installation cancelled.');

                return false;
            }
        } else {
            $this->info('   ✓ SQLite database configured correctly');
        }

        return true;
    }

    private function setupServerDb(string $connection, DriverManager $driverManager): bool {
        try {
            $driver = $driverManager->driverForName(
                config("database.connections.{$connection}.driver"),
                $connection,
            );
            $driver->assertReady();
        } catch (BinaryNotFoundException $e) {
            $this->error("   ✗ {$e->getMessage()}");

            return false;
        } catch (CheckpointException $e) {
            $this->error("   ✗ {$e->getMessage()}");

            return false;
        }

        $driverName = ucfirst(config("database.connections.{$connection}.driver"));
        $this->info("   ✓ {$driverName} database configured correctly");

        return true;
    }
}
