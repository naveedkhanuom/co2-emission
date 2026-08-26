<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * A client account. Owns one database and one subdomain, and holds as many
 * companies inside it as the client wants to report on — a holding group's
 * subsidiaries, or a consultancy's client list.
 *
 * Do not confuse this with Company. Company is the reporting entity and is
 * separated by company_id INSIDE a tenant's database (see HasCompanyScope).
 * Tenant is the party that signs the contract, and is separated by having its
 * own database entirely.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Columns stored as real columns rather than inside the package's `data`
     * JSON blob. Anything listed here can be indexed, queried and reported on;
     * anything not listed still works, it just lives in `data`.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'plan',
            'schema_version',
        ];
    }

    /**
     * Tenant ids are slugs, not UUIDs, because the id becomes the database
     * name (`tenant_acme`). When the whole point of this architecture is being
     * able to hand a client their database, that database should be named
     * after them and not after a random 128-bit number.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * The subdomain this tenant is reached on. Stored in the `domains` table
     * because that is where InitializeTenancyBySubdomain looks it up.
     */
    public function subdomain(): ?string
    {
        return $this->domains->first()?->domain;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_FAILED = 'failed';
}
