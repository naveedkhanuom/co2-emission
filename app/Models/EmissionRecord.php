<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class EmissionRecord extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'entry_date',
        'company_id',
        'site_id',
        'facility',
        'scope',
        'scope3_category_id',
        'supplier_id',
        'emission_source',
        'activity_data',
        'spend_amount',
        'spend_currency',
        'emission_factor',
        'factor_organization_id',
        'calculation_method',
        'data_quality',
        'co2e_value',
        'confidence_level',
        'department',
        'data_source',
        'notes',
        'supporting_documents',
        'created_by',
        'status',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'activity_data' => 'decimal:4',
        'spend_amount' => 'decimal:2',
        'emission_factor' => 'decimal:6',
        'co2e_value' => 'decimal:4',
        'supporting_documents' => 'array',
    ];

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function user() { return $this->belongsTo(User::class, 'created_by'); }
    public function emissionSource() { return $this->belongsTo(EmissionSource::class); }
    public function emissionFactor() { return $this->belongsTo(EmissionFactor::class); }
    public function scope3Category() { return $this->belongsTo(Scope3Category::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }

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
