<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scope3Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category_type',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all emission records for this category.
     */
    public function emissionRecords()
    {
        return $this->hasMany(EmissionRecord::class);
    }

    /**
     * Scope a query to only include upstream categories.
     */
    public function scopeUpstream($query)
    {
        return $query->where('category_type', 'upstream');
    }

    /**
     * Scope a query to only include downstream categories.
     */
    public function scopeDownstream($query)
    {
        return $query->where('category_type', 'downstream');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get total emissions for this category for a specific company and year.
     */
    public function getTotalEmissionsForCompany($companyId, $year = null)
    {
        $query = $this->emissionRecords()
            ->where('company_id', $companyId)
            ->where('scope', 3);

        if ($year) {
            $query->whereYear('entry_date', $year);
        }

        return $query->sum('co2e_value');
    }
}
