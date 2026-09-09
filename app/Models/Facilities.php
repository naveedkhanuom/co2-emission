<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

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
        'primary_sector_other',
        'primary_activity',
    ];

    protected $casts = [
        'mrv_enabled' => 'boolean',
    ];

    /**
     * Keep the denormalised name on emission records in step with this row.
     *
     * Emission records carry both facility_id and the facility name that was
     * typed at the time, because reporting and analytics still group by the
     * name. Renaming a facility used to leave every historical record filed
     * under the old spelling, with nothing linking them back — the rename
     * silently split one site into two.
     *
     * Cascades by facility_id, never by matching the old name: a record with
     * no id was never linked to this facility, and renaming it here would be
     * a guess rather than a correction.
     */
    protected static function booted(): void
    {
        static::updated(function (self $facility) {
            if (! $facility->wasChanged('name')) {
                return;
            }

            EmissionRecord::withoutGlobalScope('company')
                ->where('company_id', $facility->company_id)
                ->where('facility_id', $facility->id)
                ->update(['facility' => $facility->name]);
        });
    }

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
