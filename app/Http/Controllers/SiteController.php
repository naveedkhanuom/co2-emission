<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Company;
use Illuminate\Http\Request;
use DataTables;
use App\Helpers\CompanyHelper;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-sites|create-site|edit-site|delete-site', ['only' => ['index', 'getSites', 'show']]);
        $this->middleware('permission:create-site|edit-site', ['only' => ['storeOrUpdate']]);
        $this->middleware('permission:delete-site', ['only' => ['destroy']]);
    }

    /**
     * Get current company ID from context.
     */
    protected function getCurrentCompanyId()
    {
        return CompanyHelper::currentCompanyId();
    }
    
    // Show Sites page
    public function index()
    {
        // Get accessible companies for current user
        $user = auth()->user();
        $companies = $user ? $user->accessibleCompanies()->get() : collect();
        return view('sites.index', compact('companies'));
    }

    // Server-side Datatable
    public function getSites(Request $request)
    {
        // Sites are automatically scoped by HasCompanyScope trait
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
        $companyId = $this->getCurrentCompanyId();
        
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'No company selected. Please select a company first.'
            ], 400);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        // If updating, verify site belongs to current company
        if ($request->site_id) {
            $existingSite = Site::find($request->site_id);
            if ($existingSite && $existingSite->company_id != $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this site.'
                ], 403);
            }
        }

        $site = Site::updateOrCreate(
            ['id' => $request->site_id],
            [
                'company_id' => $companyId,
                'name' => $request->name,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'description' => $request->description,
            ]
        );

        $message = $request->site_id ? 'Site updated successfully' : 'Site added successfully';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $site
        ]);
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
        
        // Verify site belongs to current company
        $companyId = $this->getCurrentCompanyId();
        if ($companyId && $site->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this site.'
            ], 403);
        }
        
        $site->delete();
        return response()->json([
            'success' => true,
            'message' => 'Site deleted successfully'
        ]);
    }
}


