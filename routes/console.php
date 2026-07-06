<?php

use App\Models\Announcement;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('07:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('announcements:from-latest-commit {--type=update} {--title=Update}', function () {
    $result = Process::run(['git', 'log', '-1', '--pretty=%H%n%B']);

    if (! $result->successful()) {
        $this->error('Unable to read git log. Is this environment a git checkout?');
        $this->line($result->errorOutput());

        return self::FAILURE;
    }

    $out = trim($result->output());
    if ($out === '') {
        $this->error('No git output returned.');

        return self::FAILURE;
    }

    $lines = preg_split("/\r\n|\n|\r/", $out);
    $hash = array_shift($lines);
    $message = trim(implode("\n", $lines));

    Announcement::query()->create([
        'type' => (string) $this->option('type'),
        'title' => (string) $this->option('title'),
        'message' => 'Update deployed from latest commit.',
        'git_commit_hash' => $hash ?: null,
        'git_commit_message' => $message ?: null,
        'created_by_user_id' => null,
    ]);

    $this->info('Announcement created from latest commit.');

    return self::SUCCESS;
})->purpose('Create an update announcement from latest git commit');
