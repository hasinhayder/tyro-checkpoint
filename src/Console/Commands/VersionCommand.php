<?php

namespace HasinHayder\TyroCheckpoint\Console\Commands;

use Illuminate\Console\Command;

class VersionCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-checkpoint:version';

    /**
     * The console command description.
     */
    protected $description = 'Display the current Tyro Checkpoint version';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        $version = '1.6.0'; // MySQL & PostgreSQL support via driver abstraction

        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║        Tyro Checkpoint                 ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');
        $this->info("  Version: <comment>{$version}</comment>");
        $this->info('  Laravel: <comment>'.app()->version().'</comment>');
        $this->info('  PHP: <comment>'.PHP_VERSION.'</comment>');
        $this->info('');
        $this->info('  Documentation: <comment>https://github.com/hasinhayder/tyro-checkpoint</comment>');
        $this->info('  GitHub: <comment>https://github.com/hasinhayder/tyro-checkpoint</comment>');
        $this->info('');

        return self::SUCCESS;
    }
}

// 1.6.0 - MySQL & PostgreSQL support via driver abstraction
// 1.5.0 - Add auto checkpoints and checkpoint details command
// 1.4.0 - Add silent checkpoint creation option
// 1.3.1 - Laravel 13 support
// 1.3.0 - Laravel 13 support
// 1.2.0 - Security improvements and better delete UX
// 1.1.0 - Add encryption support for checkpoints
// 1.0.0 - Initial release
