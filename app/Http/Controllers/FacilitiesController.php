<?php

namespace App\Http\Controllers;

use App\Models\Facilities;
use Illuminate\Http\Request;
use App\Helpers\CompanyHelper;

class FacilitiesController extends Controller
{
    /**
     * Get current company ID from context.
     */
    protected function getCurrentCompanyId()
    {
        return CompanyHelper::currentCompanyId();
    }
    
    public function index()
    {
        // Facilities are automatically scoped by HasCompanyScope trait
        $facilities = Facilities::latest()->get();
        return view('facilities.index', compact('facilities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $companyId = $this->getCurrentCompanyId();
        
        if (!$companyId) {
            return redirect()->back()->with('error', 'No company selected. Please select a company first.');
        }

        Facilities::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
        ]);

        return redirect()->back()->with('success', 'Facility added successfully');
    }

    public function update(Request $request, Facilities $facility)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        // Ensure facility belongs to current company
        $companyId = $this->getCurrentCompanyId();
        if ($companyId && $facility->company_id != $companyId) {
            return redirect()->back()->with('error', 'You do not have access to this facility.');
        }

        $facility->update($request->only('name', 'description', 'address', 'city', 'state', 'country'));

        return redirect()->back()->with('success', 'Facility updated successfully');
    }

    public function destroy(Facilities $facility)
    {
        // Ensure facility belongs to current company
        $companyId = $this->getCurrentCompanyId();
        if ($companyId && $facility->company_id != $companyId) {
            return redirect()->back()->with('error', 'You do not have access to this facility.');
        }
        
        $facility->delete();
        return redirect()->back()->with('success', 'Facility deleted successfully');
    }
}
