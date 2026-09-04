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
    use Auditable, HasCompanyScope;

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

        // 3e1(b) — accuracy of a MEASURED source. Distinct from the same-named
        // columns on MrvSourceStream: under a measurement approach the tier
        // describes the stack being measured, not a fuel stream entering it.
        'tier_level',
        'uncertainty_pct',
        'emission_stream_type',
        'accuracy_source',
        'total_co2e',
    ];

    protected $casts = [
        'reporting_year' => 'integer',
        'energy_related' => 'boolean',
        'process_emissions' => 'boolean',
        'total_co2e' => 'decimal:4',

        // Same casts MrvSourceStream gives the same columns. Without them one
        // model returns a tier as int and the other as a string, and the code
        // reading both has to remember which.
        'tier_level' => 'integer',
        'uncertainty_pct' => 'decimal:4',
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
