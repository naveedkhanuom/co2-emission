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

    public function getData() {
        $reports = Report::with(['company', 'site']);

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

    public function storeOrUpdate(Request $request) {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'report_name' => 'required|string|max:255',
            'period' => 'required|string|max:50',
            'generated_at' => 'required|date',
        ]);

        $report = Report::updateOrCreate(
            ['id' => $request->id],
            $request->only(['company_id','site_id','report_name','period','generated_at'])
        );

        return response()->json(['message' => 'Report saved successfully']);
    }

    public function show($id) {
        return Report::with(['company','site'])->findOrFail($id);
    }

    public function destroy($id) {
        Report::findOrFail($id)->delete();
        return response()->json(['message' => 'Report deleted successfully']);
    }
}

