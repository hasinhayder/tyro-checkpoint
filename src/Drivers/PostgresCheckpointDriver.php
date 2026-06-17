<?php

namespace HasinHayder\TyroCheckpoint\Drivers;

use HasinHayder\TyroCheckpoint\Drivers\Concerns\ResolvesConnectionConfig;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Process\ProcessRunner;

class PostgresCheckpointDriver implements CheckpointDriver {
    use ResolvesConnectionConfig;

    public function __construct(
        private readonly string $connection,
        private readonly ProcessRunner $processRunner,
    ) {}

    public function name(): string {
        return 'pgsql';
    }

    public function fileExtension(): string {
        return '.sql';
    }

    public function assertReady(): void {
        BinaryHelper::ensureAvailable($this->processRunner, $this->pgDumpBin(), 'pg_dump', 'Install postgresql-client');
        BinaryHelper::ensureAvailable($this->processRunner, $this->psqlBin(), 'psql', 'Install postgresql-client');

        $database = $this->getConnectionDatabase($this->connection);

        if (! $database) {
            throw new CheckpointException(
                'PostgreSQL database name is not configured. Set DB_DATABASE in your .env file.'
            );
        }
    }

    public function createSnapshot(string $path): void {
        $this->processRunner->runWithOutputFile(
            $this->buildDumpCommand(),
            $path,
            $this->buildEnv(),
        );
    }

    public function restoreSnapshot(string $path): void {
        $preludePath = sys_get_temp_dir().'/tyro_pg_prelude_'.uniqid().'.sql';

        try {
            $this->writePreludeFile($preludePath, $path);
            $this->processRunner->runWithInputFile(
                $this->buildRestoreCommand(),
                $preludePath,
                $this->buildEnv(),
            );
        } finally {
            if (is_file($preludePath)) {
                @unlink($preludePath);
            }
        }
    }

    private function writePreludeFile(string $preludePath, string $dumpPath): void {
        $preludeHandle = fopen($preludePath, 'wb');

        if ($preludeHandle === false) {
            throw new CheckpointException("Failed to create prelude file: {$preludePath}");
        }

        fwrite($preludeHandle, "SET session_replication_role = 'replica';\n");
        fclose($preludeHandle);

        $dumpHandle = fopen($dumpPath, 'rb');

        if ($dumpHandle === false) {
            throw new CheckpointException("Failed to read checkpoint file: {$dumpPath}");
        }

        $preludeHandle = fopen($preludePath, 'ab');

        if ($preludeHandle === false) {
            fclose($dumpHandle);
            throw new CheckpointException("Failed to append to prelude file: {$preludePath}");
        }

        stream_copy_to_stream($dumpHandle, $preludeHandle);
        fclose($dumpHandle);
        fclose($preludeHandle);
    }

    private function buildDumpCommand(): array {
        $command = [$this->pgDumpBin()];

        $host = $this->getConnectionHost($this->connection);

        if ($host) {
            $command[] = "--host={$host}";
        }

        $port = $this->getConnectionPort($this->connection);

        if ($port) {
            $command[] = "--port={$port}";
        }

        $user = $this->getConnectionUsername($this->connection);

        if ($user) {
            $command[] = "--username={$user}";
        }

        $command[] = '--clean';
        $command[] = '--if-exists';
        $command[] = '--no-owner';
        $command[] = '--no-privileges';
        $command[] = '--format=plain';

        $database = $this->getConnectionDatabase($this->connection);
        $command[] = $database;

        return $command;
    }

    private function buildRestoreCommand(): array {
        $command = [$this->psqlBin()];

        $host = $this->getConnectionHost($this->connection);

        if ($host) {
            $command[] = "--host={$host}";
        }

        $port = $this->getConnectionPort($this->connection);

        if ($port) {
            $command[] = "--port={$port}";
        }

        $user = $this->getConnectionUsername($this->connection);

        if ($user) {
            $command[] = "--username={$user}";
        }

        $command[] = '-v';
        $command[] = 'ON_ERROR_STOP=1';

        if (config('tyro-checkpoint.postgres.single_transaction', false)) {
            $command[] = '--single-transaction';
        }

        $database = $this->getConnectionDatabase($this->connection);
        $command[] = $database;

        return $command;
    }

    private function buildEnv(): array {
        $password = $this->getConnectionPassword($this->connection);

        if ($password !== null) {
            return ['PGPASSWORD' => $password];
        }

        return [];
    }

    private function pgDumpBin(): string {
        return config('tyro-checkpoint.binaries.pg_dump', 'pg_dump');
    }

    private function psqlBin(): string {
        return config('tyro-checkpoint.binaries.psql', 'psql');
    }
}
