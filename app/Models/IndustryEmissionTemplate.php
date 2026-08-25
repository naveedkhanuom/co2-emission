<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryEmissionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_type',
        'sub_industry',
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
     *
     * When $subIndustry is given, generic templates for the industry (those
     * with a null sub_industry) are returned alongside the narrower ones, so a
     * car-rental firm still gets "electricity" as well as its fleet-specific
     * rows. Passing null returns the generic set only.
     */
    public static function getByIndustry($industryType, $scope = null, ?string $subIndustry = null)
    {
        $query = static::where('industry_type', $industryType)
            ->where('is_active', true);

        if ($scope) {
            $query->where('scope', $scope);
        }

        if ($subIndustry) {
            $query->where(function ($q) use ($subIndustry) {
                $q->whereNull('sub_industry')->orWhere('sub_industry', $subIndustry);
            });
        } else {
            $query->whereNull('sub_industry');
        }

        return $query->orderBy('priority')
            ->orderBy('scope')
            ->get();
    }

    /**
     * Distinct sub-industries defined for an industry, for the profile picker.
     *
     * @return array<int, string>
     */
    public static function subIndustriesFor(string $industryType): array
    {
        return static::where('industry_type', $industryType)
            ->whereNotNull('sub_industry')
            ->where('is_active', true)
            ->distinct()
            ->orderBy('sub_industry')
            ->pluck('sub_industry')
            ->all();
    }
}
