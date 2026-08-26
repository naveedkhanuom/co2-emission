<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Read-only viewer for the company's change history (audit trail).
 */
class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // The trail records who altered which emission figure and when. It was
        // readable by every authenticated user in the company, which is wider
        // than it should be — this is the administrator's and the assurer's
        // view. Super Admin and Admin bypass this through Gate::before, so
        // granting the permission is only needed for other roles.
        $this->middleware('permission:list-audit-logs');
    }

    /**
     * Restrict the query to the viewer's tenant. Super-admins see everything;
     * everyone else sees only their own company's logs.
     */
    private function scopedQuery()
    {
        $query = AuditLog::query()->with('user')->latest();

        $user = auth()->user();
        if ($user && !$user->is_super_admin) {
            $companyId = current_company_id() ?? $user->company_id;
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->scopedQuery();

        // Filters
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('model')) {
            $query->where('auditable_type', $request->model);
        }
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(fn ($q) => $q
                ->where('user_name', 'like', "%{$term}%")
                ->orWhere('url', 'like', "%{$term}%"));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Distinct model types present, for the filter dropdown.
        $models = (clone $this->scopedQuery())
            ->reorder()
            ->select('auditable_type')
            ->distinct()
            ->pluck('auditable_type')
            ->mapWithKeys(fn ($t) => [$t => $this->humanModel($t)])
            ->toArray();

        return view('audit_logs.index', [
            'logs'    => $logs,
            'models'  => $models,
            'filters' => $request->only(['event', 'model', 'search', 'from', 'to']),
        ]);
    }

    /**
     * Turn "App\Models\EmissionRecord" into "Emission Record".
     */
    private function humanModel(string $type): string
    {
        $short = class_basename($type);

        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $short));
    }
}
