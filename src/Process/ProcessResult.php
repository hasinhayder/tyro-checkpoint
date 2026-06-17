<?php

namespace HasinHayder\TyroCheckpoint\Process;

final readonly class ProcessResult {
    public function __construct(
        public int $exitCode,
        public string $output,
        public string $errorOutput,
    ) {}
}
