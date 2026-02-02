<?php

namespace App\Http\Controllers;

use App\Models\SupplierSurvey;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Helpers\CompanyHelper;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class SupplierSurveyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-supplier-surveys|create-supplier-survey|edit-supplier-survey|delete-supplier-survey', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:create-supplier-survey', ['only' => ['store']]);
        $this->middleware('permission:edit-supplier-survey', ['only' => ['updateResponses', 'send', 'sendReminder']]);
        $this->middleware('permission:delete-supplier-survey', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of surveys.
     */
    public function index()
    {
        return view('supplier_surveys.index');
    }

    /**
     * Get surveys data for DataTables.
     */
    public function getData(Request $request)
    {
        $companyId = CompanyHelper::currentCompanyId();
        
        $surveys = SupplierSurvey::where('company_id', $companyId)
            ->with(['supplier', 'creator'])
            ->latest();

        return DataTables::of($surveys)
            ->addColumn('supplier_name', function ($survey) {
                return $survey->supplier->name ?? 'N/A';
            })
            ->addColumn('status_badge', function ($survey) {
                $badges = [
                    'draft' => '<span class="badge bg-secondary">Draft</span>',
                    'sent' => '<span class="badge bg-info">Sent</span>',
                    'in_progress' => '<span class="badge bg-warning">In Progress</span>',
                    'completed' => '<span class="badge bg-success">Completed</span>',
                    'overdue' => '<span class="badge bg-danger">Overdue</span>',
                    'cancelled' => '<span class="badge bg-dark">Cancelled</span>',
                ];
                return $badges[$survey->status] ?? '<span class="badge bg-secondary">Unknown</span>';
            })
            ->addColumn('due_date_formatted', function ($survey) {
                if (!$survey->due_date) {
                    return 'N/A';
                }
                $daysUntil = $survey->getDaysUntilDue();
                $class = $daysUntil < 0 ? 'text-danger' : ($daysUntil < 7 ? 'text-warning' : '');
                return '<span class="' . $class . '">' . $survey->due_date->format('Y-m-d') . '</span>';
            })
            ->addColumn('completion_percentage', function ($survey) {
                if ($survey->status === 'completed') {
                    return '100%';
                }
                if ($survey->responses && is_array($survey->responses)) {
                    $answered = count(array_filter($survey->responses));
                    $total = $survey->questions ? count($survey->questions) : 0;
                    return $total > 0 ? round(($answered / $total) * 100) . '%' : '0%';
                }
                return '0%';
            })
            ->addColumn('actions', function ($survey) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<button class="btn btn-sm btn-info viewBtn" data-id="' . $survey->id . '" title="View"><i class="fas fa-eye"></i></button>';
                
                if ($survey->status === 'draft') {
                    $actions .= '<button class="btn btn-sm btn-primary editBtn" data-id="' . $survey->id . '" title="Edit"><i class="fas fa-edit"></i></button>';
                    $actions .= '<button class="btn btn-sm btn-success sendBtn" data-id="' . $survey->id . '" title="Send"><i class="fas fa-paper-plane"></i></button>';
                }
                
                if (in_array($survey->status, ['sent', 'in_progress', 'overdue'])) {
                    $actions .= '<button class="btn btn-sm btn-warning reminderBtn" data-id="' . $survey->id . '" title="Send Reminder"><i class="fas fa-bell"></i></button>';
                }
                
                $actions .= '<button class="btn btn-sm btn-danger deleteBtn" data-id="' . $survey->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'due_date_formatted', 'actions'])
            ->make(true);
    }

    /**
     * Store a newly created survey.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'title' => 'required|string|max:255',
            'survey_type' => 'required|string',
            'due_date' => 'required|date|after:today',
            'questions' => 'required|array|min:1',
        ]);

        $companyId = CompanyHelper::currentCompanyId();
        
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company selected.'
            ], 400);
        }

        // Verify supplier belongs to company
        $supplier = Supplier::findOrFail($request->supplier_id);
        if ($supplier->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid supplier.'
            ], 403);
        }

        $survey = SupplierSurvey::create([
            'company_id' => $companyId,
            'supplier_id' => $request->supplier_id,
            'title' => $request->title,
            'description' => $request->description,
            'survey_type' => $request->survey_type,
            'questions' => $request->questions,
            'due_date' => $request->due_date,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Survey created successfully',
            'data' => $survey
        ]);
    }

    /**
     * Send survey to supplier.
     */
    public function send($id)
    {
        $survey = SupplierSurvey::findOrFail($id);
        
        // Verify survey belongs to company
        $companyId = CompanyHelper::currentCompanyId();
        if ($survey->company_id != $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($survey->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft surveys can be sent.'
            ], 400);
        }

        $survey->markAsSent();

        // Email hook (implement Mail config later)
        Log::info('Supplier survey sent', [
            'survey_id' => $survey->id,
            'supplier_id' => $survey->supplier_id,
            'supplier_email' => $survey->supplier?->email,
            'public_token' => $survey->public_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Survey sent successfully',
            'data' => $survey
        ]);
    }

    /**
     * Send reminder to supplier.
     */
    public function sendReminder($id)
    {
        $survey = SupplierSurvey::findOrFail($id);
        
        $companyId = CompanyHelper::currentCompanyId();
        if ($survey->company_id != $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $survey->sendReminder();

        // Email hook (implement Mail config later)
        Log::info('Supplier survey reminder sent', [
            'survey_id' => $survey->id,
            'supplier_id' => $survey->supplier_id,
            'supplier_email' => $survey->supplier?->email,
            'reminder_count' => $survey->reminder_count,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder sent successfully'
        ]);
    }

    /**
     * Supplier portal: show survey form by public token.
     * No auth required.
     */
    public function publicShow(string $token)
    {
        $survey = SupplierSurvey::where('public_token', $token)->with('supplier')->firstOrFail();

        if (!$survey->isPublicLinkValid()) {
            return view('supplier_portal.survey_expired', compact('survey'));
        }

        return view('supplier_portal.survey', compact('survey'));
    }

    /**
     * Supplier portal: submit survey responses by public token.
     * No auth required.
     */
    public function publicSubmit(Request $request, string $token)
    {
        $survey = SupplierSurvey::where('public_token', $token)->with('supplier')->firstOrFail();

        if (!$survey->isPublicLinkValid()) {
            return redirect()->back()->with('error', 'This survey link has expired.');
        }

        $responses = $request->input('responses', []);

        // Save responses via existing workflow (marks completed if all answered)
        $survey->update([
            'responses' => $responses,
            'status' => 'in_progress',
        ]);

        $allAnswered = is_array($survey->questions)
            ? count(array_filter($responses, fn ($v) => $v !== null && $v !== '')) >= count($survey->questions)
            : true;

        if ($allAnswered) {
            $survey->markAsCompleted($responses);
            return redirect()->back()->with('success', 'Thank you! Survey submitted successfully.');
        }

        return redirect()->back()->with('success', 'Responses saved. You can return later to complete the survey.');
    }

    /**
     * Update survey responses (for supplier portal).
     */
    public function updateResponses(Request $request, $id)
    {
        $survey = SupplierSurvey::findOrFail($id);
        
        $request->validate([
            'responses' => 'required|array',
        ]);

        $survey->update([
            'responses' => $request->responses,
            'status' => 'in_progress',
        ]);

        // Check if all questions answered
        $allAnswered = count($request->responses) >= count($survey->questions ?? []);
        
        if ($allAnswered) {
            $survey->markAsCompleted($request->responses);
        }

        return response()->json([
            'success' => true,
            'message' => $allAnswered ? 'Survey completed successfully' : 'Responses saved',
            'data' => $survey
        ]);
    }

    /**
     * Show survey details.
     */
    public function show($id)
    {
        $survey = SupplierSurvey::with(['supplier', 'creator'])->findOrFail($id);
        
        $companyId = CompanyHelper::currentCompanyId();
        if ($survey->company_id != $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'survey' => $survey
        ]);
    }

    /**
     * Delete survey.
     */
    public function destroy($id)
    {
        $survey = SupplierSurvey::findOrFail($id);
        
        $companyId = CompanyHelper::currentCompanyId();
        if ($survey->company_id != $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($survey->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed survey.'
            ], 400);
        }

        $survey->delete();

        return response()->json([
            'success' => true,
            'message' => 'Survey deleted successfully'
        ]);
    }
}
