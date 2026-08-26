<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Facilities extends Model
{
    use Auditable, HasCompanyScope;
    
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'address',
        'city',
        'state',
        'country',
        // MRV layer — opt-in regulated facility (EAD / EU-ETS).
        'mrv_enabled',
        'economic_licence_number',
        'environmental_permit_no',
        'parent_entity',
        'coordinates',
        'primary_sector',
        'primary_activity',
    ];

    protected $casts = [
        'mrv_enabled' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'facility_id');
    }

    public function mrvReports()
    {
        return $this->hasMany(MrvFacilityReport::class, 'facility_id');
    }
}
