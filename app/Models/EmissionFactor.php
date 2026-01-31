<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'emission_source_id',
        'organization_id',
        'country_id',
        'unit',
        'factor_value',
        'region'
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
}

