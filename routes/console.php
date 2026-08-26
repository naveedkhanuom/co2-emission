<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Both of these operate on client data, which lives in per-tenant databases,
// so they are wrapped in tenants:each — it runs them once inside every ACTIVE
// tenant. Scheduling them bare would run them against the central database,
// where a suspended account's reports would still go out and no live client
// would be scanned at all. Both commands now refuse to run without a tenant
// (see App\Console\Concerns\RequiresTenant) so that mistake fails loudly.
//
// Requires the system cron to run `php artisan schedule:run` every minute.

// Generate & email any scheduled reports that are due.
Schedule::command('tenants:each reports:run-scheduled')->dailyAt('07:00');

// Scan every company for emission spikes / data-quality drops and notify them.
// Idempotent, so a daily cadence is safe.
Schedule::command('tenants:each anomalies:scan')->dailyAt('06:00');
