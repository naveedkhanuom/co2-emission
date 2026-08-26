<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Department extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

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
