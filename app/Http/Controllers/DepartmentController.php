<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Facilities;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('facility')->latest()->get();
        $facilities  = Facilities::all();

        return view('departments.index', compact('departments', 'facilities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'name'        => 'required|string|max:255',
        ]);

        Department::create($request->only(
            'facility_id',
            'name',
            'description'
        ));

        return back()->with('success', 'Department added successfully');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'name'        => 'required|string|max:255',
        ]);

        $department->update($request->only(
            'facility_id',
            'name',
            'description'
        ));

        return back()->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return back()->with('success', 'Department deleted successfully');
    }
}
