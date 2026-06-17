<?php

namespace HasinHayder\TyroCheckpoint\Tests\Unit;

use HasinHayder\TyroCheckpoint\Console\Commands\Concerns\FormatsCheckpointTables;
use HasinHayder\TyroCheckpoint\Models\Checkpoint;
use Orchestra\Testbench\TestCase;

/**
 * Fixture exposing the trait's private helpers for testing.
 */
class TableFormatterStub {
    use FormatsCheckpointTables;

    public function label($checkpoint): string {
        return $this->formatDatabaseLabel($checkpoint);
    }

    public function note($checkpoint): string {
        return $this->formatTableNote($checkpoint);
    }

    public function mixed($checkpoints): bool {
        return $this->isMixedDatabases($checkpoints);
    }
}

class FormatsCheckpointTablesTest extends TestCase {
    protected function getEnvironmentSetUp($app): void {
        $app['config']->set('tyro-checkpoint.auto_checkpoint.name_prefix', 'auto');
    }

    private function makeCheckpoint(string $driver, ?string $database): Checkpoint {
        return new Checkpoint([
            'id' => 1,
            'name' => 'test',
            'path' => '/tmp/test',
            'size' => 10,
            'created_at' => '2026-06-17 10:00:00',
            'driver' => $driver,
            'database' => $database,
        ]);
    }

    public function test_sqlite_label_is_plain_regardless_of_path(): void {
        $f = new TableFormatterStub;

        $this->assertSame('SQLite', $f->label($this->makeCheckpoint('sqlite', 'app.sqlite')));
        $this->assertSame('SQLite', $f->label($this->makeCheckpoint('sqlite', null)));
    }

    public function test_mysql_label_includes_database_name(): void {
        $f = new TableFormatterStub;

        $this->assertSame('MySQL (tyro_app_dev)', $f->label($this->makeCheckpoint('mysql', 'tyro_app_dev')));
    }

    public function test_postgres_label_includes_database_name(): void {
        $f = new TableFormatterStub;

        $this->assertSame('PG (legacy_db)', $f->label($this->makeCheckpoint('pgsql', 'legacy_db')));
    }

    public function test_single_database_is_not_mixed(): void {
        $f = new TableFormatterStub;

        $checkpoints = [
            $this->makeCheckpoint('sqlite', 'app.sqlite'),
            $this->makeCheckpoint('sqlite', null), // legacy sqlite still reads as "SQLite"
        ];

        $this->assertFalse($f->mixed($checkpoints));
    }

    public function test_two_mysql_databases_are_mixed(): void {
        $f = new TableFormatterStub;

        $checkpoints = [
            $this->makeCheckpoint('mysql', 'tyro_app_dev'),
            $this->makeCheckpoint('mysql', 'tyro_legacy'),
        ];

        $this->assertTrue($f->mixed($checkpoints));
    }

    public function test_sqlite_and_mysql_are_mixed(): void {
        $f = new TableFormatterStub;

        $checkpoints = [
            $this->makeCheckpoint('sqlite', 'app.sqlite'),
            $this->makeCheckpoint('mysql', 'tyro_app_dev'),
        ];

        $this->assertTrue($f->mixed($checkpoints));
    }

    public function test_auto_created_note_collapses_in_tables(): void {
        $f = new TableFormatterStub;

        $auto = new Checkpoint([
            'id' => 2,
            'name' => 'auto_2026_06_17_100000_migrate',
            'path' => '/tmp/auto',
            'size' => 10,
            'created_at' => '2026-06-17 10:00:00',
            'note' => 'Auto-created before running `php artisan migrate`.',
        ]);

        $this->assertSame('Auto-created', $f->note($auto));
    }

    public function test_manual_note_is_shown_in_tables(): void {
        $f = new TableFormatterStub;

        $manual = new Checkpoint([
            'id' => 3,
            'name' => 'before_changes',
            'path' => '/tmp/before',
            'size' => 10,
            'created_at' => '2026-06-17 10:00:00',
            'note' => 'Clean baseline before feature work',
        ]);

        $this->assertSame('Clean baseline before feature work', $f->note($manual));
    }
}
