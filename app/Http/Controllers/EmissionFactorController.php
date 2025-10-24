<?php

namespace App\Http\Controllers;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class EmissionFactorController extends Controller
{
    public function index()
    {
        $sources = EmissionSource::all();
        return view('emission_factors.index', compact('sources'));
    }

    public function getData()
    {
        $data = EmissionFactor::with('emissionSource')->select('emission_factors.*');
        return DataTables::of($data)
            ->addColumn('source_name', fn($row) => $row->emissionSource?->name ?? 'N/A')
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
        $validated = $request->validate([
            'emission_source_id' => 'required|exists:emission_sources,id',
            'unit' => 'required|string|max:255',
            'factor_value' => 'required|numeric',
            'region' => 'nullable|string|max:255',
        ]);

        EmissionFactor::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json(['message' => $request->id ? 'Emission Factor updated!' : 'Emission Factor added!']);
    }

    public function destroy($id)
    {
        EmissionFactor::findOrFail($id)->delete();
        return response()->json(['message' => 'Emission Factor deleted successfully!']);
    }
}
