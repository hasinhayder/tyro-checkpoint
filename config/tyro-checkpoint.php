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
];