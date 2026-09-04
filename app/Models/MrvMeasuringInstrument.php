<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * A measurement point for a facility/year — EAD 3d2(c) and 3e2(b).
 *
 * Where a continuous emission monitoring system sits: the stack, or the
 * pipeline cross-section whose CO2 flow is measured directly rather than
 * calculated from fuel consumed. Carries both halves the workbook asks for —
 * the instrument's specification (range, specified uncertainty, the part of the
 * range actually used) and the descriptive side (which emission source it
 * serves, and the procedures governing it).
 */
class MrvMeasuringInstrument extends Model
{
    use Auditable, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'facility_id',
        'reporting_year',
        'instrument_code',
        'source_stream_code',
        'type',
        'location_id',
        'range_unit',
        'range_lower',
        'range_upper',
        'specified_uncertainty_pct',
        'use_range_lower',
        'use_range_upper',

        // 3e2(b) — the descriptive half of a measurement point: which emission
        // source it serves, and the procedures governing it.
        'emission_source_code',
        'procedures',
        'relevant_procedures',
        'relevant_source',
    ];

    protected $casts = [
        'reporting_year' => 'integer',
        'range_lower' => 'decimal:6',
        'range_upper' => 'decimal:6',
        'specified_uncertainty_pct' => 'decimal:4',
        'use_range_lower' => 'decimal:6',
        'use_range_upper' => 'decimal:6',
    ];

    public function facility()
    {
        return $this->belongsTo(Facilities::class, 'facility_id');
    }
}
