<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * A source stream ("F01") under the calculation-based approach (EAD 2c2(e), 3d1,
 * 3d2). Carries the decomposed calculation inputs plus EU-ETS tier + uncertainty.
 */
class MrvSourceStream extends Model
{
    use HasCompanyScope, Auditable;

    protected $fillable = [
        'company_id',
        'facility_id',
        'reporting_year',
        'stream_code',
        'description',
        'emission_source_code',
        'classification',
        'fuel_type',
        'activity_level',
        'activity_unit',
        'combustion_device',
        'device_capacity',
        'device_capacity_unit',
        'materiality',
        'tier_level',
        'uncertainty_pct',
        'accuracy_source',
        'net_calorific_value',
        'ncv_unit',
        'emission_factor_value',
        'ef_unit',
        'oxidation_factor',
        'conversion_factor',
        'emission_factor_id',
        'information_source',
        'estimated_co2e',
    ];

    protected $casts = [
        'reporting_year' => 'integer',
        'activity_level' => 'decimal:6',
        'device_capacity' => 'decimal:6',
        'tier_level' => 'integer',
        'uncertainty_pct' => 'decimal:4',
        'net_calorific_value' => 'decimal:8',
        'emission_factor_value' => 'decimal:8',
        'oxidation_factor' => 'decimal:6',
        'conversion_factor' => 'decimal:6',
        'estimated_co2e' => 'decimal:4',
    ];

    public function facility()
    {
        return $this->belongsTo(Facilities::class, 'facility_id');
    }

    public function emissionFactor()
    {
        return $this->belongsTo(EmissionFactor::class, 'emission_factor_id');
    }

    /** Whether this stream has the decomposed inputs for the EU-ETS / EAD formula. */
    public function hasDecomposedInputs(): bool
    {
        return $this->net_calorific_value !== null
            && $this->emission_factor_value !== null;
    }
}
