<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Company;
use App\Models\Site;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index() {
        $companies = Company::all();
        return view('reports.index', compact('companies'));
    }

    public function statistics() {
        $total = Report::count();
        $thisMonth = Report::whereMonth('generated_at', now()->month)
            ->whereYear('generated_at', now()->year)
            ->count();
        $scheduled = 0; // TODO: Implement scheduled reports
        $dueToday = 0; // TODO: Implement scheduled reports
        $shared = 0; // TODO: Implement shared reports
        $viewsWeek = 0; // TODO: Implement view tracking
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

    public function getData() {
        $reports = Report::with(['company', 'site', 'user']);

        return DataTables::of($reports)
            ->addColumn('actions', function ($row) {
                $view = '<button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'">View</button>';
                $edit = '<button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'">Edit</button>';
                $delete = '<button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'">Delete</button>';
                return $view.' '.$edit.' '.$delete;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
    
    public function getReportsJson() {
        $reports = Report::with(['company', 'site', 'user'])->get();
        return response()->json(['data' => $reports]);
    }

    public function storeOrUpdate(Request $request) {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'report_name' => 'required|string|max:255',
            'period' => 'required|string|max:50',
            'generated_at' => 'required|date',
        ]);

        $data = $request->only(['company_id','site_id','report_name','period','generated_at','status','type']);
        
        // Set created_by if not set
        if (!isset($data['created_by']) && auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        $report = Report::updateOrCreate(
            ['id' => $request->id],
            $data
        );

        return response()->json(['message' => 'Report saved successfully']);
    }

    public function show($id) {
        return Report::with(['company','site','user'])->findOrFail($id);
    }

    public function destroy($id) {
        Report::findOrFail($id)->delete();
        return response()->json(['message' => 'Report deleted successfully']);
    }
}

