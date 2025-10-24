<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\EmissionFactor;
use App\Models\Company;
use App\Models\Site;
use Illuminate\Http\Request;
use DataTables;

class EmissionRecordController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        $sites = Site::all();
        $sources = EmissionSource::all();
        $factors = EmissionFactor::all();
        return view('emission_records.index', compact('companies', 'sites', 'sources', 'factors'));
    }

    public function getData()
    {
        $records = EmissionRecord::with(['company', 'site', 'user', 'emissionSource', 'emissionFactor']);
        return DataTables::of($records)
            ->addColumn('actions', function($row){
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'">View</button>
                    <button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->editColumn('emission_value', fn($row) => number_format($row->emission_value, 4))
            ->make(true);
    }

    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'site_id' => 'nullable|exists:sites,id',
            'user_id' => 'nullable|exists:users,id',
            'emission_source_id' => 'required|exists:emission_sources,id',
            'emission_factor_id' => 'nullable|exists:emission_factors,id',
            'record_date' => 'required|date',
            'activity_data' => 'required|numeric',
            'unit' => 'nullable|string',
        ]);

        // Calculate emission_value if factor is present
        if($request->emission_factor_id){
            $factor = EmissionFactor::find($request->emission_factor_id);
            $validated['emission_value'] = $request->activity_data * $factor->factor_value;
        }

        $record = EmissionRecord::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json(['message' => 'Emission record saved successfully']);
    }

    public function show(EmissionRecord $emissionRecord)
    {
        return $emissionRecord->load(['company', 'site', 'user', 'emissionSource', 'emissionFactor']);
    }

    public function destroy(EmissionRecord $emissionRecord)
    {
        $emissionRecord->delete();
        return response()->json(['message' => 'Emission record deleted successfully']);
    }
}
