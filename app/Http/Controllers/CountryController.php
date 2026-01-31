<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CountryController extends Controller
{
    public function index()
    {
        return view('countries.index');
    }

    public function getData()
    {
        return DataTables::of(Country::query())
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
        return Country::findOrFail($id);
    }

    public function storeOrUpdate(Request $request)
    {
        if ($request->has('id') && (string) $request->id === '') {
            $request->merge(['id' => null]);
        }

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:countries,id',
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $id = $request->filled('id') ? (int) $request->id : null;

        Country::updateOrCreate(
            ['id' => $id],
            [
                'code' => strtoupper(trim($validated['code'])),
                'name' => $validated['name'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return response()->json(['message' => $id ? 'Country updated!' : 'Country added!']);
    }

    public function destroy($id)
    {
        Country::findOrFail($id)->delete();
        return response()->json(['message' => 'Country deleted successfully!']);
    }
}

