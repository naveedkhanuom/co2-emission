<?php

namespace App\Models;

use App\Auditable;
use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One candidate parameter in a company's inventory boundary — "diesel for the
 * standby generator", "cement purchased", "customer-driven kilometres".
 *
 * Each item is decided individually. An excluded item must carry a written
 * reason: that reason is the evidence an auditor asks for when a Scope 3
 * category is reported as not relevant.
 */
class BoundaryItem extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

    protected $fillable = [
        'company_id',
        'boundary_assessment_id',
        'scope',
        'scope3_category_id',
        'emission_source_id',
        'suggested_name',
        'suggested_unit',
        'materiality',
        'relevance',
        'decision',
        'exclusion_reason',
        'rationale',
        'data_hint',
        'action_type',
        'typical_share_pct',
        'confidence',
        'source',
        'accepted_by',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'integer',
            'typical_share_pct' => 'decimal:2',
            'confidence' => 'decimal:2',
            'accepted_at' => 'datetime',
        ];
    }

    /** Ordering weight for the checklist — biggest likely impact first. */
    private const MATERIALITY_RANK = ['high' => 1, 'medium' => 2, 'low' => 3];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(BoundaryAssessment::class, 'boundary_assessment_id');
    }

    public function scope3Category(): BelongsTo
    {
        return $this->belongsTo(Scope3Category::class);
    }

    public function emissionSource(): BelongsTo
    {
        return $this->belongsTo(EmissionSource::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function scopeIncluded($query)
    {
        return $query->where('decision', 'included');
    }

    public function scopePending($query)
    {
        return $query->where('decision', 'pending');
    }

    public function scopeForScope($query, int $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Highest-materiality first, then the AI's rough share estimate. Used by the
     * checklist so users chase the sources that actually move the number.
     */
    public function scopeByPriority($query)
    {
        return $query
            ->orderByRaw("FIELD(materiality, 'high', 'medium', 'low')")
            ->orderByDesc('typical_share_pct');
    }

    public function materialityRank(): int
    {
        return self::MATERIALITY_RANK[$this->materiality] ?? 2;
    }

    /**
     * Stable identity for the same real-world source across assessments.
     *
     * Items are regenerated from scratch each year, so comparing one year's
     * boundary with the next needs a key that survives rewording. Scope 3 uses
     * its category (exact); everything else falls back to the catalogue source,
     * then to a normalised name.
     */
    public function signature(): string
    {
        if ($this->scope === 3 && $this->scope3_category_id) {
            return 's3:cat:'.$this->scope3_category_id;
        }

        if ($this->emission_source_id) {
            return 's'.$this->scope.':src:'.$this->emission_source_id;
        }

        $name = mb_strtolower(trim((string) $this->suggested_name));
        $name = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
        $name = trim((string) preg_replace('/\s+/', ' ', $name));

        return 's'.$this->scope.':name:'.$name;
    }

    /**
     * Whether any emission record exists for this item in the given year. Drives
     * the boundary coverage metric — an included item with no data is a gap the
     * user still has to close.
     */
    public function hasDataForYear(int $year): bool
    {
        $query = EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $this->company_id)
            ->where('scope', $this->scope)
            ->whereYear('entry_date', $year);

        // emission_records identifies its source by NAME (a string), not by a
        // foreign key — there is no emission_source_id column on that table.
        if ($this->scope === 3 && $this->scope3_category_id) {
            $query->where('scope3_category_id', $this->scope3_category_id);
        } else {
            $name = $this->emissionSource?->name ?? $this->suggested_name;
            $query->where('emission_source', 'like', '%'.$name.'%');
        }

        return $query->exists();
    }
}
