<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Generate & email any scheduled reports that are due. Requires the system cron
// to run `php artisan schedule:run` every minute.
Schedule::command('reports:run-scheduled')->dailyAt('07:00');

// Scan every company for emission spikes / data-quality drops and notify them.
// Idempotent, so a daily cadence is safe.
Schedule::command('anomalies:scan')->dailyAt('06:00');
