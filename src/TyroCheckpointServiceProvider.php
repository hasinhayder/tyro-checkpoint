<?php

namespace HasinHayder\TyroCheckpoint;

use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointAddNoteCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointCreateCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointDeleteCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointDetailsCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointFlushCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointGenerateKeyCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointInstallCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointListCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\CheckpointRestoreCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\FlagCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\LockCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\UnflagCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\UnlockCommand;
use HasinHayder\TyroCheckpoint\Console\Commands\VersionCommand;
use HasinHayder\TyroCheckpoint\Drivers\DriverManager;
use HasinHayder\TyroCheckpoint\Listeners\CreateCheckpointBeforeRiskyCommand;
use HasinHayder\TyroCheckpoint\Process\ProcessRunner;
use HasinHayder\TyroCheckpoint\Process\SymfonyProcessRunner;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TyroCheckpointServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/tyro-checkpoint.php' => config_path('tyro-checkpoint.php'),
        ], 'config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/tyro-checkpoint.php',
            'tyro-checkpoint'
        );

        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register Artisan commands (only in console)
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckpointCreateCommand::class,
                CheckpointListCommand::class,
                CheckpointDetailsCommand::class,
                CheckpointRestoreCommand::class,
                CheckpointDeleteCommand::class,
                CheckpointFlushCommand::class,
                VersionCommand::class,
                CheckpointInstallCommand::class,
                CheckpointAddNoteCommand::class,
                LockCommand::class,
                UnlockCommand::class,
                FlagCommand::class,
                UnflagCommand::class,
                CheckpointGenerateKeyCommand::class,
            ]);

            Event::listen(CommandStarting::class, CreateCheckpointBeforeRiskyCommand::class);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->singleton(ProcessRunner::class, fn () => new SymfonyProcessRunner);

        $this->app->singleton(DriverManager::class, fn ($app) => new DriverManager(
            $app->make(ProcessRunner::class)
        ));

        $this->app->singleton(Services\CheckpointService::class, function ($app) {
            return new Services\CheckpointService(
                $app->make(DriverManager::class)
            );
        });
    }
}
