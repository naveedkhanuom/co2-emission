<?php

namespace App\Console\Commands;

use App\Console\Concerns\RequiresTenant;
use App\Models\ScheduledReport;
use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledReports extends Command
{
    use RequiresTenant;

    protected $signature = 'reports:run-scheduled';

    protected $description = 'Generate and email any scheduled reports that are due, then advance their next run date.';

    public function handle(ReportGenerationService $service): int
    {
        if (! $this->ensureTenantContext()) {
            return self::FAILURE;
        }

        // Runs inside one tenant, with no authenticated user and no company
        // bound. Dropping the company scope is deliberate and now means one
        // thing only: every COMPANY in this account, not every tenant. The
        // tenant boundary is the database itself, which this cannot cross.
        $due = ScheduledReport::withoutGlobalScope('company')
            ->where('status', 'active')
            ->whereNotNull('next_run_date')
            ->whereDate('next_run_date', '<=', today())
            ->with(['facility', 'department'])
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled reports are due.');

            return self::SUCCESS;
        }

        $this->info("Processing {$due->count()} due scheduled report(s).");

        foreach ($due as $report) {
            try {
                $service->emailScheduledReport($report);
                $this->line("  ✓ {$report->name}");
            } catch (\Throwable $e) {
                Log::error('Scheduled report failed', [
                    'scheduled_report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ {$report->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
