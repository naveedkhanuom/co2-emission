<?php

namespace App\Http\Controllers;

use App\Models\EmissionSource;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmissionSourceController extends Controller
{
    /**
     * Display the main view.
     */
    public function index()
    {
        return view('emission_sources.index');
    }

    /**
     * Server-side DataTables response.
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = EmissionSource::select(['id', 'name', 'scope', 'description', 'created_at']);

            return DataTables::of($query)
                ->addColumn('actions', function ($row) {
                    return '
                        <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning editBtn" data-id="'.$row->id.'">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
    }

    /**
     * Create or update an emission source.
     */
    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scope' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $emissionSource = EmissionSource::updateOrCreate(
            ['id' => $request->input('emission_source_id')],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => $request->emission_source_id
                ? 'Emission Source updated successfully!'
                : 'Emission Source added successfully!',
            'data' => $emissionSource,
        ]);
    }

    /**
     * Show one record for view/edit modal.
     */
    public function show($id)
    {
        $source = EmissionSource::findOrFail($id);
        return response()->json($source);
    }

    /**
     * Delete a record.
     */
    public function destroy($id)
    {
        $source = EmissionSource::findOrFail($id);
        $source->delete();

        return response()->json(['success' => true, 'message' => 'Emission Source deleted successfully!']);
    }
}
