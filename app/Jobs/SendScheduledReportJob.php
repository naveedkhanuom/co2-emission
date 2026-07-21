<?php

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Services\ReportGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generates and emails a scheduled report off the request thread. Building the
 * PDF/Excel is heavy, so running it inline would hang the "Run Now" request (and
 * risk a timeout) for large inventories. Mirrors ProcessExportJob.
 */
class SendScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $scheduledReportId)
    {
    }

    public function handle(ReportGenerationService $service): void
    {
        // The worker has no request/company context, so bypass the tenant scope
        // and load by id (the dispatcher already authorised the report).
        $report = ScheduledReport::withoutGlobalScope('company')->find($this->scheduledReportId);
        if (! $report) {
            return;
        }

        try {
            $service->emailScheduledReport($report);
        } catch (\Throwable $e) {
            Log::error('SendScheduledReportJob failed', [
                'scheduled_report_id' => $this->scheduledReportId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // let the queue mark it failed / retry per config
        }
    }
}
