<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Site extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

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


