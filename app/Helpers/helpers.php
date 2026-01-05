<?php

use App\Models\Facilities as Facility;
use App\Models\Department;

if (!function_exists('facilities')) {
    function facilities($all = true)
    {
        if ($all) {
            return Facility::all();
        }
        return Facility::query(); // allows further chaining
    }
}


if (!function_exists('departments')) {
    function departments($facilityId = null)
    {
        $query = Department::query();

        if ($facilityId) {
            $query->where('facility_id', $facilityId);
        }

        return $query->get();
    }
}