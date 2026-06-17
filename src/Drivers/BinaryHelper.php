<?php

namespace HasinHayder\TyroCheckpoint\Drivers;

use HasinHayder\TyroCheckpoint\Exceptions\BinaryNotFoundException;
use HasinHayder\TyroCheckpoint\Process\ProcessRunner;

class BinaryHelper {
    /**
     * Ensure a CLI binary is available by running --version.
     *
     * @throws BinaryNotFoundException
     */
    public static function ensureAvailable(
        ProcessRunner $processRunner,
        string $binary,
        string $label,
        string $installSuggestion,
    ): void {
        try {
            $processRunner->run([$binary, '--version']);
        } catch (\Exception $e) {
            $envVar = strtoupper("TYRO_CHECKPOINT_{$label}_BIN");

            throw new BinaryNotFoundException(
                "{$label} binary not found at '{$binary}'. {$installSuggestion} or set {$envVar} in your .env file."
            );
        }
    }
}
