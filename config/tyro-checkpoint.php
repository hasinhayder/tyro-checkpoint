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
];
