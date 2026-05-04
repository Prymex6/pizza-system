<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Backups ───────────────────────────────────────────────────────────────────

// Co godzinę: backup baz danych wszystkich aktywnych restauracji
Schedule::command('backup:tenants')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Co 8 godzin: pełny backup (landlord + wszyscy tenanci + manifest)
Schedule::command('backup:full')
    ->cron('0 */8 * * *')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// ── Loyalty ───────────────────────────────────────────────────────────────────

Schedule::command('loyalty:expire-points')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command('loyalty:monthly-bonus')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
