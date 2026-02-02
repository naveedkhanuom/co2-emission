<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ReviewDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-review-data|create-review-data|edit-review-data|delete-review-data', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:edit-review-data', ['only' => ['updateStatus', 'bulkUpdate']]);
    }

    public function index()
    {
        // Calculate summary statistics
        $totalRecords = EmissionRecord::count();
        
        // Validated records (status = 'active')
        $validatedRecords = EmissionRecord::where('status', 'active')->count();
        
        // Draft records (status = 'draft') - these need review
        $draftRecords = EmissionRecord::where('status', 'draft')->count();
        
        // Calculate records with validation issues
        // Records with missing facility or department are considered invalid
        $invalidRecords = EmissionRecord::where(function($query) {
            $query->whereNull('facility')
                  ->orWhere('facility', '')
                  ->orWhereNull('department')
                  ->orWhere('department', '');
        })->count();
        
        // Calculate data quality percentage
        $dataQuality = $totalRecords > 0 ? round(($validatedRecords / $totalRecords) * 100, 1) : 0;
        
        return view('review.index', compact(
            'totalRecords',
            'validatedRecords',
            'draftRecords',
            'invalidRecords',
            'dataQuality'
        ));
    }

    public function getData(Request $request)
    {
        $query = EmissionRecord::select('emission_records.*')
            ->orderBy('emission_records.entry_date', 'desc')
            ->orderBy('emission_records.created_at', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('data_source') && $request->data_source != '') {
            $query->where('data_source', $request->data_source);
        }

        if ($request->has('scope') && $request->scope != '') {
            $query->where('scope', $request->scope);
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->where('entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->where('entry_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function ($row) {
                return '<div class="form-check">
                    <input class="form-check-input row-checkbox" type="checkbox" value="' . $row->id . '">
                </div>';
            })
            ->addColumn('scope_badge', function ($row) {
                $badgeClass = [
                    1 => 'bg-success',
                    2 => 'bg-primary',
                    3 => 'bg-warning text-dark'
                ][$row->scope] ?? 'bg-secondary';
                
                return '<span class="badge ' . $badgeClass . '">Scope ' . $row->scope . '</span>';
            })
            ->addColumn('source_badge', function ($row) {
                $badgeClass = [
                    'import' => 'bg-secondary',
                    'manual' => 'bg-info',
                    'api' => 'bg-primary'
                ][$row->data_source] ?? 'bg-secondary';
                
                $sourceLabel = ucfirst($row->data_source ?? 'manual');
                
                return '<span class="badge ' . $badgeClass . '">' . $sourceLabel . '</span>';
            })
            ->addColumn('status_badge', function ($row) {
                if ($row->status == 'active') {
                    return '<span class="status-badge status-validated">
                        <i class="fas fa-check-circle me-1"></i>Validated
                    </span>';
                } else {
                    return '<span class="status-badge status-pending">
                        <i class="fas fa-clock me-1"></i>Pending
                    </span>';
                }
            })
            ->addColumn('validation_tag', function ($row) {
                // Determine validation status based on data quality
                if ($row->status == 'active' && !empty($row->facility) && !empty($row->department)) {
                    return '<span class="valid-tag validation-tag">
                        <i class="fas fa-check me-1"></i>Valid
                    </span>';
                } elseif (empty($row->facility) || empty($row->department)) {
                    $issue = empty($row->facility) ? 'Missing Facility' : 'Missing Department';
                    return '<span class="error-tag validation-tag">
                        <i class="fas fa-times me-1"></i>' . $issue . '
                    </span>';
                } else {
                    return '<span class="warning-tag validation-tag">
                        <i class="fas fa-exclamation me-1"></i>Needs Review
                    </span>';
                }
            })
            ->addColumn('co2e_formatted', function ($row) {
                return '<strong>' . number_format($row->co2e_value, 2) . '</strong> tCO₂e';
            })
            ->addColumn('updated_formatted', function ($row) {
                return $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function ($row) {
                return '<div class="action-buttons">
                    <button class="action-btn edit-btn" onclick="editRecord(' . $row->id . ')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn" onclick="viewRecord(' . $row->id . ')" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" onclick="validateRecord(' . $row->id . ')" title="Validate">
                        <i class="fas fa-check"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['checkbox', 'scope_badge', 'source_badge', 'status_badge', 'validation_tag', 'co2e_formatted', 'actions'])
            ->make(true);
    }

    public function show($id)
    {
        $record = EmissionRecord::findOrFail($id);
        return response()->json($record);
    }

    public function updateStatus(Request $request, $id)
    {
        $record = EmissionRecord::findOrFail($id);
        $record->status = $request->status;
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully'
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'action' => 'required|in:validate,reject,delete'
        ]);

        $ids = $request->ids;

        if ($request->action == 'validate') {
            EmissionRecord::whereIn('id', $ids)->update(['status' => 'active']);
            $message = count($ids) . ' records validated successfully';
        } elseif ($request->action == 'reject') {
            EmissionRecord::whereIn('id', $ids)->update(['status' => 'draft']);
            $message = count($ids) . ' records rejected';
        } elseif ($request->action == 'delete') {
            EmissionRecord::whereIn('id', $ids)->delete();
            $message = count($ids) . ' records deleted successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
