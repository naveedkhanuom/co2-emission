<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class SupplierSurvey extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'supplier_id',
        'survey_type',
        'title',
        'description',
        'questions',
        'responses',
        'status',
        'sent_at',
        'due_date',
        'completed_at',
        'emissions_generated_at',
        'reminder_sent_at',
        'reminder_count',
        'public_token',
        'public_token_expires_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'questions' => 'array',
        'responses' => 'array',
        'sent_at' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'emissions_generated_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'public_token_expires_at' => 'datetime',
    ];

    /**
     * Get the company that owns this survey.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the supplier for this survey.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this survey.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if survey is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Mark survey as sent.
     */
    public function markAsSent()
    {
        // Mint a fresh link whenever the current one is missing or no longer
        // valid (e.g. expired, or consumed by a previous submission). This lets
        // an admin legitimately re-issue access.
        if (!$this->isPublicLinkValid()) {
            $this->public_token = bin2hex(random_bytes(32));
            $this->public_token_expires_at = now()->addDays(30);
        }

        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'public_token' => $this->public_token,
            'public_token_expires_at' => $this->public_token_expires_at,
        ]);
    }

    /**
     * Force a brand-new public link (token + expiry), regardless of the current
     * one. Used by the "re-send link" action for surveys that are already out.
     */
    public function regeneratePublicToken(int $validDays = 30): void
    {
        $this->update([
            'public_token' => bin2hex(random_bytes(32)),
            'public_token_expires_at' => now()->addDays($validDays),
        ]);
    }

    /**
     * Determine if the supplier portal link is valid.
     */
    public function isPublicLinkValid(): bool
    {
        if (!$this->public_token) {
            return false;
        }
        if ($this->public_token_expires_at && $this->public_token_expires_at->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * Mark survey as completed.
     */
    public function markAsCompleted($responses = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'responses' => $responses ?? $this->responses,
        ]);

        // Update supplier's last data submission
        if ($this->supplier) {
            $this->supplier->update([
                'last_data_submission' => now(),
            ]);
        }
    }

    /**
     * Send reminder.
     */
    public function sendReminder()
    {
        $this->increment('reminder_count');
        $this->update([
            'reminder_sent_at' => now(),
        ]);
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDue()
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }
}
