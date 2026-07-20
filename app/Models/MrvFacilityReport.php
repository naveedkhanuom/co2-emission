<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * MRV submission header for one facility + reporting year (EAD "Deliverable C").
 * Holds the descriptive/governance sheets as JSON; numeric source data lives in
 * MrvEmissionSource / MrvSourceStream / MrvMeasuringInstrument.
 */
class MrvFacilityReport extends Model
{
    use HasCompanyScope, Auditable;

    protected $fillable = [
        'company_id',
        'facility_id',
        'reporting_year',
        'status',
        'estimated_annual_co2e',
        'estimation_justification',
        'contacts',
        'products',
        'methane_present',
        'methane',
        'verification_text',
        'data_gaps',
        'management',
        'mitigation_measures',
        'created_by',
    ];

    protected $casts = [
        'reporting_year' => 'integer',
        'estimated_annual_co2e' => 'decimal:4',
        'methane_present' => 'boolean',
        'contacts' => 'array',
        'products' => 'array',
        'methane' => 'array',
        'data_gaps' => 'array',
        'management' => 'array',
        'mitigation_measures' => 'array',
    ];

    public function facility()
    {
        return $this->belongsTo(Facilities::class, 'facility_id');
    }

    public function emissionSources()
    {
        return $this->hasMany(MrvEmissionSource::class, 'facility_id', 'facility_id')
            ->where('mrv_emission_sources.reporting_year', $this->reporting_year);
    }

    public function sourceStreams()
    {
        return $this->hasMany(MrvSourceStream::class, 'facility_id', 'facility_id')
            ->where('mrv_source_streams.reporting_year', $this->reporting_year);
    }
}
