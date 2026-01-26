<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Department extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'facility_id',
        'name',
        'description'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function facility()
    {
        return $this->belongsTo(Facilities::class);
    }
}
