<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // Who can reach a company's inventory, and which pages they may see, is part
    // of the control environment an assurer reviews — so account changes are
    // recorded alongside the data changes.
    use Auditable, HasFactory, HasRoles, Notifiable;

    /**
     * Never write these into an audit diff.
     *
     * The trail is readable by administrators, so it must not become a place
     * where password hashes and session tokens accumulate — a hash is still
     * credential material, and the audit table has a far longer life and a wider
     * audience than the users row it came from.
     *
     * @var array<int, string>
     */
    protected array $auditExclude = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',

        // Sees every company in this account, and nothing beyond it — the
        // tenant's database is the edge of its reach, not this flag. Never a
        // form field: UserController strips it from input on create and
        // update, and tenant:provision is the only thing that grants it.
        'is_account_owner',

        'company_access',
        'is_demo_user',
        'allowed_sidebar_routes',
        'restricted_sidebar_routes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_account_owner' => 'boolean',
            'company_access' => 'array',
            'is_demo_user' => 'boolean',
            'allowed_sidebar_routes' => 'array',
            'restricted_sidebar_routes' => 'array',
        ];
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all companies the user can access.
     */
    public function accessibleCompanies()
    {
        if ($this->is_account_owner) {
            return Company::query();
        }

        $companyIds = $this->company_access ?? [];
        if ($this->company_id) {
            $companyIds[] = $this->company_id;
        }

        if (empty($companyIds)) {
            // Return empty query if no companies assigned
            return Company::whereRaw('1 = 0');
        }

        return Company::whereIn('id', array_unique($companyIds));
    }

    /**
     * The company this user last worked in.
     *
     * Deliberately NOT `company_id`: that one is membership and grants access,
     * this one is only a remembered preference. Kept off $fillable for the same
     * reason `is_account_owner` is guarded — it is never a form field. The
     * company switcher sets it directly, and only after canAccessCompany() has
     * passed.
     */
    public function lastCompany()
    {
        return $this->belongsTo(Company::class, 'last_company_id');
    }

    /**
     * Remember this company as where the user is working, if they may see it.
     *
     * Re-checks access rather than trusting the caller: this is written from a
     * request and read back on a later one, and an account's membership can
     * change in between. A remembered company the user has since lost access to
     * must not be silently restored.
     */
    public function rememberCompany($companyId): void
    {
        if (! $companyId || ! $this->canAccessCompany($companyId)) {
            return;
        }

        if ((string) $this->last_company_id === (string) $companyId) {
            return;
        }

        // Saved quietly. This is a UI preference, not a change to the user, and
        // routing it through the audit trail would file a row every time
        // somebody flipped between two companies.
        $this->last_company_id = $companyId;
        $this->saveQuietly();
    }

    /**
     * Check if user can access a company.
     */
    public function canAccessCompany($companyId)
    {
        if ($this->is_account_owner) {
            return true;
        }

        if ($this->company_id == $companyId) {
            return true;
        }

        return in_array($companyId, $this->company_access ?? []);
    }
}
