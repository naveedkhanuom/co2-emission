<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'industry_type',
        'business_description',
        'sub_industry',
        'isic_code',
        'country',
        'address',
        'contact_person',
        'email',
        'phone',
        'tax_id',
        'registration_number',
        'website',
        'logo',
        'size',
        'employee_count',
        'annual_revenue',
        'currency',
        'timezone',
        'fiscal_year_start',
        'reporting_standards',
        'scopes_enabled',
        'is_active',
        'subscription_expires_at',
        'notes',
    ];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function emissionRecords()
    {
        return $this->hasMany(EmissionRecord::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function settings()
    {
        return $this->hasMany(CompanySetting::class);
    }

    protected function casts(): array
    {
        return [
            'reporting_standards' => 'array',
            'scopes_enabled' => 'array',
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'annual_revenue' => 'decimal:2',
        ];
    }

    /**
     * Get a setting value by key.
     */
    public function getSetting($key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value.
     */
    public function setSetting($key, $value, $type = 'string', $description = null)
    {
        $value = match ($type) {
            'boolean', 'integer' => (string) $value,
            'json' => json_encode($value),
            default => $value,
        };

        return $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    public function boundaryAssessments()
    {
        return $this->hasMany(BoundaryAssessment::class);
    }

    /**
     * The company's live inventory boundary, if the Boundary Advisor has been
     * run and activated. Null means the company has not scoped its boundary yet.
     */
    public function activeBoundaryAssessment(?int $year = null): ?BoundaryAssessment
    {
        return $this->boundaryAssessments()
            ->where('status', 'active')
            ->when($year, fn ($q) => $q->where('reporting_year', $year))
            ->latest('reporting_year')
            ->first();
    }

    /**
     * Get industry-specific emission templates, narrowing to the company's
     * sub-industry when one is set (a car-rental firm and a shipping line are
     * both `transportation` but need different templates).
     */
    public function getIndustryTemplates()
    {
        return IndustryEmissionTemplate::getByIndustry($this->industry_type, null, $this->sub_industry);
    }
}
