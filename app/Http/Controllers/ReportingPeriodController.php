<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Governance: manage reporting periods (inventory years) — freeze/unfreeze a
 * year so its data can't change after a report is filed, and designate the
 * base year. Mutations require the review-data permission (reviewers/admins).
 */
class ReportingPeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:edit-review-data', ['only' => ['lock', 'unlock', 'setBaseYear']]);
    }

    public function index()
    {
        $companyId = current_company_id();

        // Years that actually have records (with counts + totals).
        $dataYears = EmissionRecord::selectRaw('YEAR(entry_date) as year, COUNT(*) as records, SUM(co2e_value) as total')
            ->whereNotNull('entry_date')
            ->groupBy(DB::raw('YEAR(entry_date)'))
            ->get()
            ->keyBy('year');

        $periodRows = ReportingPeriod::with('locker')->get()->keyBy('year');

        // Union of years that have data and years that already have a period row,
        // newest first, so a locked-but-empty year still shows.
        $years = $dataYears->keys()->merge($periodRows->keys())->unique()->sortDesc()->values();

        $periods = $years->map(function ($year) use ($dataYears, $periodRows) {
            $data = $dataYears->get($year);
            $row = $periodRows->get($year);

            return [
                'year'         => (int) $year,
                'records'      => (int) ($data->records ?? 0),
                'total'        => round((float) ($data->total ?? 0), 2),
                'status'       => $row->status ?? 'open',
                'is_base_year' => (bool) ($row->is_base_year ?? false),
                'locked_at'    => $row?->locked_at,
                'locked_by'    => $row?->locker?->name,
                'note'         => $row->note ?? null,
            ];
        });

        return view('reporting_periods.index', [
            'periods'  => $periods,
            'baseYear' => ReportingPeriod::baseYearFor($companyId),
        ]);
    }

    public function lock(Request $request, int $year)
    {
        $data = $request->validate(['note' => 'nullable|string|max:500']);

        ReportingPeriod::updateOrCreate(
            ['company_id' => current_company_id(), 'year' => $year],
            [
                'status'    => 'locked',
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'note'      => $data['note'] ?? null,
            ]
        );

        return back()->with('success', "Reporting period {$year} is now locked. Its data can no longer be changed.");
    }

    public function unlock(int $year)
    {
        ReportingPeriod::where('company_id', current_company_id())
            ->where('year', $year)
            ->update(['status' => 'open', 'locked_at' => null, 'locked_by' => null]);

        return back()->with('success', "Reporting period {$year} is unlocked and editable again.");
    }

    public function setBaseYear(int $year)
    {
        $companyId = current_company_id();

        // Only one base year per company.
        ReportingPeriod::where('company_id', $companyId)->update(['is_base_year' => false]);
        ReportingPeriod::updateOrCreate(
            ['company_id' => $companyId, 'year' => $year],
            ['is_base_year' => true]
        );

        // Keep the base_year company setting (used by Data Health / disclosures) in sync.
        if ($company = Company::find($companyId)) {
            $company->setSetting('base_year', $year, 'integer');
        }

        return back()->with('success', "{$year} is now the base year — the baseline targets and comparisons are measured against.");
    }
}
