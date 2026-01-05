<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\EmissionFactor;
use App\Models\Company;
use App\Models\Site;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Validator;

class EmissionRecordController extends Controller
{
    public function index()
    {
        return view('emission_records.index', [
           
        ]);
    }

    public function getData()
    {
        $records = EmissionRecord::with(['company', 'site', 'user']);

        return DataTables::of($records)
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'">View</button>
                    <button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->editColumn('co2e_value', fn ($row) => number_format($row->co2e_value, 4))
            ->make(true);
    }


    public function store(Request $request)
    {
        // Determine status
        $status = $request->input('status', 'active');

        // If multiple entries are sent
        if ($request->has('entries')) {

            $entries = $request->input('entries');

            // Validate each entry
            foreach ($entries as $index => $data) {
                $validator = Validator::make($data, [
                    'entryDate'             => 'required|date',
                    'facilitySelect'        => 'required|string|max:50',
                    'scopeSelect'           => 'required|in:1,2,3',
                    'emissionSourceSelect'  => 'required|string|max:100',
                    'co2eValue'             => 'required|numeric|min:0',
                    'confidenceLevel'       => 'required|in:low,medium,high',
                    'departmentSelect'      => 'nullable|string|max:100',
                    'dataSource'            => 'required|in:manual,import,api',
                    'entryNotes'            => 'nullable|string|max:1000',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'errors' => ['row' => $index + 1, 'messages' => $validator->errors()]
                    ], 422);
                }
            }

            // Save all entries
            foreach ($entries as $data) {
                EmissionRecord::create([
                    'entry_date'        => $data['entryDate'],
                    'facility'          => $data['facilitySelect'],
                    'scope'             => $data['scopeSelect'],
                    'emission_source'   => $data['emissionSourceSelect'],
                    'co2e_value'        => $data['co2eValue'],
                    'confidence_level'  => $data['confidenceLevel'] ?? 'medium',
                    'department'        => $data['departmentSelect'] ?? null,
                    'data_source'       => $data['dataSource'] ?? 'manual',
                    'notes'             => $data['entryNotes'] ?? null,
                    'created_by'        => auth()->id(),
                    'status'            => $status,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => count($entries) . ' records saved successfully'
            ]);
        }

        // Single entry validation
        $validator = Validator::make($request->all(), [
            'entryDate'             => 'required|date',
            'facilitySelect'        => 'required|string|max:50',
            'scopeSelect'           => 'required|in:1,2,3',
            'emissionSourceSelect'  => 'required|string|max:100',
            'activityData'          => 'required|numeric|min:0',
            'emissionFactor'        => 'required|numeric|min:0',
            'co2eValue'             => 'required|numeric|min:0',
            'confidenceLevel'       => 'required|in:low,medium,high',
            'departmentSelect'      => 'required|string|max:100',
            'dataSource'            => 'required|in:manual,import,api',
            'entryNotes'            => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Save single entry
        EmissionRecord::create([
            'entry_date'        => $request->entryDate,
            'facility'          => $request->facilitySelect,
            'scope'             => $request->scopeSelect,
            'emission_source'   => $request->emissionSourceSelect,
            'activity_data'     => $request->activityData,
            'emission_factor'   => $request->emissionFactor,
            'co2e_value'        => $request->co2eValue,
            'confidence_level'  => $request->confidenceLevel,
            'department'        => $request->departmentSelect,
            'data_source'       => $request->dataSource,
            'notes'             => $request->entryNotes,
            'created_by'        => auth()->id(),
            'status'            => $status,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Emission record saved successfully'
        ]);
    }



    public function show(EmissionRecord $emissionRecord)
    {
        return response()->json(
            $emissionRecord->load(['company', 'site', 'user'])
        );
    }

    public function destroy(EmissionRecord $emissionRecord)
    {
        $emissionRecord->delete();

        return response()->json([
            'message' => 'Emission record deleted successfully'
        ]);
    }
}
