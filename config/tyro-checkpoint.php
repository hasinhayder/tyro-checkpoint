<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tyro Checkpoint Storage Path
    |--------------------------------------------------------------------------
    |
    | This value determines where the checkpoint files will be stored.
    | You can set this value to a custom path using the TYRO_CHECKPOINT_STORAGE_PATH
    | environment variable. If no value is provided, it defaults to the
    | standard storage/tyro-checkpoints directory.
    |
    */

    'storage_path' => env('TYRO_CHECKPOINT_STORAGE_PATH', storage_path('tyro-checkpoints')),

    /*
    |--------------------------------------------------------------------------
    | Tyro Checkpoint Encryption Key
    |--------------------------------------------------------------------------
    |
    | This value is used by the Tyro Checkpoint command to encrypt your
    | database checkpoints. You can generate a new key using the
    | `tyro-checkpoint:generate-key` command.
    |
    */

    'encryption_key' => env('TYRO_CHECKPOINT_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Auto Checkpoints Before Risky Commands
    |--------------------------------------------------------------------------
    |
    | When enabled, Tyro Checkpoint listens for risky Artisan commands and
    | creates a checkpoint before the command runs. This is useful before
    | migrations, seeders, database wipes, and other destructive local
    | development commands.
    |
    */

    'auto_checkpoint' => [
        'enabled' => env('TYRO_CHECKPOINT_AUTO_ENABLED', false),

        'commands' => [
            'migrate',
            'migrate:fresh',
            'migrate:refresh',
            'migrate:reset',
            'migrate:rollback',
            'db:seed',
            'db:wipe',
        ],

        'name_prefix' => env('TYRO_CHECKPOINT_AUTO_NAME_PREFIX', 'auto'),
        'encrypt' => env('TYRO_CHECKPOINT_AUTO_ENCRYPT', false),
        'stop_on_failure' => env('TYRO_CHECKPOINT_AUTO_STOP_ON_FAILURE', true),
    ],
];
