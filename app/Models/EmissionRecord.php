<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionRecord extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

    protected $fillable = [
        'entry_date',
        'company_id',
        'site_id',

        // facility/department are the names typed at the time; facility_id and
        // department_id are the identity. The names are kept in step with the
        // rows they point at — see the Facilities and Department models — so a
        // rename no longer orphans the history filed under the old spelling.
        'facility',
        'facility_id',
        'department_id',
        'scope',
        'scope3_category_id',
        'supplier_id',
        'emission_source',
        'activity_data',
        'activity_unit',
        'spend_amount',
        'spend_currency',
        'emission_factor',
        'market_based_factor',
        'emission_factor_id',
        'factor_dataset',
        'factor_organization_id',
        'calculation_method',
        'scope2_method',
        'data_quality',
        'co2e_value',
        'market_based_co2e',
        'energy_attribute_certificate_id',
        'gwp_version',
        'co2e_co2',
        'co2e_ch4',
        'co2e_n2o',
        'co2e_other',
        'biogenic_co2',
        'confidence_level',
        'department',
        'data_source',
        'notes',
        'supporting_documents',
        'created_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'activity_data' => 'decimal:4',
        'spend_amount' => 'decimal:2',
        'emission_factor' => 'decimal:10',
        'market_based_factor' => 'decimal:6',
        'co2e_value' => 'decimal:4',
        'market_based_co2e' => 'decimal:4',
        'co2e_co2' => 'decimal:4',
        'co2e_ch4' => 'decimal:4',
        'co2e_n2o' => 'decimal:4',
        'co2e_other' => 'decimal:4',
        'biogenic_co2' => 'decimal:4',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'supporting_documents' => 'array',
    ];

    /**
     * Resolve facility_id / department_id from the names being saved.
     *
     * Done on the model rather than in the controllers because records arrive
     * from five different places — the entry pages, quick add, the spreadsheet
     * import, AI document extraction, and supplier surveys — and a link that
     * only some of them set is worse than none, since the gaps are invisible.
     *
     * An id the caller set explicitly always wins; this only fills a blank.
     * A name that matches no facility leaves the id null, which is honest:
     * the record keeps the name someone typed and is visibly unlinked.
     */
    protected static function booted(): void
    {
        static::saving(function (self $record) {
            // HasCompanyScope stamps company_id on `creating`, which fires
            // after this, so a new record may not carry one yet.
            $companyId = $record->company_id
                ?: (function_exists('current_company_id') ? current_company_id() : null);

            if (! $companyId) {
                return;
            }

            if (! $record->isDirty('facility_id') && filled($record->facility)) {
                $record->facility_id = Facilities::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($record->facility))])
                    ->orderBy('id')
                    ->value('id');
            }

            if (! $record->isDirty('department_id') && filled($record->department)) {
                $record->department_id = Department::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($record->department))])
                    // A department name is only unambiguous within a facility,
                    // so prefer the one under this record's facility.
                    ->orderByRaw('(facility_id <=> ?) DESC, id', [$record->facility_id])
                    ->value('id');
            }
        });
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The facility this record belongs to, by identity rather than by name.
     *
     * Null on records whose facility name matched nothing when the ids were
     * backfilled — `emissions:link-facilities --report` lists them.
     */
    public function facilityRecord(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facilities::class, 'facility_id');
    }

    public function departmentRecord(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // NOTE: there is deliberately no emissionSource() relation here. This table
    // records its source as a NAME (the `emission_source` string column), not a
    // foreign key — a belongsTo would query a column that does not exist.
    public function emissionFactor()
    {
        return $this->belongsTo(EmissionFactor::class);
    }

    public function scope3Category()
    {
        return $this->belongsTo(Scope3Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function energyAttributeCertificate()
    {
        return $this->belongsTo(EnergyAttributeCertificate::class);
    }

    /**
     * GHG Protocol market-based Scope 2 figure. Falls back to the location-based
     * figure (co2e_value) when no separate market-based value was recorded, so
     * dual-reporting sums are always well-defined.
     */
    public function marketBasedCo2e(): float
    {
        return (float) ($this->market_based_co2e ?? $this->co2e_value);
    }

    // Scopes for filtering
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('scope3_category_id', $categoryId);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopePrimaryData($query)
    {
        return $query->where('data_quality', 'primary');
    }

    public function scopeSpendBased($query)
    {
        return $query->where('calculation_method', 'spend-based');
    }

    public function scopeActivityBased($query)
    {
        return $query->where('calculation_method', 'activity-based');
    }
}
