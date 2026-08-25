<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One run of the Boundary Advisor: the company profile and clarifying answers
 * that were given, and the checklist of parameters it produced.
 *
 * Versioned per reporting year. Activating a new assessment supersedes the
 * previous one rather than overwriting it, so a boundary change between years
 * stays visible — which is exactly what an assurer asks for, and what triggers
 * a base-year recalculation review.
 */
class BoundaryAssessment extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

    protected $fillable = [
        'company_id',
        'reporting_year',
        'status',
        'version',
        'profile_snapshot',
        'questions',
        'summary',
        'model',
        'prompt_version',
        'confidence',
        'generator',
        'generated_at',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reporting_year' => 'integer',
            'version' => 'integer',
            'profile_snapshot' => 'array',
            'questions' => 'array',
            'confidence' => 'decimal:2',
            'generated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BoundaryItem::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Items the company has agreed to measure. These are what the coverage
     * metric counts against.
     */
    public function includedItems(): HasMany
    {
        return $this->items()->where('decision', 'included');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('reporting_year', $year);
    }

    /**
     * Whether this assessment was produced by the AI rather than the
     * deterministic industry-template fallback.
     */
    public function isAiGenerated(): bool
    {
        return $this->generator === 'ai';
    }

    /**
     * How many of the 15 Scope 3 categories still have no relevance decision.
     * The GHG Protocol Scope 3 Standard requires all 15 to be screened, so a
     * non-zero count means the assessment is not yet complete.
     */
    public function unscreenedScope3Count(): int
    {
        $screened = $this->items()
            ->where('scope', 3)
            ->whereNotNull('scope3_category_id')
            ->distinct()
            ->count('scope3_category_id');

        return max(0, Scope3Category::count() - $screened);
    }
}
