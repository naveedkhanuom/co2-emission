<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * A physical emission source ("S01") for a facility/year (EAD 2c2(d), 3e1),
 * independent of monitoring method.
 */
class MrvEmissionSource extends Model
{
    use HasCompanyScope, Auditable;

    protected $fillable = [
        'company_id',
        'facility_id',
        'reporting_year',
        'source_code',
        'name',
        'description',
        'associated_product',
        'ghg_types',
        'energy_related',
        'process_emissions',
        'methodology',
        'materiality',
        'total_co2e',
    ];

    protected $casts = [
        'reporting_year' => 'integer',
        'energy_related' => 'boolean',
        'process_emissions' => 'boolean',
        'total_co2e' => 'decimal:4',
    ];

    public function facility()
    {
        return $this->belongsTo(Facilities::class, 'facility_id');
    }

    public function sourceStreams()
    {
        return $this->hasMany(MrvSourceStream::class, 'emission_source_code', 'source_code')
            ->where('mrv_source_streams.facility_id', $this->facility_id)
            ->where('mrv_source_streams.reporting_year', $this->reporting_year);
    }
}
