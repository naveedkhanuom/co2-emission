<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'site_id',
        'report_name',
        'period',
        'generated_at',
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function site() {
        return $this->belongsTo(Site::class);
    }
}

