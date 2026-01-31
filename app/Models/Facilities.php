<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Facilities extends Model
{
    use HasCompanyScope;
    
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'address',
        'city',
        'state',
        'country',
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function departments()
    {
        return $this->hasMany(Department::class, 'facility_id');
    }
}
