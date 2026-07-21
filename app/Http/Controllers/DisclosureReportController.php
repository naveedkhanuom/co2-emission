<?php

namespace App\Http\Controllers;

use App\Exports\DisclosureExport;
use App\Models\CompanySetting;
use App\Services\DisclosureReportService;
use App\Support\Gwp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Regulatory disclosure exports — CSRD/ESRS E1, CDP and GRI 305 — built from one
 * consistent emission inventory. Reuses the reports permission set.
 */
class DisclosureReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-reports|create-report|edit-report|delete-report');
    }

    public function index(Request $request, DisclosureReportService $service)
    {
        $year = (int) $request->get('year', date('Y'));
        $framework = $this->resolveFramework($request->get('framework'));
        $companyId = (int) (current_company_id() ?? auth()->user()->company_id);

        $gwpVersion = $this->resolveAndPersistGwp($request, $companyId);

        $data = $service->build($companyId, $year, $gwpVersion);
        $datapoints = $service->datapoints($framework, $data);

        return view('reports.disclosure.index', [
            'frameworks'   => DisclosureReportService::FRAMEWORKS,
            'framework'    => $framework,
            'frameworkLabel' => DisclosureReportService::FRAMEWORKS[$framework],
            'gwpOptions'   => Gwp::options(),
            'gwpVersion'   => $gwpVersion,
            'year'         => $year,
            'years'        => range((int) date('Y'), (int) date('Y') - 6),
            'data'         => $data,
            'datapoints'   => $datapoints,
        ]);
    }

    public function export(Request $request, DisclosureReportService $service)
    {
        $year = (int) $request->get('year', date('Y'));
        $framework = $this->resolveFramework($request->get('framework'));
        $format = in_array($request->get('format'), ['pdf', 'excel', 'csv'], true) ? $request->get('format') : 'pdf';
        $companyId = (int) (current_company_id() ?? auth()->user()->company_id);

        $gwpVersion = $this->resolveAndPersistGwp($request, $companyId);

        $data = $service->build($companyId, $year, $gwpVersion);
        $datapoints = $service->datapoints($framework, $data);
        $label = DisclosureReportService::FRAMEWORKS[$framework];

        $slug = str_replace('_', '-', $framework) . '-' . $year;

        if ($format === 'excel' || $format === 'csv') {
            $writer = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
            return Excel::download(
                new DisclosureExport($datapoints, $label),
                $slug . '.' . ($format === 'csv' ? 'csv' : 'xlsx'),
                $writer
            );
        }

        $pdf = Pdf::loadView('reports.disclosure.pdf', [
            'framework'      => $framework,
            'frameworkLabel' => $label,
            'data'           => $data,
            'datapoints'     => $datapoints,
        ])->setPaper('a4');

        return $pdf->download($slug . '.pdf');
    }

    private function resolveFramework(?string $framework): string
    {
        return array_key_exists($framework, DisclosureReportService::FRAMEWORKS) ? $framework : 'esrs_e1';
    }

    /**
     * Resolve the GWP version for this request. When the user explicitly picks
     * one, persist it as the company's reporting default for future filings.
     */
    private function resolveAndPersistGwp(Request $request, int $companyId): string
    {
        $requested = $request->get('gwp_version');
        if (Gwp::isValid($requested)) {
            CompanySetting::updateOrCreate(
                ['company_id' => $companyId, 'key' => 'gwp_version'],
                ['value' => $requested, 'type' => 'string', 'description' => 'Default GWP assessment set for disclosures']
            );
            return $requested;
        }

        return Gwp::versionForCompany($companyId);
    }
}
