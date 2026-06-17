<?php

namespace HasinHayder\TyroCheckpoint\Process;

use HasinHayder\TyroCheckpoint\Exceptions\ProcessException;

interface ProcessRunner {
    /**
     * Run a command.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $env  Extra environment variables (e.g. MYSQL_PWD, PGPASSWORD)
     *
     * @throws ProcessException
     */
    public function run(array $command, ?string $stdin = null, array $env = []): ProcessResult;

    /**
     * Run a command, streaming stdout to a file.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     *
     * @throws ProcessException
     */
    public function runWithOutputFile(array $command, string $outputPath, array $env = []): ProcessResult;

    /**
     * Run a command, streaming a file as stdin.
     *
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     *
     * @throws ProcessException
     */
    public function runWithInputFile(array $command, string $inputFilePath, array $env = []): ProcessResult;
}
