<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionSource extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'scope',
        'description',
    ];

    public function emissionFactors()
    {
        return $this->hasMany(EmissionFactor::class);
    }
}

