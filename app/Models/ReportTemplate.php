<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'formats',
        'sections',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'formats' => 'array',
        'sections' => 'array',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scheduledReports()
    {
        return $this->hasMany(ScheduledReport::class);
    }
}

