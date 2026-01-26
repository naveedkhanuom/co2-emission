<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryEmissionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_type',
        'name',
        'scope',
        'emission_source',
        'unit',
        'default_factor',
        'region',
        'source_reference',
        'description',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'default_factor' => 'decimal:6',
            'is_active' => 'boolean',
            'scope' => 'integer',
            'priority' => 'integer',
        ];
    }

    /**
     * Get templates by industry type.
     */
    public static function getByIndustry($industryType, $scope = null)
    {
        $query = static::where('industry_type', $industryType)
            ->where('is_active', true);

        if ($scope) {
            $query->where('scope', $scope);
        }

        return $query->orderBy('priority')
            ->orderBy('scope')
            ->get();
    }
}
