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
        'status',
        'type',
        'created_by',
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function site() {
        return $this->belongsTo(Site::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }
}

