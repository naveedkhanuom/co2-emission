<?php

namespace App\Models;

use App\HasCompanyScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
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

    /**
     * Next run date based on this report's frequency, from a given start.
     */
    public function computeNextRunDate(?Carbon $from = null): string
    {
        $from = $from ? $from->copy() : now();

        $next = match ($this->frequency) {
            'daily'     => $from->addDay(),
            'weekly'    => $from->addWeek(),
            'monthly'   => $from->addMonth(),
            'quarterly' => $from->addMonths(3),
            'yearly'    => $from->addYear(),
            default     => $from->addMonth(),
        };

        return $next->toDateString();
    }
}

