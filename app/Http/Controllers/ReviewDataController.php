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
            ->with(['reviewer:id,name', 'approver:id,name', 'user:id,name'])
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
                // Three-step workflow: Draft -> Reviewed -> Approved.
                if ($row->status == 'active') {
                    $html = '<span class="status-badge status-validated"><i class="fas fa-check-circle me-1"></i>Approved</span>';
                    $meta = [];
                    if ($row->reviewed_by) {
                        $meta[] = 'reviewed by ' . e($row->reviewer?->name ?? 'Unknown');
                    }
                    if ($row->approved_by) {
                        $meta[] = 'approved by ' . e($row->approver?->name ?? 'Unknown');
                    }
                    $html .= $meta ? '<div class="text-muted" style="font-size:.72rem;margin-top:3px">' . implode(' · ', $meta) . '</div>' : '';
                    // Segregation-of-duties flags.
                    $flags = [];
                    if ($row->approved_by && $row->approved_by == $row->created_by) {
                        $flags[] = 'preparer approved own record';
                    }
                    if ($row->approved_by && $row->reviewed_by && $row->approved_by == $row->reviewed_by) {
                        $flags[] = 'same person reviewed & approved';
                    }
                    if ($flags) {
                        $html .= '<div style="margin-top:2px"><span class="badge bg-warning text-dark" title="' . e(implode('; ', $flags)) . '"><i class="fas fa-triangle-exclamation"></i> SoD</span></div>';
                    }
                    return $html;
                } elseif ($row->status == 'reviewed') {
                    $html = '<span class="status-badge" style="background:rgba(2,119,189,.12);color:#0277bd"><i class="fas fa-user-check me-1"></i>Reviewed</span>';
                    $html .= '<div class="text-muted" style="font-size:.72rem;margin-top:3px">by ' . e($row->reviewer?->name ?? 'Unknown') . ' · awaiting approval</div>';
                    return $html;
                }
                return '<span class="status-badge status-pending"><i class="fas fa-pen me-1"></i>Draft</span>';
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
                $html = '<div class="action-buttons">
                    <button class="action-btn edit-btn" onclick="editRecord(' . $row->id . ')" title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="action-btn" onclick="viewRecord(' . $row->id . ')" title="View Details"><i class="fas fa-eye"></i></button>';

                // State-based workflow buttons: Draft -> Review -> Approve, with Send-back.
                if ($row->status == 'draft') {
                    $html .= '<button class="action-btn" style="color:var(--primary-blue,#0277bd)" onclick="setRecordStatus(' . $row->id . ', \'reviewed\')" title="Mark reviewed"><i class="fas fa-user-check"></i></button>';
                } elseif ($row->status == 'reviewed') {
                    $html .= '<button class="action-btn" style="color:var(--primary-green,#2e7d32)" onclick="setRecordStatus(' . $row->id . ', \'active\')" title="Approve"><i class="fas fa-check-double"></i></button>';
                    $html .= '<button class="action-btn" onclick="setRecordStatus(' . $row->id . ', \'draft\')" title="Send back to draft"><i class="fas fa-rotate-left"></i></button>';
                } else { // active/approved
                    $html .= '<button class="action-btn" onclick="setRecordStatus(' . $row->id . ', \'draft\')" title="Reopen (unlock for editing)"><i class="fas fa-rotate-left"></i></button>';
                }

                return $html . '</div>';
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
        // Three-step workflow states: draft -> reviewed -> active (approved).
        $validated = $request->validate([
            'status' => 'required|in:draft,reviewed,active',
        ]);

        // findOrFail is company-scoped via HasCompanyScope (cross-tenant ids 404).
        $record = EmissionRecord::findOrFail($id);

        // Can't change a record that sits in a locked (finalised) reporting period.
        if ($record->entry_date && \App\Models\ReportingPeriod::isYearLocked((int) $record->entry_date->format('Y'), $record->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This record is in a locked reporting period and cannot be changed.',
            ], 422);
        }

        $target = $validated['status'];
        $uid = auth()->id();

        if ($target === 'reviewed') {
            // Preparer's work checked by a reviewer.
            $record->status = 'reviewed';
            $record->reviewed_by = $uid;
            $record->reviewed_at = now();
            $record->approved_by = null;
            $record->approved_at = null;
        } elseif ($target === 'active') {
            // Final approval. Enforce the chain: must be reviewed first.
            if (!$record->reviewed_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'This record must be reviewed before it can be approved.',
                ], 422);
            }
            $record->status = 'active';
            $record->approved_by = $uid;
            $record->approved_at = now();
        } else { // draft — send back, clearing review & approval
            $record->status = 'draft';
            $record->reviewed_by = null;
            $record->reviewed_at = null;
            $record->approved_by = null;
            $record->approved_at = null;
        }

        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action' => 'required|in:review,approve,reject,delete',
        ]);

        // Never touch records in a locked reporting period.
        $lockedYears = \App\Models\ReportingPeriod::where('status', 'locked')->pluck('year')->all();
        $base = EmissionRecord::whereIn('id', $request->ids);
        if ($lockedYears) {
            $base->whereNotIn(DB::raw('YEAR(entry_date)'), $lockedYears);
        }

        $uid = auth()->id();

        if ($request->action == 'review') {
            $affected = (clone $base)->update([
                'status' => 'reviewed', 'reviewed_by' => $uid, 'reviewed_at' => now(),
                'approved_by' => null, 'approved_at' => null,
            ]);
            $message = "{$affected} record(s) marked as reviewed";
        } elseif ($request->action == 'approve') {
            // Only approve records that have been reviewed (enforce the chain).
            $affected = (clone $base)->whereNotNull('reviewed_by')->update([
                'status' => 'active', 'approved_by' => $uid, 'approved_at' => now(),
            ]);
            $message = "{$affected} reviewed record(s) approved";
        } elseif ($request->action == 'reject') {
            $affected = (clone $base)->update([
                'status' => 'draft', 'reviewed_by' => null, 'reviewed_at' => null,
                'approved_by' => null, 'approved_at' => null,
            ]);
            $message = "{$affected} record(s) sent back to draft";
        } else { // delete
            $affected = (clone $base)->delete();
            $message = "{$affected} record(s) deleted";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
