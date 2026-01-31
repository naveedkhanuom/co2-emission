<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'report_template_id',
        'facility_id',
        'department_id',
        'frequency',
        'schedule_time',
        'next_run_date',
        'last_run_date',
        'recipients',
        'formats',
        'status',
        'created_by',
    ];

    protected $casts = [
        'recipients' => 'array',
        'formats' => 'array',
        'next_run_date' => 'date',
        'last_run_date' => 'date',
        'schedule_time' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facilities::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

