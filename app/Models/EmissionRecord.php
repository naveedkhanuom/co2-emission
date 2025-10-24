<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'site_id',
        'user_id',
        'emission_source_id',
        'emission_factor_id',
        'record_date',
        'activity_data',
        'emission_value',
        'unit'
    ];

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function emissionSource() { return $this->belongsTo(EmissionSource::class); }
    public function emissionFactor() { return $this->belongsTo(EmissionFactor::class); }
}
