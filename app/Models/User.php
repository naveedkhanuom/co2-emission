<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

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
        'is_super_admin',
        'company_access',
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
            'is_super_admin' => 'boolean',
            'company_access' => 'array',
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
        if ($this->is_super_admin) {
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
     * Check if user can access a company.
     */
    public function canAccessCompany($companyId)
    {
        if ($this->is_super_admin) {
            return true;
        }
        
        if ($this->company_id == $companyId) {
            return true;
        }
        
        return in_array($companyId, $this->company_access ?? []);
    }
}
