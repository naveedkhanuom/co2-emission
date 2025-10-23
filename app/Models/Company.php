<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'industry_type',
        'country',
        'address',
        'contact_person',
        'email',
        'phone'
    ];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function emissionRecords()
    {
        return $this->hasMany(EmissionRecord::class);
    }
}

