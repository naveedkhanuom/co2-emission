<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EioFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_code',
        'sector_name',
        'country',
        'currency',
        'emission_factor',
        'factor_unit',
        'data_source',
        'year',
        'description',
        'is_active',
    ];

    protected $casts = [
        'emission_factor' => 'decimal:6',
        'is_active' => 'boolean',
        'year' => 'integer',
    ];

    /**
     * Get emission factor for a sector and country.
     */
    public static function getFactor($sectorCode, $country = 'USA', $year = null)
    {
        $query = static::where('sector_code', $sectorCode)
            ->where('country', $country)
            ->where('is_active', true);

        if ($year) {
            $query->where('year', $year);
        } else {
            $query->orderBy('year', 'desc');
        }

        return $query->first();
    }

    /**
     * Calculate emissions from spend amount.
     */
    public static function calculateFromSpend($spendAmount, $sectorCode, $country = 'USA', $currency = 'USD')
    {
        $factor = static::getFactor($sectorCode, $country);
        
        if (!$factor) {
            return null;
        }

        // If currency differs, you might need conversion
        // For now, assuming same currency
        return $spendAmount * $factor->emission_factor;
    }

    /**
     * Scope to active factors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by country.
     */
    public function scopeForCountry($query, $country)
    {
        return $query->where('country', $country);
    }
}
