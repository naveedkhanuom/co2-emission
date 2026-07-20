<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * A measuring instrument register entry for a facility/year (EAD 3d2(c), 3e2).
 * Phase 1 is descriptive; the measurement-based numeric path is phase 2.
 */
class MrvMeasuringInstrument extends Model
{
    use HasCompanyScope, Auditable;

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
