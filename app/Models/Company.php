<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'industry_type',
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
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
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
        $value = match($type) {
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

    /**
     * Get industry-specific emission templates.
     */
    public function getIndustryTemplates()
    {
        return IndustryEmissionTemplate::where('industry_type', $this->industry_type)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('scope')
            ->get();
    }
}

