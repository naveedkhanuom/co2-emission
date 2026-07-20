<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single recorded change to an audited model. Append-only: rows are written by
 * the Auditable trait and are never updated.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The model that was changed (morphs to EmissionRecord, Target, etc.).
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Human-friendly short name of the changed model, e.g. "Emission Record".
     */
    public function getModelLabelAttribute(): string
    {
        $short = class_basename($this->auditable_type);

        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $short));
    }
}
