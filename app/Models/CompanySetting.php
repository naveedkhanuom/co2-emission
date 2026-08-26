<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
        'description',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
