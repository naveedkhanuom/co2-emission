<?php

namespace App\Http\Controllers;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorOrganization;
use App\Models\Country;
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
        return view('emission_factors.index', compact('sources', 'factorOrganizations', 'countries', 'countrySpecificOrgId'));
    }

    public function getData()
    {
        $data = EmissionFactor::with(['emissionSource', 'organization', 'country'])->select('emission_factors.*');
        return DataTables::of($data)
            ->addColumn('source_name', fn($row) => $row->emissionSource?->name ?? 'N/A')
            ->addColumn('organization_name', fn($row) => $row->organization?->code ?? $row->organization?->name ?? '—')
            ->addColumn('country_name', fn($row) => $row->country?->code ?? $row->country?->name ?? '')
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-warning editBtn" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
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
        ];
        if ($countrySpecificOrgId && (int) $request->organization_id === (int) $countrySpecificOrgId) {
            $rules['country_id'] = 'required|integer|exists:countries,id';
        }
        $validated = $request->validate($rules);

        $id = $request->filled('id') ? (int) $request->id : null;

        EmissionFactor::updateOrCreate(
            ['id' => $id],
            collect($validated)->except('id')->all()
        );

        return response()->json(['message' => $id ? 'Emission Factor updated!' : 'Emission Factor added!']);
    }

    public function destroy($id)
    {
        EmissionFactor::findOrFail($id)->delete();
        return response()->json(['message' => 'Emission Factor deleted successfully!']);
    }
}

