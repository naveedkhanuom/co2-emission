<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorOrganization extends Model
{
    use HasFactory;

    protected $table = 'factor_organizations';

    protected $fillable = [
        'code',
        'name',
        'url',
    ];

    public function emissionFactors()
    {
        return $this->hasMany(EmissionFactor::class, 'organization_id');
    }
}

