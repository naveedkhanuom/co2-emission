<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Base-year comparison: each inventory year's emissions (by scope) versus the
 * designated base year, with the % change (reduction/increase). This is the
 * like-for-like comparability that targets and disclosures depend on — it uses
 * the base year set on the Reporting Periods page.
 */
class BaseYearComparisonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $companyId = current_company_id();

        // Prefer the base year flagged on a reporting period; fall back to the
        // base_year company setting (both are kept in sync).
        $baseYear = ReportingPeriod::baseYearFor($companyId);
        if (!$baseYear && $companyId && ($company = Company::find($companyId))) {
            $baseYear = (int) $company->getSetting('base_year', 0) ?: null;
        }

        // Per-year, per-scope totals for finalised (active) records — grouped by
        // both YEAR() and scope, so it's ONLY_FULL_GROUP_BY-safe on the server.
        $rows = EmissionRecord::where('status', 'active')
            ->whereNotNull('entry_date')
            ->selectRaw('YEAR(entry_date) as yr, scope, SUM(co2e_value) as total')
            ->groupBy(DB::raw('YEAR(entry_date)'), 'scope')
            ->get();

        $byYear = [];
        foreach ($rows as $r) {
            $y = (int) $r->yr;
            $byYear[$y] ??= ['scope1' => 0.0, 'scope2' => 0.0, 'scope3' => 0.0, 'total' => 0.0];
            $byYear[$y]['scope' . (int) $r->scope] = (float) $r->total;
            $byYear[$y]['total'] += (float) $r->total;
        }
        krsort($byYear); // newest first

        $baseTotals = ($baseYear && isset($byYear[$baseYear])) ? $byYear[$baseYear] : null;

        $years = [];
        foreach ($byYear as $y => $t) {
            $change = null; // % vs base year total
            if ($baseTotals && $baseTotals['total'] > 0 && $y != $baseYear) {
                $change = round((($t['total'] - $baseTotals['total']) / $baseTotals['total']) * 100, 1);
            }

            $years[] = [
                'year'    => $y,
                'is_base' => $y == $baseYear,
                'scope1'  => round($t['scope1'], 2),
                'scope2'  => round($t['scope2'], 2),
                'scope3'  => round($t['scope3'], 2),
                'total'   => round($t['total'], 2),
                'change'  => $change,
            ];
        }

        return view('reports.base_year_comparison', [
            'years'      => $years,
            'baseYear'   => $baseYear,
            'hasBaseData' => $baseTotals !== null,
        ]);
    }
}
