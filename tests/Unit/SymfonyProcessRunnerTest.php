<?php

namespace HasinHayder\TyroCheckpoint\Tests\Unit;

use HasinHayder\TyroCheckpoint\Process\SymfonyProcessRunner;
use HasinHayder\TyroCheckpoint\TyroCheckpointServiceProvider;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;

class SymfonyProcessRunnerTest extends TestCase {
    protected function getPackageProviders($app): array {
        return [TyroCheckpointServiceProvider::class];
    }

    private function buildProcessTimeout(SymfonyProcessRunner $runner): ?float {
        $method = new ReflectionMethod($runner, 'buildProcess');
        $method->setAccessible(true);

        $process = $method->invoke($runner, ['echo', 'hi'], []);

        return $process->getTimeout();
    }

    public function test_timeout_is_read_from_config(): void {
        config(['tyro-checkpoint.process.timeout' => 1234]);

        $this->assertSame(1234.0, $this->buildProcessTimeout(new SymfonyProcessRunner));
    }

    public function test_timeout_falls_back_to_default_when_missing(): void {
        config(['tyro-checkpoint.process.timeout' => null]);

        $this->assertSame(600.0, $this->buildProcessTimeout(new SymfonyProcessRunner));
    }

    public function test_timeout_falls_back_to_default_for_invalid_value(): void {
        config(['tyro-checkpoint.process.timeout' => 'not-a-number']);

        $this->assertSame(600.0, $this->buildProcessTimeout(new SymfonyProcessRunner));
    }

    public function test_timeout_never_disables_the_guard_for_zero(): void {
        config(['tyro-checkpoint.process.timeout' => 0]);

        $this->assertSame(600.0, $this->buildProcessTimeout(new SymfonyProcessRunner));
    }
}
