<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facilities extends Model
{
    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'state',
        'country',
    ];
}
