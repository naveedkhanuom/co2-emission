<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A record that an anomaly was detected and alerted on. The (company_id,
 * anomaly_key) unique index makes the scan idempotent — see the migration.
 */
class AnomalyAlert extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'anomaly_key',
        'period',
        'title',
        'message',
        'severity',
        'metadata',
        'notified_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'notified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
