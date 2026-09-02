<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorOrganization;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmissionFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-emission-factors|create-emission-factor|edit-emission-factor|delete-emission-factor', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:create-emission-factor|edit-emission-factor', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:delete-emission-factor', ['only' => ['destroy']]);
    }

    public function index()
    {
        $sources = EmissionSource::orderBy('name')->get();
        $factorOrganizations = FactorOrganization::orderBy('name')->get();
        $countries = Country::where('is_active', true)->orderBy('name')->get();
        $countrySpecificOrgId = FactorOrganization::where('code', 'COUNTRY')->value('id');

        return view('emission_factors.index', compact(
            'sources',
            'factorOrganizations',
            'countries',
            'countrySpecificOrgId'
        ) + [
            // Filter options read from the data rather than hardcoded: which
            // datasets and units exist depends on what has been imported, and a
            // fixed list would quietly stop offering DEFRA the day someone
            // imports EPA.
            'datasets' => EmissionFactor::query()
                ->whereNotNull('dataset_name')
                ->select('dataset_name')
                ->distinct()
                ->orderBy('dataset_name')
                ->pluck('dataset_name'),

            // Capped: DEFRA alone brings ~90 distinct units, and a select with
            // every one of them is not a filter anyone can use. The common ones
            // cover almost every row; the rest are reachable by search.
            'units' => EmissionFactor::query()
                ->select('unit')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('unit')
                ->orderByDesc('total')
                ->limit(30)
                ->pluck('unit'),
        ]);
    }

    public function getData(Request $request)
    {
        // Joined rather than eager-loaded alone, so the source name and scope are
        // real columns: searching and sorting them then happens in MySQL instead
        // of over one page of already-fetched rows. With a seeded library of a
        // few hundred that distinction did not matter; after importing DEFRA it
        // is thousands, and "source_name" as an addColumn was silently
        // unsearchable — typing "diesel" matched nothing.
        $data = EmissionFactor::query()
            ->with(['emissionSource', 'organization', 'country'])
            ->leftJoin('emission_sources', 'emission_sources.id', '=', 'emission_factors.emission_source_id')
            ->select([
                'emission_factors.*',
                'emission_sources.name as source_name',
                'emission_sources.scope as source_scope',
            ]);

        $this->applyFilters($data, $request);

        return DataTables::of($data)
            ->addColumn('organization_name', fn ($row) => $row->organization?->code ?? $row->organization?->name ?? '—')
            ->addColumn('country_name', fn ($row) => $row->country?->code ?? $row->country?->name ?? '')
            ->addColumn('dataset', fn ($row) => $row->datasetLabel() ?? '—')
            ->addColumn('status', function ($row) {
                // Superseded rows are kept on purpose: a figure computed against
                // one still points at it, and that row has to keep saying what it
                // said. Labelled so nobody enters new data against a retired
                // factor by accident.
                return $row->is_active
                    ? '<span class="badge bg-success-subtle text-success">Active</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary" title="Superseded'
                        .($row->valid_to ? ' on '.$row->valid_to->format('j M Y') : '')
                        .'">Superseded</span>';
            })
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-warning editBtn" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>
                ';
            })
            // Global search across the fields someone would actually type into:
            // the activity, its unit, the region, and the published citation.
            ->filterColumn('source_name', function ($query, $keyword) {
                $query->where('emission_sources.name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Narrow the factor list.
     *
     * Every filter is optional and additive. `status` is the one with an opinion:
     * it defaults to active, because a library holding several editions of the
     * same factor would otherwise show each activity two or three times over with
     * no indication which one is in force.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->filled('organization_id'), fn ($q) => $q->where('emission_factors.organization_id', $request->integer('organization_id')))
            ->when($request->filled('scope'), fn ($q) => $q->where('emission_sources.scope', $request->integer('scope')))
            ->when($request->filled('country_id'), fn ($q) => $q->where('emission_factors.country_id', $request->integer('country_id')))
            ->when($request->filled('unit'), fn ($q) => $q->where('emission_factors.unit', $request->string('unit')->toString()))
            ->when($request->filled('dataset_name'), fn ($q) => $q->where('emission_factors.dataset_name', $request->string('dataset_name')->toString()))
            ->when($request->filled('gwp_version'), fn ($q) => $q->where('emission_factors.gwp_version', $request->string('gwp_version')->toString()));

        // Rows with a per-gas breakdown are the ones usable for regulated MRV
        // reporting, which needs emissions decomposed by gas rather than a single
        // CO2e figure. Worth being able to see what qualifies.
        if ($request->boolean('has_breakdown')) {
            $query->whereNotNull('emission_factors.co2_factor');
        }

        match ($request->string('status')->toString()) {
            'superseded' => $query->where('emission_factors.is_active', false),
            'all' => null,
            default => $query->where('emission_factors.is_active', true),
        };
    }

    public function show($id)
    {
        return EmissionFactor::findOrFail($id);
    }

    public function storeOrUpdate(Request $request)
    {
        if ($request->has('id') && (string) $request->id === '') {
            $request->merge(['id' => null]);
        }

        $countrySpecificOrgId = FactorOrganization::where('code', 'COUNTRY')->value('id');
        $rules = [
            'id' => 'nullable|integer|exists:emission_factors,id',
            'emission_source_id' => 'required|exists:emission_sources,id',
            'organization_id' => 'required|exists:factor_organizations,id',
            'country_id' => 'nullable|integer|exists:countries,id',
            'unit' => 'required|string|max:255',
            'factor_value' => 'required|numeric',
            'region' => 'nullable|string|max:255',
            // Versioning / provenance
            'dataset_name' => 'nullable|string|max:255',
            'dataset_version' => 'nullable|string|max:50',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'nullable|boolean',
            'gwp_version' => 'nullable|in:ar4,ar5,ar6',
            'source_reference' => 'nullable|string|max:255',
            // Per-gas factors (kg of gas per activity unit)
            'co2_factor' => 'nullable|numeric|min:0',
            'ch4_factor' => 'nullable|numeric|min:0',
            'n2o_factor' => 'nullable|numeric|min:0',
            'biogenic_co2_factor' => 'nullable|numeric|min:0',
        ];
        if ($countrySpecificOrgId && (int) $request->organization_id === (int) $countrySpecificOrgId) {
            $rules['country_id'] = 'required|integer|exists:countries,id';
        }
        $validated = $request->validate($rules);

        $id = $request->filled('id') ? (int) $request->id : null;

        $payload = collect($validated)->except('id')->all();
        // Checkboxes are absent when unticked — resolve explicitly.
        $payload['is_active'] = $request->boolean('is_active', true);

        EmissionFactor::updateOrCreate(
            ['id' => $id],
            $payload
        );

        return response()->json(['message' => $id ? 'Emission Factor updated!' : 'Emission Factor added!']);
    }

    public function destroy($id)
    {
        EmissionFactor::findOrFail($id)->delete();

        return response()->json(['message' => 'Emission Factor deleted successfully!']);
    }
}
