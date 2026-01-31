<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportTemplate;
use App\Models\ScheduledReport;
use App\Models\ExportJob;
use App\Models\Facilities;
use App\Models\Department;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index() {
        $facilities = Facilities::all();
        $departments = Department::all();
        
        // Calculate statistics
        $total = Report::count();
        $thisMonth = Report::whereMonth('generated_at', now()->month)
            ->whereYear('generated_at', now()->year)
            ->count();
        $pending = Report::where('status', 'draft')->count();
        $published = Report::where('status', 'published')->count();
        
        // Load dynamic data
        $templates = ReportTemplate::where('is_active', true)->get()->groupBy('category');
        $scheduledReports = ScheduledReport::with(['template', 'facility', 'department'])
            ->where('status', 'active')
            ->orderBy('next_run_date')
            ->get();
        $exportJobs = ExportJob::orderBy('created_at', 'desc')->limit(20)->get();
        
        return view('reports.index', compact(
            'facilities', 
            'departments', 
            'total', 
            'thisMonth', 
            'pending', 
            'published',
            'templates',
            'scheduledReports',
            'exportJobs'
        ));
    }

    public function statistics() {
        $total = Report::count();
        $thisMonth = Report::whereMonth('generated_at', now()->month)
            ->whereYear('generated_at', now()->year)
            ->count();
        $scheduled = Report::where('status', 'scheduled')->count();
        $dueToday = Report::where('status', 'scheduled')
            ->whereDate('generated_at', now()->toDateString())
            ->count();
        $shared = Report::where('status', 'published')->count(); // Published reports are considered shared
        // Calculate views in the last week
        $viewsWeek = Report::where('last_viewed_at', '>=', now()->subWeek())
            ->sum('views_count');
        $pending = Report::where('status', 'draft')->count();

        return response()->json([
            'total' => $total,
            'this_month' => $thisMonth,
            'scheduled' => $scheduled,
            'due_today' => $dueToday,
            'shared' => $shared,
            'views_week' => $viewsWeek,
            'pending' => $pending,
        ]);
    }

    public function getData(Request $request) {
        $query = Report::with(['facility', 'department', 'user']);

        // Apply filters
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_range') && $request->date_range !== 'all') {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('generated_at', now()->toDateString());
                    break;
                case 'week':
                    $query->where('generated_at', '>=', now()->subDays(7));
                    break;
                case 'month':
                    $query->where('generated_at', '>=', now()->subDays(30));
                    break;
                case 'quarter':
                    $query->where('generated_at', '>=', now()->subMonths(3));
                    break;
            }
        }

        return DataTables::of($query)
            ->addColumn('facility_name', function ($row) {
                return $row->facility ? $row->facility->name : 'N/A';
            })
            ->addColumn('department_name', function ($row) {
                return $row->department ? $row->department->name : 'N/A';
            })
            ->addColumn('author_name', function ($row) {
                return $row->user ? $row->user->name : 'Unknown';
            })
            ->addColumn('status_badge', function ($row) {
                $badges = [
                    'published' => '<span class="badge bg-success">Published</span>',
                    'draft' => '<span class="badge bg-warning text-dark">Draft</span>',
                    'scheduled' => '<span class="badge bg-info">Scheduled</span>',
                    'archived' => '<span class="badge bg-secondary">Archived</span>',
                ];
                return $badges[$row->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('type_badge', function ($row) {
                $types = [
                    'executive' => '<span class="badge bg-primary">Executive</span>',
                    'regulatory' => '<span class="badge bg-warning text-dark">Regulatory</span>',
                    'internal' => '<span class="badge bg-success">Internal</span>',
                    'public' => '<span class="badge bg-info">Public</span>',
                ];
                return $types[$row->type] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('actions', function ($row) {
                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>';
            })
            ->editColumn('generated_at', function ($row) {
                return $row->generated_at ? $row->generated_at->format('Y-m-d') : 'N/A';
            })
            ->rawColumns(['status_badge', 'type_badge', 'actions'])
            ->make(true);
    }
    
    public function getReportsJson() {
        $reports = Report::with(['facility', 'department', 'user'])->get();
        return response()->json(['data' => $reports]);
    }

    public function storeOrUpdate(Request $request) {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:reports,id',
            'facility_id' => 'required|exists:facilities,id',
            'department_id' => 'nullable|exists:departments,id',
            'report_name' => 'required|string|max:255',
            'period' => 'required|string|max:50',
            'generated_at' => 'required|date',
            'status' => 'required|in:published,draft,scheduled,archived',
            'type' => 'required|in:executive,regulatory,internal,public',
        ]);

        $data = $validated;
        unset($data['id']);
        
        // Set created_by if not set
        if (!isset($data['created_by']) && auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        $report = Report::updateOrCreate(
            ['id' => $request->id ?? null],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => ($request->id ? 'Report updated successfully' : 'Report created successfully'),
            'data' => $report->load(['facility', 'department', 'user']),
        ]);
    }

    public function show($id) {
        return Report::with(['facility','department','user'])->findOrFail($id);
    }

    public function destroy($id) {
        $report = Report::findOrFail($id);
        $report->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    // Report Templates
    public function getTemplates() {
        $templates = ReportTemplate::where('is_active', true)->get();
        return response()->json(['data' => $templates]);
    }

    public function storeTemplate(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:executive,compliance,facility,stakeholder,periodic,target-tracking',
            'formats' => 'nullable|array',
            'sections' => 'nullable|array',
        ]);

        $validated['created_by'] = auth()->id();
        $template = ReportTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template,
        ]);
    }

    // Scheduled Reports
    public function getScheduledReports() {
        $scheduled = ScheduledReport::with(['template', 'facility', 'department'])
            ->orderBy('next_run_date')
            ->get();
        return response()->json(['data' => $scheduled]);
    }

    public function storeScheduledReport(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_template_id' => 'nullable|exists:report_templates,id',
            'facility_id' => 'nullable|exists:facilities,id',
            'department_id' => 'nullable|exists:departments,id',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'schedule_time' => 'required|date_format:H:i',
            'recipients' => 'nullable|array',
            'formats' => 'nullable|array',
        ]);

        // Calculate next_run_date based on frequency
        $nextRunDate = $this->calculateNextRunDate($validated['frequency']);
        $validated['next_run_date'] = $nextRunDate;
        $validated['created_by'] = auth()->id();

        $scheduled = ScheduledReport::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report created successfully',
            'data' => $scheduled->load(['template', 'facility', 'department']),
        ]);
    }

    private function calculateNextRunDate($frequency) {
        $now = now();
        switch ($frequency) {
            case 'daily':
                return $now->addDay()->toDateString();
            case 'weekly':
                return $now->addWeek()->toDateString();
            case 'monthly':
                return $now->addMonth()->toDateString();
            case 'quarterly':
                return $now->addMonths(3)->toDateString();
            case 'yearly':
                return $now->addYear()->toDateString();
            default:
                return $now->addMonth()->toDateString();
        }
    }

    // Export Jobs
    public function getExportJobs() {
        $jobs = ExportJob::orderBy('created_at', 'desc')->limit(50)->get();
        return response()->json(['data' => $jobs]);
    }

    public function storeExportJob(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'format' => 'required|in:pdf,excel,csv,pptx,png',
            'filters' => 'nullable|array',
        ]);

        $validated['created_by'] = auth()->id();
        $job = ExportJob::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Export job created successfully',
            'data' => $job,
        ]);
    }

    // Track report view
    public function trackView($id) {
        $report = Report::findOrFail($id);
        $report->increment('views_count');
        $report->update(['last_viewed_at' => now()]);
        
        return response()->json(['success' => true]);
    }
}

