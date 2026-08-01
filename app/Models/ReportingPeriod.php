<?php

namespace App\Models;

use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * One inventory year for a company. Locking freezes the year's emission records;
 * one period may be flagged as the base year. Company-scoped via HasCompanyScope.
 */
class ReportingPeriod extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'year',
        'status',
        'is_base_year',
        'locked_at',
        'locked_by',
        'note',
    ];

    protected $casts = [
        'year'         => 'integer',
        'is_base_year' => 'boolean',
        'locked_at'    => 'datetime',
    ];

    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Is the given year locked for a company? Used to block edits to records in
     * a finalised period. Bypasses the tenant scope and matches company_id
     * explicitly so it is reliable from any context (queue, command, request).
     */
    public static function isYearLocked(int $year, ?int $companyId = null): bool
    {
        $companyId = $companyId ?? current_company_id();
        if (!$companyId) {
            return false;
        }

        return static::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('status', 'locked')
            ->exists();
    }

    /** The company's designated base year, or null if none set. */
    public static function baseYearFor(?int $companyId = null): ?int
    {
        $companyId = $companyId ?? current_company_id();
        if (!$companyId) {
            return null;
        }

        return static::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('is_base_year', true)
            ->value('year');
    }
}
