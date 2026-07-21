<?php

namespace App\Jobs;

use App\Exports\EmissionsSummaryExport;
use App\Models\ExportJob;
use App\Services\ReportGenerationService;
use App\Support\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Generates the file for an ExportJob row and records the result. Reuses
 * ReportGenerationService so exports match the on-demand report downloads.
 */
class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $exportJobId)
    {
    }

    public function handle(ReportGenerationService $service): void
    {
        $job = ExportJob::find($this->exportJobId);
        if (!$job) {
            return;
        }

        $job->update(['status' => 'processing']);

        try {
            $filters = is_array($job->filters) ? $job->filters : [];
            $summary = $service->summaryFromFilters((int) $job->company_id, $filters, $job->name);
            $base = 'exports/' . Str::slug($job->name ?: 'export') . '-' . $job->id;

            switch ($job->format) {
                case 'pdf':
                    $path = $base . '.pdf';
                    Storage::put($path, $service->pdfFromSummary($summary)->output());
                    break;

                case 'excel':
                    $path = $base . '.xlsx';
                    Excel::store(new EmissionsSummaryExport($summary), $path, null, ExcelWriter::XLSX);
                    break;

                case 'csv':
                    $path = $base . '.csv';
                    Excel::store(new EmissionsSummaryExport($summary), $path, null, ExcelWriter::CSV);
                    break;

                default:
                    // pptx / png are not supported yet — fail explicitly rather
                    // than leaving the job stuck in "processing".
                    throw new \RuntimeException("Export format '{$job->format}' is not supported yet.");
            }

            $job->update([
                'status'       => 'completed',
                'file_path'    => $path,
                'file_size'    => $this->humanSize(Storage::size($path)),
                'completed_at' => now(),
            ]);

            Notifier::exportReady($job->created_by, $job->name ?: 'Export', route('reports.exports.download', $job->id));
        } catch (\Throwable $e) {
            $job->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Export job failed', [
                'export_job_id' => $job->id,
                'error'         => $e->getMessage(),
            ]);

            Notifier::exportFailed($job->created_by, $job->name ?: 'Export');
        }
    }

    /**
     * Human-readable file size (the column stores a label like "24.5 KB").
     */
    protected function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }
}
