<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'name',
        'description'
    ];

    public function facility()
    {
        return $this->belongsTo(Facilities::class);
    }
}
