<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MakeStoredFilesPrivate extends Command
{
    protected $signature = 'files:make-private {--dry-run : List what would move without moving}';

    protected $description = 'Move every file from the web-served public disk to the private disk so it is only reachable through the authenticated /storage route. Run once after deploying the storage hardening. Idempotent.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        $moved = 0;

        foreach ($public->allFiles() as $path) {
            if (str_ends_with($path, '.gitignore')) {
                continue;
            }

            if ($private->exists($path)) {
                $this->warn("skip (already on private disk): {$path}");

                continue;
            }

            if (! $dryRun) {
                $private->writeStream($path, $public->readStream($path));
                $public->delete($path);
            }

            $moved++;
            $this->line(($dryRun ? '[dry-run] ' : '').'moved: '.$path);
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Done. {$moved} file(s) moved to the private disk.");

        return self::SUCCESS;
    }
}
