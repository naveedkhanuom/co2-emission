<?php

namespace App\Http\Controllers;

use App\Models\EnergyAttributeCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

/**
 * Manages market-based Scope 2 instruments (RECs, GOs, PPAs, supplier-specific
 * and residual-mix factors). These supply the market-based emission factor for
 * Scope 2 records and feed the GHG Protocol dual-reporting totals.
 */
class EnergyAttributeCertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-energy-certificates|create-energy-certificate|edit-energy-certificate|delete-energy-certificate', ['only' => ['index', 'getData', 'show', 'list']]);
        $this->middleware('permission:create-energy-certificate|edit-energy-certificate', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:delete-energy-certificate', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('energy_certificates.index', [
            'types' => EnergyAttributeCertificate::TYPES,
            'carriers' => EnergyAttributeCertificate::CARRIERS,
        ]);
    }

    public function getData()
    {
        $data = EnergyAttributeCertificate::query()->latest('id');

        return DataTables::of($data)
            ->addColumn('type_label', fn ($row) => $row->typeLabel())
            ->addColumn('factor_formatted', fn ($row) => number_format((float) $row->emission_factor, 4).' kgCO₂e/kWh')
            ->addColumn('volume_formatted', fn ($row) => number_format((float) $row->mwh_volume, 2).' MWh')
            ->addColumn('validity', function ($row) {
                $from = $row->valid_from?->format('Y-m-d') ?? '—';
                $to = $row->valid_to?->format('Y-m-d') ?? '—';

                return $from.' → '.$to;
            })
            ->addColumn('status_badge', function ($row) {
                $map = [
                    'active' => 'success', 'retired' => 'secondary',
                    'expired' => 'warning', 'cancelled' => 'danger',
                ];
                $cls = $map[$row->status] ?? 'secondary';

                return '<span class="badge bg-'.$cls.'">'.ucfirst($row->status).'</span>';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-warning editBtn" data-id="'.$row->id.'"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'"><i class="fas fa-trash"></i></button>
                ';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /** Lightweight list for select inputs on the Scope 2 entry screen. */
    public function list()
    {
        $items = EnergyAttributeCertificate::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'emission_factor', 'energy_carrier']);

        return response()->json($items);
    }

    public function show($id)
    {
        $cert = EnergyAttributeCertificate::findOrFail($id);
        if ($cert->company_id != current_company_id()) {
            abort(403, 'You do not have access to this certificate.');
        }

        return response()->json($cert);
    }

    public function storeOrUpdate(Request $request)
    {
        if ($request->has('id') && (string) $request->id === '') {
            $request->merge(['id' => null]);
        }

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:energy_attribute_certificates,id',
            'type' => 'required|string|in:'.implode(',', array_keys(EnergyAttributeCertificate::TYPES)),
            'name' => 'required|string|max:255',
            'certificate_number' => 'nullable|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'energy_carrier' => 'required|string|in:'.implode(',', array_keys(EnergyAttributeCertificate::CARRIERS)),
            'mwh_volume' => 'nullable|numeric|min:0',
            'emission_factor' => 'required|numeric|min:0',
            'region' => 'nullable|string|max:255',
            'vintage_year' => 'nullable|integer|min:1990|max:2100',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'required|string|in:active,retired,expired,cancelled',
            'notes' => 'nullable|string|max:1000',
            'document' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,csv',
        ]);

        $companyId = current_company_id();
        $id = $request->filled('id') ? (int) $request->id : null;

        $payload = collect($validated)->except(['id', 'document'])->all();
        $payload['company_id'] = $companyId;

        if ($request->hasFile('document')) {
            // PRIVATE disk: a REC/GO certificate is the evidence behind a
            // market-based Scope 2 claim, and the public disk is served by
            // /tenancy/assets/{path} with no authentication. Nothing builds a
            // URL to these today — they are referenced by filename only — so
            // moving them off the public disk costs nothing.
            $folder = 'energy-certificates/'.($companyId ?: 'unknown').'/'.now()->format('Y/m');
            $payload['document_path'] = $request->file('document')->store($folder, 'local');
        }

        if ($id) {
            $cert = EnergyAttributeCertificate::findOrFail($id);
            if ($cert->company_id != $companyId) {
                abort(403, 'You do not have access to this certificate.');
            }
            $cert->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            EnergyAttributeCertificate::create($payload);
        }

        return response()->json(['message' => $id ? 'Certificate updated!' : 'Certificate added!']);
    }

    public function destroy($id)
    {
        $cert = EnergyAttributeCertificate::findOrFail($id);
        if ($cert->company_id != current_company_id()) {
            abort(403, 'You do not have access to this certificate.');
        }

        // 'public' is still swept because certificates uploaded before these
        // were made private are still there; new ones only ever land on 'local'.
        foreach (['local', 'public'] as $disk) {
            if ($cert->document_path && Storage::disk($disk)->exists($cert->document_path)) {
                Storage::disk($disk)->delete($cert->document_path);
            }
        }

        $cert->delete();

        return response()->json(['message' => 'Certificate deleted successfully!']);
    }
}
