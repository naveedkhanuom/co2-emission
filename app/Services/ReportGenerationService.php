<?php

namespace App\Services;

use App\Exports\EmissionsSummaryExport;
use App\Mail\ScheduledReportMail;
use App\Models\EmissionRecord;
use App\Models\Report;
use App\Models\ScheduledReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Builds emissions summary data and renders it to a downloadable file (PDF/Excel).
 *
 * Figures are summarised on demand from a company's EmissionRecords. Queries are
 * scoped explicitly by company_id so this works identically in web, queued-job,
 * and scheduler contexts (no reliance on request company state). Both saved
 * Reports and ad-hoc ExportJobs funnel through buildSummary().
 */
class ReportGenerationService
{
    /**
     * Summary for a saved Report (period + optional facility/department).
     */
    public function summary(Report $report): array
    {
        return $this->buildSummary($report->company_id, [
            'title'      => $report->report_name,
            'period'     => $report->period,
            'facility'   => $report->facility?->name,
            'department' => $report->department?->name,
            'year'       => $this->yearFromPeriod($report->period),
        ]);
    }

    /**
     * Summary from an ad-hoc filter set (used by export jobs / scheduler).
     */
    public function summaryFromFilters(int $companyId, array $filters, ?string $title = null): array
    {
        return $this->buildSummary($companyId, [
            'title'      => $title ?? 'Emissions Export',
            'period'     => $filters['period'] ?? null,
            'facility'   => $filters['facility'] ?? null,
            'department' => $filters['department'] ?? null,
            'year'       => $filters['year'] ?? $this->yearFromPeriod($filters['period'] ?? null),
        ]);
    }

    /**
     * Core summary builder.
     */
    protected function buildSummary(int $companyId, array $opts): array
    {
        $query = EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'active');

        if (!empty($opts['facility'])) {
            $query->where('facility', $opts['facility']);
        }
        if (!empty($opts['department'])) {
            $query->where('department', $opts['department']);
        }
        if (!empty($opts['year'])) {
            $query->whereYear('entry_date', (int) $opts['year']);
        }

        $records = $query->get();

        $scope1 = (float) $records->where('scope', 1)->sum('co2e_value');
        $scope2 = (float) $records->where('scope', 2)->sum('co2e_value'); // location-based
        $scope2Market = (float) $records->where('scope', 2)
            ->reduce(fn ($carry, $r) => $carry + $r->marketBasedCo2e(), 0.0);
        $scope3 = (float) $records->where('scope', 3)->sum('co2e_value');

        $bySource = $records->groupBy('emission_source')
            ->map(function ($group, $name) {
                return [
                    'source'  => $name !== '' ? $name : 'Unspecified',
                    'scope'   => $group->first()->scope,
                    'records' => $group->count(),
                    'co2e'    => round((float) $group->sum('co2e_value'), 4),
                ];
            })
            ->sortByDesc('co2e')
            ->values()
            ->all();

        return [
            'title'        => $opts['title'] ?? 'Emissions Report',
            'period'       => $opts['period'] ?? null,
            'facility'     => $opts['facility'] ?? null,
            'department'   => $opts['department'] ?? null,
            'generated_on' => now()->format('Y-m-d H:i'),
            'totals'       => [
                'scope1'        => round($scope1, 4),
                'scope2'        => round($scope2, 4),         // location-based
                'scope2_market' => round($scope2Market, 4),   // market-based
                'scope3'        => round($scope3, 4),
                'total'         => round($scope1 + $scope2 + $scope3, 4),
                'total_market'  => round($scope1 + $scope2Market + $scope3, 4),
            ],
            'record_count' => $records->count(),
            'by_source'    => $bySource,
        ];
    }

    /**
     * Render a saved report to a DomPDF instance.
     */
    public function pdf(Report $report)
    {
        return $this->pdfFromSummary($this->summary($report));
    }

    /**
     * Render any summary array to a DomPDF instance.
     */
    public function pdfFromSummary(array $summary)
    {
        return Pdf::loadView('reports.pdf', $summary)->setPaper('a4');
    }

    /**
     * Suggested download filename for the given extension.
     */
    public function filename(Report $report, string $ext): string
    {
        return Str::slug($report->report_name ?: 'report') . '-' . $report->id . '.' . $ext;
    }

    /**
     * Extract a 4-digit year from a free-text period label, if present.
     */
    public function yearFromPeriod(?string $period): ?int
    {
        return preg_match('/(20\d{2})/', (string) $period, $m) ? (int) $m[1] : null;
    }

    /**
     * Build and email one scheduled report to its recipients, then advance its
     * next run date. Shared by the scheduler command and the "Run Now" action so
     * the two paths can never diverge.
     */
    public function emailScheduledReport(ScheduledReport $report): void
    {
        $year = now()->year;

        $summary = $this->summaryFromFilters((int) $report->company_id, [
            'facility'   => $report->facility?->name,
            'department' => $report->department?->name,
            'year'       => $year,
            'period'     => (string) $year,
        ], $report->name);

        // Always attach a PDF; add Excel when the schedule requests it.
        $formats = is_array($report->formats) ? $report->formats : [];
        $files = [[
            'data' => $this->pdfFromSummary($summary)->output(),
            'name' => 'report.pdf',
            'mime' => 'application/pdf',
        ]];

        if (in_array('excel', $formats, true) || in_array('xlsx', $formats, true)) {
            $files[] = [
                'data' => Excel::raw(new EmissionsSummaryExport($summary), ExcelWriter::XLSX),
                'name' => 'report.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
        }

        $recipients = array_filter((array) ($report->recipients ?? []));
        if (!empty($recipients)) {
            Mail::to($recipients)->send(new ScheduledReportMail($report, $summary, $files));
        }

        $report->update([
            'last_run_date' => today(),
            'next_run_date' => $report->computeNextRunDate(),
        ]);
    }
}
