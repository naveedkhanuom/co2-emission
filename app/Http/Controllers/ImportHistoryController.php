<?php

namespace App\Http\Controllers;

use App\Models\ImportHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
            $query->where('import_type', $request->type);
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

        return response()->json([
            'total_imports' => $totalImports,
            'success_rate' => $successRate,
            'total_records' => $totalRecords,
            'this_month_records' => $thisMonthRecords,
            'pending_reviews' => $pendingReviews,
            'avg_processing_time' => round($avgProcessingTime ?? 0, 1),
            'percentage_change' => $percentageChange,
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

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,export,retry,archive',
            'ids' => 'required|array',
        ]);
        
        $ids = $request->ids;
        
        switch ($request->action) {
            case 'delete':
                ImportHistory::whereIn('id', $ids)->delete();
                break;
            case 'archive':
                // You can implement archiving logic here
                break;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Bulk action completed successfully',
        ]);
    }
}
