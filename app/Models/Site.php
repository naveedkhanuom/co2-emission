<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Site extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'name',
        'location',
        'latitude',
        'longitude',
        'description',
    ];

    // Relationship with Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}


