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
                'year' => (int) $year,
                'records' => (int) ($data->records ?? 0),
                'total' => round((float) ($data->total ?? 0), 2),
                'status' => $row->status ?? 'open',
                'is_base_year' => (bool) ($row->is_base_year ?? false),
                'locked_at' => $row?->locked_at,
                'locked_by' => $row?->locker?->name,
                'note' => $row->note ?? null,

                // Present only on a year finalised without a complete
                // boundary. Surfaced in the table because a caveat nobody can
                // see is not a caveat.
                'ack_reason' => $row->boundary_ack_reason ?? null,
                'ack_gap' => $row->boundary_ack_gap ?? null,
            ];
        });

        return view('reporting_periods.index', [
            'periods' => $periods,
            'baseYear' => ReportingPeriod::baseYearFor($companyId),

            // Null when the boundary is complete, in which case locking is the
            // one-click action it has always been. Non-null makes the lock
            // control ask for a written reason first.
            'boundaryGap' => $this->boundaryGapFor(Company::find($companyId)),
        ]);
    }

    public function lock(Request $request, int $year)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
            'boundary_ack_reason' => 'nullable|string|min:20|max:500',
        ], [
            'boundary_ack_reason.min' => 'Please say in a sentence why this year can be finalised without a complete boundary.',
        ]);

        $companyId = current_company_id();
        $gap = $this->boundaryGapFor(Company::find($companyId));
        $reason = trim((string) ($data['boundary_ack_reason'] ?? ''));

        // The gate. A year with an incomplete boundary can still be locked,
        // but not silently — see boundaryGapFor() for why it is not a refusal.
        if ($gap !== null && $reason === '') {
            return back()->with('error',
                "{$year} cannot be finalised yet: {$gap} A locked year is a signed-off inventory, and an inventory ".
                'is only complete against a boundary that says what belongs in it. Scope your boundary first, or '.
                'record why this year can be signed off without one.'
            );
        }

        ReportingPeriod::updateOrCreate(
            ['company_id' => $companyId, 'year' => $year],
            [
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'note' => $data['note'] ?? null,

                // Recorded together, and only when the boundary was actually
                // incomplete: an acknowledgement typed against a complete
                // boundary would put a permanent caveat on a clean year.
                'boundary_ack_reason' => $gap !== null ? $reason : null,
                'boundary_ack_gap' => $gap,
            ]
        );

        return back()->with('success', $gap === null
            ? "Reporting period {$year} is now locked. Its data can no longer be changed."
            : "Reporting period {$year} is locked, with your note recorded against it. It will show as finalised without a complete boundary."
        );
    }

    /**
     * What stops this company's inventory being called complete — or null when
     * nothing does.
     *
     * WHY THIS DOES NOT REFUSE
     *
     * Under the GHG Protocol a footprint means nothing without a decided
     * boundary, so on principle an incomplete one should block a lock. In
     * practice this shipped to clients who already have years of data and no
     * boundary assessment, and taking a governance action away from a live
     * account is a worse outcome than letting it proceed on the record. The
     * written acknowledgement IS the assurance artefact — the same bargain
     * BoundaryItem strikes, where an exclusion is acceptable precisely because
     * a reason was recorded against it.
     *
     * Not year-scoped. Only one assessment is active at a time
     * (BoundaryController::activate supersedes the rest), so the active
     * boundary is the company's current boundary whatever year it was scoped
     * in — its year is named in the gap text instead, where a reader can weigh
     * it.
     */
    private function boundaryGapFor(?Company $company): ?string
    {
        if (! $company) {
            return null;
        }

        $assessment = $company->activeBoundaryAssessment();

        if (! $assessment) {
            return 'no inventory boundary has been scoped and activated.';
        }

        // The Scope 3 Standard requires all 15 categories to be screened for
        // relevance. Screened, not included — deciding a category is
        // irrelevant is a complete answer; leaving it untouched is not.
        $unscreened = $assessment->unscreenedScope3Count();

        if ($unscreened > 0) {
            return "{$unscreened} of the 15 Scope 3 categories have not been screened for relevance ".
                "(boundary scoped for {$assessment->reporting_year}).";
        }

        return null;
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
