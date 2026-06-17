<?php

namespace HasinHayder\TyroCheckpoint\Drivers\Concerns;

trait ResolvesConnectionConfig {
    protected function getConnectionConfig(string $connection): array {
        return config("database.connections.{$connection}", []);
    }

    protected function getConnectionHost(string $connection): ?string {
        return $this->getConnectionConfig($connection)['host'] ?? null;
    }

    protected function getConnectionPort(string $connection): ?string {
        $port = $this->getConnectionConfig($connection)['port'] ?? null;

        return $port ? (string) $port : null;
    }

    protected function getConnectionUsername(string $connection): ?string {
        return $this->getConnectionConfig($connection)['username'] ?? null;
    }

    protected function getConnectionPassword(string $connection): ?string {
        return $this->getConnectionConfig($connection)['password'] ?? null;
    }

    protected function getConnectionDatabase(string $connection): ?string {
        return $this->getConnectionConfig($connection)['database'] ?? null;
    }

    protected function getConnectionSocket(string $connection): ?string {
        return $this->getConnectionConfig($connection)['unix_socket'] ?? null;
    }

    protected function getConnectionCharset(string $connection): string {
        return $this->getConnectionConfig($connection)['charset'] ?? 'utf8mb4';
    }

    protected function getConnectionDriver(string $connection): string {
        return $this->getConnectionConfig($connection)['driver'] ?? '';
    }
}
