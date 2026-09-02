<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One run of `factors:import` — the provenance record behind a batch of
 * emission factors.
 *
 * Deliberately NOT company-scoped: a published factor set belongs to the tenant,
 * not to one company inside it, the same way emission_factors and
 * emission_sources are shared reference data.
 */
class FactorImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_name',
        'dataset_version',
        'organization_id',
        'source_file',
        'source_file_hash',
        'source_url',
        'gwp_version',
        'factors_written',
        'sources_created',
        'factors_superseded',
        'imported_at',
        'imported_by',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'factors_written' => 'integer',
        'sources_created' => 'integer',
        'factors_superseded' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(FactorOrganization::class, 'organization_id');
    }

    public function emissionFactors()
    {
        return $this->hasMany(EmissionFactor::class, 'factor_import_id');
    }

    /** Human label for a report footer or the factor library screen. */
    public function label(): string
    {
        return trim($this->dataset_name.' '.$this->dataset_version);
    }
}
