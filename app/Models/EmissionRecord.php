<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_date',
        'facility',
        'scope',
        'emission_source',
        'activity_data',
        'emission_factor',
        'co2e_value',
        'confidence_level',
        'department',
        'data_source',
        'notes',
        'created_by',
        'status',
    ];


    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function emissionSource() { return $this->belongsTo(EmissionSource::class); }
    public function emissionFactor() { return $this->belongsTo(EmissionFactor::class); }
}
