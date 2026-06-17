<?php

namespace HasinHayder\TyroCheckpoint\Process;

use HasinHayder\TyroCheckpoint\Exceptions\ProcessException;
use Symfony\Component\Process\Process;

class SymfonyProcessRunner implements ProcessRunner {
    public function run(array $command, ?string $stdin = null, array $env = []): ProcessResult {
        $process = $this->buildProcess($command, $env);

        if ($stdin !== null) {
            $process->setInput($stdin);
        }

        $exitCode = $process->run();

        if ($exitCode !== 0) {
            throw new ProcessException(
                "Command failed with exit code {$exitCode}: ".
                implode(' ', $command)."\n\n".
                ($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        return new ProcessResult(
            exitCode: $exitCode,
            output: $process->getOutput(),
            errorOutput: $process->getErrorOutput(),
        );
    }

    public function runWithOutputFile(array $command, string $outputPath, array $env = []): ProcessResult {
        $process = $this->buildProcess($command, $env);
        $handle = fopen($outputPath, 'wb');

        if ($handle === false) {
            throw new ProcessException("Failed to open output file: {$outputPath}");
        }

        try {
            $process->run(function ($type, $buffer) use ($handle): void {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } finally {
            fclose($handle);
        }

        $exitCode = $process->getExitCode() ?? -1;

        if ($exitCode !== 0) {
            throw new ProcessException(
                "Command failed with exit code {$exitCode}: ".
                implode(' ', $command)."\n\n".
                $process->getErrorOutput()
            );
        }

        return new ProcessResult(
            exitCode: $exitCode,
            output: '',
            errorOutput: $process->getErrorOutput(),
        );
    }

    public function runWithInputFile(array $command, string $inputFilePath, array $env = []): ProcessResult {
        $handle = fopen($inputFilePath, 'rb');

        if ($handle === false) {
            throw new ProcessException("Failed to open input file: {$inputFilePath}");
        }

        try {
            $process = $this->buildProcess($command, $env);
            $process->setInput($handle);
            $exitCode = $process->run();

            if ($exitCode !== 0) {
                throw new ProcessException(
                    "Command failed with exit code {$exitCode}: ".
                    implode(' ', $command)."\n\n".
                    ($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            return new ProcessResult(
                exitCode: $exitCode,
                output: $process->getOutput(),
                errorOutput: $process->getErrorOutput(),
            );
        } finally {
            fclose($handle);
        }
    }

    private function buildProcess(array $command, array $env): Process {
        $process = new Process($command);
        $process->setTimeout(600);

        if ($env !== []) {
            $process->setEnv(array_merge($_ENV, $_SERVER, $env));
        }

        return $process;
    }
}
