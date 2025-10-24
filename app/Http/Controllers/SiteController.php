<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Company;
use Illuminate\Http\Request;
use DataTables;

class SiteController extends Controller
{
    // Show Sites page
    public function index()
    {
        $companies = Company::all();
        return view('sites.index', compact('companies'));
    }

    // Server-side Datatable
    public function getSites(Request $request)
    {
        $sites = Site::with('company')->select('sites.*');
        return DataTables::of($sites)
            ->addColumn('company', function(Site $site){
                return $site->company->name ?? '';
            })
            ->addColumn('actions', function(Site $site){
                $btn = '<button class="btn btn-sm btn-info viewBtn" data-id="'.$site->id.'">View</button> ';
                $btn .= '<button class="btn btn-sm btn-primary editBtn" data-id="'.$site->id.'">Edit</button> ';
                $btn .= '<button class="btn btn-sm btn-danger deleteBtn" data-id="'.$site->id.'">Delete</button>';
                return $btn;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    // Store or Update
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
        ]);

        $site = Site::updateOrCreate(
            ['id' => $request->site_id],
            $request->only('company_id', 'name', 'location', 'latitude', 'longitude', 'description')
        );

        $message = $request->site_id ? 'Site updated successfully' : 'Site added successfully';

        return response()->json(['message' => $message]);
    }

    // Show single site
    public function show($id)
    {
        $site = Site::with('company')->findOrFail($id);
        return response()->json($site);
    }

    // Delete site
    public function destroy($id)
    {
        $site = Site::findOrFail($id);
        $site->delete();
        return response()->json(['message' => 'Site deleted successfully']);
    }
}


