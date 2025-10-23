<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companies = Company::select(['id', 'name', 'industry_type', 'country', 'contact_person', 'email', 'phone', 'created_at']);
            return DataTables::of($companies)
                ->addColumn('action', function ($row) {
                    return '
                        <button class="px-2 py-1 bg-blue-500 text-white rounded text-xs viewBtn" data-id="'.$row->id.'">View</button>
                        <button class="px-2 py-1 bg-yellow-400 text-white rounded text-xs editBtn" data-id="'.$row->id.'">Edit</button>
                        <button class="px-2 py-1 bg-red-500 text-white rounded text-xs deleteBtn" data-id="'.$row->id.'">Delete</button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('companies.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        Company::updateOrCreate(
            ['id' => $request->id],
            $validated + $request->only(['industry_type','country','contact_person','email','phone'])
        );

        return response()->json(['success' => true, 'message' => 'Company saved successfully.']);
    }

    public function show($id)
    {
        $company = Company::findOrFail($id);
        return response()->json($company);
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return response()->json($company);
    }

    public function destroy($id)
    {
        Company::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Company deleted successfully.']);
    }
}
