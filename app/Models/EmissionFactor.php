<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Auditable;

class EmissionFactor extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'emission_source_id',
        'organization_id',
        'country_id',
        'unit',
        'factor_value',
        'region',
        'dataset_name',
        'dataset_version',
        'valid_from',
        'valid_to',
        'is_active',
        'gwp_version',
        'source_reference',
        'co2_factor',
        'ch4_factor',
        'n2o_factor',
        'biogenic_co2_factor',
        // MRV layer — optional decomposed components (EU-ETS / EAD).
        'net_calorific_value',
        'ncv_unit',
        'ef_per_energy',
        'ef_per_energy_unit',
        'oxidation_factor',
        'conversion_factor',
        'ipcc_reference',
    ];

    protected $casts = [
        'factor_value' => 'decimal:6',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
        'co2_factor' => 'decimal:8',
        'ch4_factor' => 'decimal:8',
        'n2o_factor' => 'decimal:8',
        'biogenic_co2_factor' => 'decimal:8',
        'net_calorific_value' => 'decimal:8',
        'ef_per_energy' => 'decimal:8',
        'oxidation_factor' => 'decimal:6',
        'conversion_factor' => 'decimal:6',
    ];

    public function emissionSource()
    {
        return $this->belongsTo(EmissionSource::class);
    }

    public function organization()
    {
        return $this->belongsTo(FactorOrganization::class, 'organization_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Whether this factor carries a per-gas breakdown (CO2/CH4/N2O). */
    public function hasGasBreakdown(): bool
    {
        return $this->co2_factor !== null
            || $this->ch4_factor !== null
            || $this->n2o_factor !== null;
    }

    /**
     * Whether this factor carries the decomposed EU-ETS / EAD inputs
     * (NCV × EF × oxidation) needed for a regulated MRV calculation. When false,
     * the combined `factor_value` is used as today.
     */
    public function hasDecomposedComponents(): bool
    {
        return $this->net_calorific_value !== null
            && $this->ef_per_energy !== null;
    }

    /** Short provenance label, e.g. "DEFRA 2024". */
    public function datasetLabel(): ?string
    {
        $parts = array_filter([$this->dataset_name, $this->dataset_version]);
        return empty($parts) ? null : implode(' ', $parts);
    }
}

