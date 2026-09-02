<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionFactor extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'emission_source_id',
        'organization_id',
        // Without this, mass assignment silently drops the link to the import
        // run — the row still saves, and its provenance is quietly gone.
        'factor_import_id',
        'country_id',
        'unit',
        'factor_value',
        // Which unit factor_value is IN. Without this in $fillable an importer's
        // basis is silently dropped on mass assignment and the row falls back to
        // being read as tonnes — the exact 1000x error the column exists to stop.
        'factor_unit',
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
        'factor_value' => 'decimal:10',
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

    /**
     * The unit `factor_value` is expressed in, when the row does not say.
     *
     * Tonnes, because every row that predates the `factor_unit` column was
     * compiled through BuiltInFactorCatalog, which divides by 1000. A new row
     * should always state its basis; this default only covers history.
     */
    public const DEFAULT_UNIT = 'tCO2e';

    /** Publisher basis: the factor is kg of CO2e per activity unit. */
    public const UNIT_KG = 'kgCO2e';

    /** Application basis: the factor is tonnes of CO2e per activity unit. */
    public const UNIT_TONNE = 'tCO2e';

    /**
     * `factor_value` converted to tCO2e per activity unit — the basis every
     * stored `emission_records.co2e_value` is in.
     *
     * This is the "callers convert at the point of use" step that
     * DefraFlatFileImporter's docblock promises. It did not exist, so a DEFRA
     * factor (kgCO2e, as published) was read as though it were tonnes and
     * overstated the figure by 1000x.
     *
     * An unrecognised basis is treated as tonnes rather than guessed at or
     * thrown on: the fleet's existing rows are tonnes, and a new dataset that
     * forgets to declare its unit should produce a figure that is merely
     * unconverted, not an exception in the middle of saving a record. Add the
     * basis here when adding an importer.
     */
    public function valueInTonnes(): float
    {
        $value = (float) $this->factor_value;

        return match ($this->factor_unit ?: self::DEFAULT_UNIT) {
            self::UNIT_KG => $value / 1000,
            default => $value,
        };
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

    /** Short provenance label for a list column, e.g. "DEFRA/DESNZ 2026". */
    public function datasetLabel(): ?string
    {
        $parts = array_filter([$this->dataset_name, $this->dataset_version]);

        return empty($parts) ? null : implode(' ', $parts);
    }

    /**
     * Full provenance, for storing on a record: the dataset AND the publisher's
     * own citation.
     *
     * The distinction matters. "Built-in catalogue 2026.1" names the edition;
     * "IPCC 74100 kgCO2/TJ, NCV 26.5 GJ/t" is the citation an assurer actually
     * checks the number against. Enrichment used to stamp records with the short
     * label alone, which silently dropped the citation the resolver had already
     * found — provenance that got shorter as the pipeline got better.
     */
    public function provenanceLabel(): ?string
    {
        $parts = array_filter([
            $this->datasetLabel(),
            $this->source_reference ?: null,
        ]);

        return empty($parts) ? null : implode(' — ', $parts);
    }
}
