<?php

namespace App\Http\Controllers;

use App\Models\Facilities;
use Illuminate\Http\Request;

class FacilitiesController extends Controller
{
    public function index()
       {
           $facilities = Facilities::latest()->get();
           return view('facilities.index', compact('facilities'));
       }

       public function store(Request $request)
       {
           $request->validate([
               'name' => 'required|string|max:255'
           ]);

           Facilities::create($request->all());

           return redirect()->back()->with('success', 'Facility added successfully');
       }

       public function update(Request $request, Facilities $facility)
       {
           $request->validate([
               'name' => 'required|string|max:255'
           ]);

           $facility->update($request->only('name'));

           return redirect()->back()->with('success', 'Facility updated successfully');
       }

       public function destroy(Facilities $facility)
       {
           $facility->delete();
           return redirect()->back()->with('success', 'Facility deleted successfully');
       }
}
