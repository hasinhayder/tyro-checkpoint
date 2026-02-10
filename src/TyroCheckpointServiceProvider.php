<?php

namespace HasinHayder\TyroCheckpoint;

use Illuminate\Support\ServiceProvider;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointCreateCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointListCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointRestoreCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointDeleteCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointFlushCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointVersionCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointInstallCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointAddNoteCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\LockCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\UnlockCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointGenerateKeyCommand;

class TyroCheckpointServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/tyro-checkpoint.php' => config_path('tyro-checkpoint.php'),
        ], 'config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/tyro-checkpoint.php',
            'tyro-checkpoint'
        );

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
                CheckpointGenerateKeyCommand::class,
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
