<?php

namespace HasinHayder\TyroCheckpoint\Tests\Unit;

use HasinHayder\TyroCheckpoint\Drivers\DriverManager;
use HasinHayder\TyroCheckpoint\Drivers\SqliteCheckpointDriver;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Models\Checkpoint;
use HasinHayder\TyroCheckpoint\Process\ProcessResult;
use HasinHayder\TyroCheckpoint\Process\ProcessRunner;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use HasinHayder\TyroCheckpoint\TyroCheckpointServiceProvider;
use Orchestra\Testbench\TestCase;

class DriverManagerTest extends TestCase {
    protected function getEnvironmentSetUp($app): void {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('tyro-checkpoint.storage_path', sys_get_temp_dir().'/tyro_checkpoint_tests');
        $app['config']->set('tyro-checkpoint.binaries', [
            'mysql' => 'mysql',
            'mysqldump' => 'mysqldump',
            'psql' => 'psql',
            'pg_dump' => 'pg_dump',
        ]);
    }

    protected function getPackageProviders($app): array {
        return [TyroCheckpointServiceProvider::class];
    }

    /** @test */
    public function it_resolves_sqlite_driver_for_sqlite_connection(): void {
        $manager = new DriverManager(new class implements ProcessRunner {
            public function run(array $command, ?string $stdin = null, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithOutputFile(array $command, string $outputPath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithInputFile(array $command, string $inputFilePath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }
        });

        $driver = $manager->driver();

        $this->assertInstanceOf(SqliteCheckpointDriver::class, $driver);
        $this->assertSame('sqlite', $driver->name());
        $this->assertSame('.sqlite', $driver->fileExtension());
    }

    /** @test */
    public function it_throws_for_unsupported_driver(): void {
        $this->expectException(CheckpointException::class);
        $this->expectExceptionMessage('Unsupported database driver');

        $manager = new DriverManager(new class implements ProcessRunner {
            public function run(array $command, ?string $stdin = null, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithOutputFile(array $command, string $outputPath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithInputFile(array $command, string $inputFilePath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }
        });

        $manager->driverForName('mongodb', 'mongodb');
    }

    /** @test */
    public function it_resolves_connection_name_from_config(): void {
        config(['tyro-checkpoint.connection' => 'mysql_alt']);

        $manager = new DriverManager(new class implements ProcessRunner {
            public function run(array $command, ?string $stdin = null, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithOutputFile(array $command, string $outputPath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithInputFile(array $command, string $inputFilePath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }
        });

        $this->assertSame('mysql_alt', $manager->connectionName());
    }

    /** @test */
    public function it_defaults_to_database_default_when_no_override(): void {
        config(['tyro-checkpoint.connection' => null]);
        config(['database.default' => 'pgsql']);

        $manager = new DriverManager(new class implements ProcessRunner {
            public function run(array $command, ?string $stdin = null, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithOutputFile(array $command, string $outputPath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }

            public function runWithInputFile(array $command, string $inputFilePath, array $env = []): ProcessResult {
                return new ProcessResult(0, '', '');
            }
        });

        $this->assertSame('pgsql', $manager->connectionName());
    }

    /** @test */
    public function checkpoint_model_defaults_driver_to_sqlite(): void {
        $checkpoint = new Checkpoint([
            'id' => 1,
            'name' => 'test',
            'path' => '/tmp/test.sqlite',
            'size' => 1024,
            'created_at' => '2026-06-17 10:00:00',
        ]);

        $this->assertSame('sqlite', $checkpoint->driver);
    }

    /** @test */
    public function checkpoint_model_preserves_explicit_driver(): void {
        $checkpoint = new Checkpoint([
            'id' => 1,
            'name' => 'test',
            'path' => '/tmp/test.sql',
            'size' => 1024,
            'created_at' => '2026-06-17 10:00:00',
            'driver' => 'mysql',
        ]);

        $this->assertSame('mysql', $checkpoint->driver);

        $array = $checkpoint->toArray();

        $this->assertArrayHasKey('driver', $array);
        $this->assertSame('mysql', $array['driver']);
    }

    /** @test */
    public function process_result_stores_exit_code_and_output(): void {
        $result = new ProcessResult(
            exitCode: 0,
            output: 'dump output',
            errorOutput: '',
        );

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('dump output', $result->output);
        $this->assertSame('', $result->errorOutput);
    }

    /** @test */
    public function checkpoint_service_is_resolvable(): void {
        $service = $this->app->make(CheckpointService::class);

        $this->assertInstanceOf(CheckpointService::class, $service);
    }

    /** @test */
    public function driver_interface_methods_exist(): void {
        $driver = new SqliteCheckpointDriver('sqlite');

        $this->assertSame('sqlite', $driver->name());
        $this->assertSame('.sqlite', $driver->fileExtension());
    }
}
