<?php

namespace HasinHayder\TyroCheckpoint\Drivers;

use HasinHayder\TyroCheckpoint\Drivers\Concerns\ResolvesConnectionConfig;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Process\ProcessRunner;

class MysqlCheckpointDriver implements CheckpointDriver {
    use ResolvesConnectionConfig;

    public function __construct(
        private readonly string $connection,
        private readonly ProcessRunner $processRunner,
    ) {}

    public function name(): string {
        return 'mysql';
    }

    public function fileExtension(): string {
        return '.sql';
    }

    public function databaseName(): ?string {
        return $this->getConnectionDatabase($this->connection);
    }

    public function assertReady(): void {
        BinaryHelper::ensureAvailable($this->processRunner, $this->mysqldumpBin(), 'mysqldump', 'Install mysql-client');
        BinaryHelper::ensureAvailable($this->processRunner, $this->mysqlBin(), 'mysql', 'Install mysql-client');

        $database = $this->getConnectionDatabase($this->connection);

        if (! $database) {
            throw new CheckpointException(
                'MySQL database name is not configured. Set DB_DATABASE in your .env file.'
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
        $this->processRunner->runWithInputFile(
            $this->buildRestoreCommand(),
            $path,
            $this->buildEnv(),
        );
    }

    private function buildDumpCommand(): array {
        $command = [$this->mysqldumpBin()];

        $socket = $this->getConnectionSocket($this->connection);

        if ($socket) {
            $command[] = "--socket={$socket}";
        } else {
            $host = $this->getConnectionHost($this->connection);

            if ($host) {
                $command[] = "--host={$host}";
            }

            $port = $this->getConnectionPort($this->connection);

            if ($port) {
                $command[] = "--port={$port}";
            }
        }

        $user = $this->getConnectionUsername($this->connection);

        if ($user) {
            $command[] = "--user={$user}";
        }

        $command[] = '--single-transaction';
        $command[] = '--add-drop-table';
        $command[] = '--add-drop-trigger';
        $command[] = '--routines';
        $command[] = '--triggers';
        $command[] = '--events';
        $command[] = '--no-tablespaces';

        $charset = $this->getConnectionCharset($this->connection);
        $command[] = "--default-character-set={$charset}";

        $gtidPurged = config('tyro-checkpoint.mysql.gtid_purged', 'OFF');
        $command[] = "--set-gtid-purged={$gtidPurged}";

        $database = $this->getConnectionDatabase($this->connection);
        $command[] = $database;

        return $command;
    }

    private function buildRestoreCommand(): array {
        $command = [$this->mysqlBin()];

        $socket = $this->getConnectionSocket($this->connection);

        if ($socket) {
            $command[] = "--socket={$socket}";
        } else {
            $host = $this->getConnectionHost($this->connection);

            if ($host) {
                $command[] = "--host={$host}";
            }

            $port = $this->getConnectionPort($this->connection);

            if ($port) {
                $command[] = "--port={$port}";
            }
        }

        $user = $this->getConnectionUsername($this->connection);

        if ($user) {
            $command[] = "--user={$user}";
        }

        $database = $this->getConnectionDatabase($this->connection);
        $command[] = $database;

        return $command;
    }

    private function buildEnv(): array {
        $password = $this->getConnectionPassword($this->connection);

        if ($password !== null) {
            return ['MYSQL_PWD' => $password];
        }

        return [];
    }

    private function mysqldumpBin(): string {
        return config('tyro-checkpoint.binaries.mysqldump', 'mysqldump');
    }

    private function mysqlBin(): string {
        return config('tyro-checkpoint.binaries.mysql', 'mysql');
    }
}
