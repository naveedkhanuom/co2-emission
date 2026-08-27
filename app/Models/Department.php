<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

    protected $fillable = [
        'company_id',
        'facility_id',
        'name',
        'description',
    ];

    /**
     * Keep the denormalised name on emission records in step with this row.
     * Same reasoning as Facilities: a rename must not orphan the history
     * filed under the old spelling. Cascades by department_id only.
     */
    protected static function booted(): void
    {
        static::updated(function (self $department) {
            if (! $department->wasChanged('name')) {
                return;
            }

            EmissionRecord::withoutGlobalScope('company')
                ->where('company_id', $department->company_id)
                ->where('department_id', $department->id)
                ->update(['department' => $department->name]);
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facilities::class);
    }
}
