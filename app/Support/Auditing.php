<?php

namespace App\Support;

/**
 * A process-wide switch for the App\Auditable trait.
 *
 * The audit trail is the artefact an ISO 14064-3 assurer reads: it is supposed
 * to answer "who changed this figure, and when". Machine-generated rows that no
 * person caused are not merely noise in it — they dilute the one thing it is
 * for.
 *
 * The case this exists for is seeding. TenantDatabaseSeeder writes roughly 485
 * reference rows into every new tenant (271 emission factors, 190+13 emission
 * sources, 11 EIO factors), and with auditing live each one produced an
 * audit_logs entry with user_id = NULL. A brand-new client opened their change
 * history and found 485 entries before they had done anything at all.
 *
 * WHY NOT Model::withoutEvents()
 *
 * That would silence every model event, not just auditing — including
 * HasCompanyScope's `creating` hook, which stamps company_id on new rows. Using
 * it around the seeders would trade an audit-noise problem for unscoped
 * reference data. This switch turns off exactly one thing.
 *
 * Scope: static, so it lasts for the current process only. It is not a
 * per-tenant or persisted setting, and it is not intended to be — a request
 * that wants no audit trail is almost always a request that should not be made.
 * Reach for it for machine-generated reference data, not to make a user's
 * change quieter.
 */
class Auditing
{
    protected static bool $enabled = true;

    public static function enabled(): bool
    {
        return static::$enabled;
    }

    /**
     * Run $callback with audit writing suppressed, then restore the previous
     * state — restored via finally, so a throwing callback cannot leave
     * auditing off for everything that follows it in the process.
     *
     * Nests correctly: the previous value is captured rather than assumed true.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function without(callable $callback): mixed
    {
        $previous = static::$enabled;
        static::$enabled = false;

        try {
            return $callback();
        } finally {
            static::$enabled = $previous;
        }
    }
}
