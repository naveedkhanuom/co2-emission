<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledReports extends Command
{
    protected $signature = 'reports:run-scheduled';

    protected $description = 'Generate and email any scheduled reports that are due, then advance their next run date.';

    public function handle(ReportGenerationService $service): int
    {
        // Runs from the console (no authenticated user / no company bound). The
        // global company scope leaves unauthenticated queries unscoped, but be
        // explicit: this command processes every tenant's due reports on purpose.
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
