<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Report extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'facility_id',
        'department_id',
        'report_name',
        'period',
        'generated_at',
        'status',
        'type',
        'views_count',
        'last_viewed_at',
        'created_by',
    ];

    protected $casts = [
        'generated_at' => 'date',
        'last_viewed_at' => 'datetime',
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function facility() {
        return $this->belongsTo(Facilities::class);
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'created_by');
    }
}

