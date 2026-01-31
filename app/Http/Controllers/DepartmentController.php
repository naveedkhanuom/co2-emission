<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Facilities;
use Illuminate\Http\Request;
use App\Helpers\CompanyHelper;

class DepartmentController extends Controller
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
        // Departments and facilities are automatically scoped by HasCompanyScope trait
        $departments = Department::with('facility')->latest()->get();
        $facilities  = Facilities::all();

        return view('departments.index', compact('departments', 'facilities'));
    }

    public function store(Request $request)
    {
        $companyId = $this->getCurrentCompanyId();
        
        if (!$companyId) {
            return back()->with('error', 'No company selected. Please select a company first.');
        }
        
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'name'        => 'required|string|max:255',
        ]);
        
        // Verify facility belongs to current company
        $facility = Facilities::find($request->facility_id);
        if (!$facility || $facility->company_id != $companyId) {
            return back()->with('error', 'Invalid facility selected.');
        }

        Department::create([
            'company_id' => $companyId,
            'facility_id' => $request->facility_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Department added successfully');
    }

    public function update(Request $request, Department $department)
    {
        $companyId = $this->getCurrentCompanyId();
        
        // Ensure department belongs to current company
        if ($companyId && $department->company_id != $companyId) {
            return back()->with('error', 'You do not have access to this department.');
        }
        
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'name'        => 'required|string|max:255',
        ]);
        
        // Verify facility belongs to current company
        $facility = Facilities::find($request->facility_id);
        if (!$facility || $facility->company_id != $companyId) {
            return back()->with('error', 'Invalid facility selected.');
        }

        $department->update([
            'facility_id' => $request->facility_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        // Ensure department belongs to current company
        $companyId = $this->getCurrentCompanyId();
        if ($companyId && $department->company_id != $companyId) {
            return back()->with('error', 'You do not have access to this department.');
        }
        
        $department->delete();
        return back()->with('success', 'Department deleted successfully');
    }
}
