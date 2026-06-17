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
    | Tyro Checkpoint Target Connection
    |--------------------------------------------------------------------------
    |
    | Override which database connection to checkpoint. By default,
    | Tyro Checkpoint uses config('database.default'). Set this to
    | checkpoint a different connection (e.g. a named MySQL connection
    | while your default remains SQLite).
    |
    */

    'connection' => env('TYRO_CHECKPOINT_CONNECTION'),

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
    | Binary Paths
    |--------------------------------------------------------------------------
    |
    | Custom paths to the CLI tools used by MySQL and PostgreSQL drivers.
    | Leave as default unless your binaries are installed in a non-standard
    | location.
    |
    */

    'binaries' => [
        'mysql' => env('TYRO_CHECKPOINT_MYSQL_BIN', 'mysql'),
        'mysqldump' => env('TYRO_CHECKPOINT_MYSQLDUMP_BIN', 'mysqldump'),
        'psql' => env('TYRO_CHECKPOINT_PSQL_BIN', 'psql'),
        'pg_dump' => env('TYRO_CHECKPOINT_PG_DUMP_BIN', 'pg_dump'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Process Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds allowed for a single snapshot/restore operation
    | (mysqldump, mysql, pg_dump, psql). Large databases can take well over the
    | default 30s, so this is set generously. Increase it via the
    | TYRO_CHECKPOINT_PROCESS_TIMEOUT env var for very large databases.
    |
    */

    'process' => [
        'timeout' => env('TYRO_CHECKPOINT_PROCESS_TIMEOUT', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Driver Options
    |--------------------------------------------------------------------------
    */

    'mysql' => [
        'gtid_purged' => env('TYRO_CHECKPOINT_MYSQL_GTID_PURGED', 'OFF'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Driver Options
    |--------------------------------------------------------------------------
    |
    | single_transaction: wrap the entire restore in a transaction.
    |   May not be supported by all psql builds or for very large dumps.
    |   Disabled by default.
    |
    */

    'postgres' => [
        'single_transaction' => env('TYRO_CHECKPOINT_POSTGRES_SINGLE_TRANSACTION', false),
    ],

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
