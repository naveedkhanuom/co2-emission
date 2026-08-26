<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Models\AnomalyAlert;
use App\Models\Company;
use App\Models\User;
use App\Services\AnomalyDetectionService;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scans every active company for emissions anomalies and notifies that
 * company's users. Idempotent: an anomaly is recorded in anomaly_alerts and
 * only notified the first time it's seen, so running daily never re-spams.
 *
 * Runs inside one tenant and scans every active company in that account.
 * Detection is scoped per company_id explicitly inside
 * AnomalyDetectionService, so subsidiaries are never compared against each
 * other. Schedule it across all clients with `tenants:each anomalies:scan`.
 */
class ScanAnomalies extends Command
{
    use RequiresTenant;

    protected $signature = 'anomalies:scan {--company= : Limit the scan to a single company id}';

    protected $description = 'Detect emission spikes, new sources and data-quality drops, and notify affected companies.';

    public function handle(AnomalyDetectionService $detector): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        $companies = Company::query()
            ->when($this->option('company'), fn ($q, $id) => $q->where('id', $id))
            ->where('is_active', true)
            ->get();

        if ($companies->isEmpty()) {
            $this->info('No active companies to scan.');

            return self::SUCCESS;
        }

        $totalNew = 0;

        foreach ($companies as $company) {
            try {
                $anomalies = $detector->detectForCompany($company->id);
            } catch (\Throwable $e) {
                Log::error('Anomaly scan failed for company', ['company_id' => $company->id, 'error' => $e->getMessage()]);
                $this->error("  ✗ {$company->name}: {$e->getMessage()}");

                continue;
            }

            if (empty($anomalies)) {
                continue;
            }

            // Recipients: the company's own members.
            $recipients = User::where('company_id', $company->id)->get();

            $newForCompany = 0;

            foreach ($anomalies as $a) {
                // firstOrCreate on the unique (company_id, anomaly_key) makes this
                // safe to run repeatedly — only a brand-new anomaly notifies.
                $alert = AnomalyAlert::firstOrCreate(
                    ['company_id' => $company->id, 'anomaly_key' => $a['key']],
                    [
                        'type' => $a['type'],
                        'period' => $a['period'],
                        'title' => $a['title'],
                        'message' => $a['message'],
                        'severity' => $a['severity'],
                        'metadata' => $a['metadata'] ?? null,
                        'notified_at' => now(),
                    ]
                );

                if (! $alert->wasRecentlyCreated) {
                    continue; // already alerted in a previous run
                }

                foreach ($recipients as $user) {
                    Notifier::anomaly($user, $a['title'], $a['message'], $a['severity'], $a['icon'], $a['url'] ?? null);
                }

                $newForCompany++;
                $totalNew++;
            }

            if ($newForCompany > 0) {
                $this->line("  ✓ {$company->name}: {$newForCompany} new anomaly alert(s) to {$recipients->count()} user(s)");
            }
        }

        $this->info("Anomaly scan complete. {$totalNew} new alert(s) raised across {$companies->count()} company(ies).");

        return self::SUCCESS;
    }
}
