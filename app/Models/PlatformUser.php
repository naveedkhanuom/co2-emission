<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A member of our staff, authenticating against the central database.
 *
 * Never a tenant's user. This model must not carry HasCompanyScope: it has no
 * company, and the scope's fail-closed branch would deny every query.
 */
class PlatformUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Pinned to the central connection rather than left to the default.
     *
     * The back-office only runs on the central domain, where tenancy never
     * initialises and the default connection is already central — but if this
     * model is ever touched inside a tenant context (a console command, an
     * impersonation flow), the default connection there is the TENANT
     * database, which has no platform_users table. Better to be explicit than
     * to depend on where the caller happens to be standing.
     */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', parent::getConnectionName());
    }
}
