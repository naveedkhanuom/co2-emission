<?php

namespace App\Http\Controllers;

use App\Models\ImportHistory;
use App\Models\User;
use App\Imports\EmissionsImport;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-import-history|create-import-history|edit-import-history|delete-import-history', ['only' => ['index', 'getData', 'getStatistics', 'getTrendData', 'getStatusDistribution', 'show', 'getLogs', 'getImportSources', 'bulkAction', 'downloadFile', 'retry', 'cancel', 'exportHistory', 'downloadReport', 'exportLogsBulk']]);
        $this->middleware('permission:delete-import-history', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('import_history.index');
    }

    public function getData(Request $request)
    {
        $query = ImportHistory::with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('date_range') && $request->date_range != 'all') {
            $dateRange = $request->date_range;
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subDays(7));
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subDays(30));
                    break;
                case 'quarter':
                    $query->where('created_at', '>=', Carbon::now()->subMonths(3));
                    break;
                case 'year':
                    $query->where('created_at', '>=', Carbon::now()->subYear());
                    break;
            }
        }

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type != 'all') {
            if ($request->type === 'csv') {
                $query->whereIn('import_type', ['csv', 'excel']);
            } else {
                $query->where('import_type', $request->type);
            }
        }

        if ($request->has('user') && $request->user != 'all') {
            if ($request->user === 'system') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $request->user);
            }
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function ($row) {
                return '<div class="form-check">
                    <input class="form-check-input row-checkbox" type="checkbox" value="' . $row->id . '">
                </div>';
            })
            ->editColumn('import_id', function ($row) {
                return '<code class="text-primary">' . $row->import_id . '</code>';
            })
            ->editColumn('created_at', function ($row) {
                return '<div class="fw-bold">' . $row->created_at->format('Y-m-d') . '</div>
                        <div class="text-muted small">' . $row->created_at->format('H:i:s') . '</div>';
            })
            ->editColumn('file_name', function ($row) {
                $name = $row->file_name ?: 'N/A';
                $description = $row->metadata['description'] ?? '';
                return '<div class="fw-bold">' . $name . '</div>
                        <div class="text-muted small">' . $description . '</div>';
            })
            ->addColumn('type_badge', function ($row) {
                $icons = [
                    'csv' => 'fa-file-csv',
                    'excel' => 'fa-file-excel',
                    'api' => 'fa-plug',
                    'manual' => 'fa-keyboard',
                    'scheduled' => 'fa-clock',
                ];
                $icon = $icons[$row->import_type] ?? 'fa-file';
                $typeLabel = ucfirst($row->import_type);
                
                return '<span class="import-type type-' . $row->import_type . '">
                    <i class="fas ' . $icon . ' me-1"></i>' . $typeLabel . '
                </span>';
            })
            ->editColumn('total_records', function ($row) {
                $success = $row->successful_records ?? 0;
                $failed = $row->failed_records ?? 0;
                $warning = $row->warning_records ?? 0;
                
                return '<div class="fw-bold">' . $row->total_records . '</div>
                        <div class="text-muted small">
                            <span class="text-success">✓ ' . $success . '</span> /
                            <span class="text-danger">✗ ' . $failed . '</span>' . 
                            ($warning > 0 ? ' / <span class="text-warning">⚠ ' . $warning . '</span>' : '') . '
                        </div>';
            })
            ->addColumn('status_badge', function ($row) {
                $badges = [
                    'completed' => ['class' => 'status-completed', 'icon' => 'fa-check-circle', 'label' => 'Completed'],
                    'processing' => ['class' => 'status-processing', 'icon' => 'fa-sync-alt fa-spin', 'label' => 'Processing'],
                    'failed' => ['class' => 'status-failed', 'icon' => 'fa-times-circle', 'label' => 'Failed'],
                    'partial' => ['class' => 'status-partial', 'icon' => 'fa-exclamation-triangle', 'label' => 'Partial'],
                    'queued' => ['class' => 'status-queued', 'icon' => 'fa-clock', 'label' => 'Queued'],
                ];
                
                $badge = $badges[$row->status] ?? $badges['queued'];
                
                return '<span class="status-badge ' . $badge['class'] . '">
                    <i class="fas ' . $badge['icon'] . '"></i>' . $badge['label'] . '
                </span>';
            })
            ->editColumn('processing_time', function ($row) {
                if ($row->status === 'processing') {
                    $time = $row->started_at ? Carbon::now()->diffInSeconds($row->started_at) : 0;
                    return '<div class="fw-bold">' . number_format($time, 1) . 's</div>
                            <div class="text-muted small">In progress</div>';
                }
                
                if ($row->processing_time) {
                    return '<div class="fw-bold">' . number_format($row->processing_time, 1) . 's</div>
                            <div class="text-muted small">Processing time</div>';
                }
                
                return '<div class="fw-bold">-</div><div class="text-muted small">Not started</div>';
            })
            ->addColumn('user_avatar', function ($row) {
                if (!$row->user) {
                    $initials = 'SYS';
                    $color = 'bg-info';
                    $name = 'System';
                } else {
                    $initials = strtoupper(substr($row->user->name, 0, 2));
                    $color = 'bg-primary';
                    $name = $row->user->name;
                }
                
                return '<div class="d-flex align-items-center">
                    <div class="rounded-circle ' . $color . ' d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                        <span class="text-white small">' . $initials . '</span>
                    </div>
                    <span>' . $name . '</span>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                $actions = '<div class="action-buttons">';
                
                // View button
                $actions .= '<button class="action-btn view-btn" onclick="viewImportDetails(' . $row->id . ')" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>';
                
                // Download button (if file exists)
                if ($row->file_path) {
                    $actions .= '<button class="action-btn" onclick="downloadImportFile(' . $row->id . ')" title="Download File">
                        <i class="fas fa-download"></i>
                    </button>';
                }
                
                // Retry button (for failed/partial)
                if (in_array($row->status, ['failed', 'partial'])) {
                    $actions .= '<button class="action-btn retry-btn" onclick="retryImport(' . $row->id . ')" title="Re-import">
                        <i class="fas fa-redo"></i>
                    </button>';
                }
                
                // Cancel button (for processing/queued)
                if (in_array($row->status, ['processing', 'queued'])) {
                    $actions .= '<button class="action-btn" onclick="cancelImport(' . $row->id . ')" title="Cancel">
                        <i class="fas fa-stop-circle"></i>
                    </button>';
                }
                
                // Run now button (for queued)
                if ($row->status === 'queued') {
                    $actions .= '<button class="action-btn" onclick="runNowImport(' . $row->id . ')" title="Run Now">
                        <i class="fas fa-play"></i>
                    </button>';
                }
                
                // Delete button
                $actions .= '<button class="action-btn delete-btn" onclick="deleteImport(' . $row->id . ')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>';
                
                $actions .= '</div>';
                
                return $actions;
            })
            ->rawColumns(['checkbox', 'import_id', 'created_at', 'file_name', 'type_badge', 'total_records', 'status_badge', 'processing_time', 'user_avatar', 'actions'])
            ->make(true);
    }

    public function show($id)
    {
        $import = ImportHistory::with('user')->findOrFail($id);
        return response()->json($import);
    }

    public function getStatistics()
    {
        $totalImports = ImportHistory::count();
        $successfulImports = ImportHistory::where('status', 'completed')->count();
        $successRate = $totalImports > 0 ? round(($successfulImports / $totalImports) * 100, 1) : 0;
        $totalRecords = ImportHistory::sum('successful_records');
        $pendingReviews = ImportHistory::where('status', 'partial')->count();
        
        // This month records
        $thisMonthRecords = ImportHistory::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('successful_records');
        
        // Average processing time
        $avgProcessingTime = ImportHistory::whereNotNull('processing_time')
            ->avg('processing_time');
        
        // Last month total for comparison
        $lastMonthTotal = ImportHistory::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();
        
        $thisMonthTotal = ImportHistory::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        $percentageChange = $lastMonthTotal > 0 
            ? round((($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : 0;

        $failedLast30Days = ImportHistory::where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        return response()->json([
            'total_imports' => $totalImports,
            'success_rate' => $successRate,
            'total_records' => $totalRecords,
            'this_month_records' => $thisMonthRecords,
            'pending_reviews' => $pendingReviews,
            'avg_processing_time' => round($avgProcessingTime ?? 0, 1),
            'percentage_change' => $percentageChange,
            'failed_last_30_days' => $failedLast30Days,
        ]);
    }

    public function getTrendData(Request $request)
    {
        $days = $request->get('days', 7);
        $data = [];
        $labels = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            
            $data['successful'][] = ImportHistory::whereDate('created_at', $date)
                ->where('status', 'completed')->count();
            
            $data['failed'][] = ImportHistory::whereDate('created_at', $date)
                ->where('status', 'failed')->count();
            
            $data['partial'][] = ImportHistory::whereDate('created_at', $date)
                ->where('status', 'partial')->count();
        }
        
        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function getStatusDistribution()
    {
        $distribution = ImportHistory::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        return response()->json([
            'completed' => $distribution['completed'] ?? 0,
            'processing' => $distribution['processing'] ?? 0,
            'failed' => $distribution['failed'] ?? 0,
            'partial' => $distribution['partial'] ?? 0,
            'queued' => $distribution['queued'] ?? 0,
        ]);
    }

    public function getLogs($id)
    {
        $import = ImportHistory::findOrFail($id);
        return response()->json([
            'logs' => $import->parsed_logs,
        ]);
    }

    public function destroy($id)
    {
        $import = ImportHistory::findOrFail($id);
        $import->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Import history deleted successfully',
        ]);
    }

    public function downloadFile($id)
    {
        $import = ImportHistory::findOrFail($id);
        if (!$import->file_path || !Storage::disk('public')->exists($import->file_path)) {
            abort(404, 'File not found');
        }
        return Storage::disk('public')->download(
            $import->file_path,
            $import->file_name ?: 'import_' . $import->import_id . '.xlsx'
        );
    }

    public function retry($id)
    {
        $import = ImportHistory::findOrFail($id);
        if (!$import->file_path || !Storage::disk('public')->exists($import->file_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Original file not found. Cannot retry.',
            ], 404);
        }
        $metadata = $import->metadata ?? [];
        $mapping = $metadata['mapping'] ?? [];
        $overwrite = (bool) ($metadata['overwrite'] ?? false);
        
        $companyId = function_exists('current_company_id') ? current_company_id() : (auth()->user()->company_id ?? null);
        if (!$companyId) {
            return response()->json([
                'status' => 'error',
                'message' => 'No company selected.',
            ], 422);
        }

        $hasDate = !empty($mapping['entry_date']) || !empty($mapping['date']);
        $hasFacility = !empty($mapping['facility']) || !empty($mapping['facility_id']);
        $hasDepartment = !empty($mapping['department']) || !empty($mapping['department_id']);
        if (!$hasDate || !$hasFacility || !$hasDepartment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Original import mapping is invalid (missing required columns).',
            ], 422);
        }

        try {
            $path = Storage::disk('public')->path($import->file_path);
            $importClass = new EmissionsImport($overwrite, $mapping);
            Excel::import($importClass, $path);
            
            $processedCount = $importClass->getProcessedCount();
            $skippedCount = $importClass->getSkippedCount();
            $successfulCount = $processedCount - $skippedCount;

            return response()->json([
                'status' => 'success',
                'message' => "Retry completed. {$successfulCount} record(s) imported, {$skippedCount} skipped.",
                'successful' => $successfulCount,
                'skipped' => $skippedCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Retry failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel($id)
    {
        $import = ImportHistory::findOrFail($id);
        if (in_array($import->status, ['processing', 'queued'])) {
            $import->update([
                'status' => 'failed',
                'error_message' => 'Cancelled by user',
                'completed_at' => Carbon::now(),
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Import cancelled.',
        ]);
    }

    public function exportHistory(Request $request)
    {
        $query = ImportHistory::with('user')->orderBy('created_at', 'desc');
        
        if ($request->has('date_range') && $request->date_range != 'all') {
            switch ($request->date_range) {
                case 'today': $query->whereDate('created_at', Carbon::today()); break;
                case 'week': $query->where('created_at', '>=', Carbon::now()->subDays(7)); break;
                case 'month': $query->where('created_at', '>=', Carbon::now()->subDays(30)); break;
                case 'quarter': $query->where('created_at', '>=', Carbon::now()->subMonths(3)); break;
                case 'year': $query->where('created_at', '>=', Carbon::now()->subYear()); break;
            }
        }
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        $imports = $query->get();
        $filename = 'import_history_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($imports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Import ID', 'Date', 'File Name', 'Type', 'Status', 'Total', 'Successful', 'Failed', 'Duration (s)', 'User']);
            foreach ($imports as $row) {
                fputcsv($file, [
                    $row->import_id,
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->file_name ?? 'N/A',
                    $row->import_type,
                    $row->status,
                    $row->total_records,
                    $row->successful_records ?? 0,
                    $row->failed_records ?? 0,
                    $row->processing_time ?? '',
                    $row->user ? $row->user->name : 'System',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadReport($id)
    {
        $import = ImportHistory::with('user')->findOrFail($id);
        $filename = 'import_report_' . $import->import_id . '_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($import) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Import Report - ' . $import->import_id]);
            fputcsv($file, []);
            fputcsv($file, ['Import ID', $import->import_id]);
            fputcsv($file, ['Date & Time', $import->created_at->format('Y-m-d H:i:s')]);
            fputcsv($file, ['File Name', $import->file_name ?? 'N/A']);
            fputcsv($file, ['Import Type', $import->import_type]);
            fputcsv($file, ['Status', $import->status]);
            fputcsv($file, ['Total Records', $import->total_records]);
            fputcsv($file, ['Successful', $import->successful_records ?? 0]);
            fputcsv($file, ['Failed', $import->failed_records ?? 0]);
            fputcsv($file, ['Processing Time (s)', $import->processing_time ?? 'N/A']);
            fputcsv($file, ['User', $import->user ? $import->user->name : 'System']);
            if ($import->error_message) {
                fputcsv($file, []);
                fputcsv($file, ['Error Message', $import->error_message]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Logs']);
            foreach ($import->parsed_logs as $log) {
                fputcsv($file, [$log['level'] ?? '', $log['time'] ?? '', $log['message'] ?? '']);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportLogsBulk(Request $request)
    {
        $ids = $request->has('ids') ? array_map('intval', explode(',', $request->ids)) : [];
        $imports = ImportHistory::with('user')->whereIn('id', $ids)->orderBy('created_at', 'desc')->get();
        $filename = 'import_logs_bulk_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function () use ($imports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Import ID', 'Date', 'File', 'Type', 'Status', 'Total', 'Successful', 'Failed', 'User']);
            foreach ($imports as $row) {
                fputcsv($file, [
                    $row->import_id,
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->file_name ?? 'N/A',
                    $row->import_type,
                    $row->status,
                    $row->total_records,
                    $row->successful_records ?? 0,
                    $row->failed_records ?? 0,
                    $row->user ? $row->user->name : 'System',
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function getImportSources()
    {
        $sources = ImportHistory::select('import_type', DB::raw('count(*) as count'), DB::raw('sum(successful_records) as records'))
            ->groupBy('import_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'sources' => $sources->map(function ($s) {
                return [
                    'type' => ucfirst($s->import_type),
                    'count' => $s->count,
                    'records' => $s->records ?? 0,
                ];
            }),
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,export,archive',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:import_history,id',
        ]);
        
        $ids = $request->ids;
        
        switch ($request->action) {
            case 'delete':
                ImportHistory::whereIn('id', $ids)->delete();
                return response()->json([
                    'success' => true,
                    'message' => count($ids) . ' import(s) deleted successfully',
                ]);
            case 'export':
                $idsStr = implode(',', $ids);
                return response()->json([
                    'success' => true,
                    'message' => 'Preparing download...',
                    'download_url' => route('import_history.export_logs', ['ids' => $idsStr]),
                ]);
            case 'archive':
                foreach ($ids as $id) {
                    $imp = ImportHistory::find($id);
                    if ($imp) {
                        $meta = $imp->metadata ?? [];
                        $meta['archived'] = true;
                        $meta['archived_at'] = now()->toIso8601String();
                        $imp->update(['metadata' => $meta]);
                    }
                }
                return response()->json([
                    'success' => true,
                    'message' => count($ids) . ' import(s) archived',
                ]);
            default:
                return response()->json(['success' => false, 'message' => 'Unknown action'], 422);
        }
    }
}
