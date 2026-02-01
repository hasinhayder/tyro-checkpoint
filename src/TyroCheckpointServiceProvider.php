<?php

namespace TyroLabs\TyroCheckpoint;

use Illuminate\Support\ServiceProvider;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointCreateCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointListCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointRestoreCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointDeleteCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointFlushCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointVersionCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointInstallCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\CheckpointAddNoteCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\LockCommand;
use TyroLabs\TyroCheckpoint\Console\Commands\UnlockCommand;

class TyroCheckpointServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register Artisan commands (only in console)
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckpointCreateCommand::class,
                CheckpointListCommand::class,
                CheckpointRestoreCommand::class,
                CheckpointDeleteCommand::class,
                CheckpointFlushCommand::class,
                CheckpointVersionCommand::class,
                CheckpointInstallCommand::class,
                CheckpointAddNoteCommand::class,
                LockCommand::class,
                UnlockCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the checkpoint service as a singleton
        $this->app->singleton(Services\CheckpointService::class, function ($app) {
            return new Services\CheckpointService();
        });
    }
}
