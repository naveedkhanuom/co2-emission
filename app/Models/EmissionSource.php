<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scope',
        'description',
    ];

    // Example relationship placeholder (if later needed)
    // public function emissionFactors()
    // {
    //     return $this->hasMany(EmissionFactor::class);
    // }
}
