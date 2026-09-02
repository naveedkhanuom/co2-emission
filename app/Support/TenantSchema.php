<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * TEN-06 — knowing whether a tenant's database is up to date.
 *
 * Jobs\MigrateDatabase runs once, at tenant creation. After that, a new file in
 * database/migrations/tenant/ reaches NEW tenants only: existing clients keep
 * the old schema and throw on the first query that touches a new column. Nothing
 * enforced `tenants:migrate`, and the tenants.schema_version column that exists
 * for precisely this was written by nothing and read by nothing.
 *
 * WHY THE STAMP EXISTS AT ALL
 *
 * Answering "is this tenant behind?" properly means reading that tenant's own
 * migrations table — a query into a different database. Doing that on every
 * request would put a query on the hot path of every page, and doing it for the
 * whole fleet would mean opening one connection per tenant.
 *
 * So the answer is cached on the tenant row in the CENTRAL database, where it
 * is already in memory once the subdomain resolves. The middleware compares two
 * strings and issues no query at all; the back-office lists the fleet from one
 * table.
 *
 * The stamp is written after migrating (tenants:schema-stamp, or provisioning),
 * and self-heals: a tenant with no stamp — every tenant that existed before this
 * was added — gets one computed from its real migrations table on the next
 * request that asks.
 */
class TenantSchema
{
    /**
     * Cached per process: the directory does not change while PHP is running,
     * and this is read on every tenant request.
     */
    protected static ?string $latestAvailable = null;

    /**
     * The version of the newest tenant migration that EXISTS on disk.
     *
     * Laravel runs migrations in filename order and their names are timestamp
     * prefixed, so the maximum name is the newest. Returns null when there are
     * no tenant migrations at all, which only happens in a broken checkout.
     */
    public static function latestAvailable(): ?string
    {
        if (static::$latestAvailable !== null) {
            return static::$latestAvailable;
        }

        $names = static::availableMigrations();

        return static::$latestAvailable = $names === [] ? null : static::versionOf(max($names));
    }

    /**
     * The timestamp prefix of a migration name — 2026_08_27_121426 — which is
     * what gets stored as the version.
     *
     * The prefix rather than the whole filename, for three reasons: it fits the
     * 40-character column, it is the part that actually orders migrations, and
     * renaming a migration file without changing its timestamp is not a schema
     * change and should not read as drift.
     *
     * A name without a recognisable prefix is returned trimmed to the column
     * width rather than rejected — a wrong-looking version is easier to
     * diagnose than a silently skipped stamp.
     */
    public static function versionOf(string $migration): string
    {
        return preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $migration, $matches) === 1
            ? $matches[1]
            : mb_substr($migration, 0, 40);
    }

    /**
     * Every tenant migration on disk, without the .php extension — the same
     * form Laravel stores in the `migrations` table.
     *
     * @return array<int, string>
     */
    public static function availableMigrations(): array
    {
        $path = database_path('migrations/tenant');

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->all();
    }

    /**
     * The version of the newest migration this tenant's database has run.
     *
     * Queries the tenant's own `migrations` table, so it needs tenancy to be
     * initialised for that tenant. This is the authoritative answer and the
     * expensive one — prefer the stamp on the hot path.
     */
    public static function appliedForCurrentTenant(): ?string
    {
        $applied = DB::table('migrations')->pluck('migration')->all();

        return $applied === [] ? null : static::versionOf(max($applied));
    }

    /**
     * Migrations on disk that this tenant's database has NOT run.
     *
     * A set difference rather than a comparison of the newest names, because a
     * migration can be added with an older timestamp than one already applied —
     * in which case the newest names match while a file has still never run.
     *
     * @return array<int, string>
     */
    public static function pendingForCurrentTenant(): array
    {
        $applied = DB::table('migrations')->pluck('migration')->all();

        return array_values(array_diff(static::availableMigrations(), $applied));
    }

    /**
     * Record what this tenant's database has run, on the tenant row.
     *
     * Must be called with tenancy initialised for $tenant — it reads that
     * tenant's migrations table. The write itself goes to the central database,
     * which is where the tenants table lives.
     */
    public static function stampCurrentTenant(Tenant $tenant): ?string
    {
        $applied = static::appliedForCurrentTenant();

        // forceFill + saveQuietly: this is bookkeeping about the row, not a
        // change to the account, and it should not fire tenant lifecycle events.
        $tenant->forceFill(['schema_version' => $applied])->saveQuietly();

        return $applied;
    }

    /**
     * Is this tenant's schema behind what the code expects?
     *
     * Reads the stamp only — no query. A tenant with no stamp is reported as
     * NOT behind: refusing to serve on "we have not checked yet" would take
     * every pre-existing tenant offline the moment this shipped. The middleware
     * resolves that case by computing and storing the stamp instead.
     */
    public static function isBehind(Tenant $tenant): bool
    {
        $latest = static::latestAvailable();

        if ($latest === null || $tenant->schema_version === null) {
            return false;
        }

        return $tenant->schema_version !== $latest;
    }

    /**
     * Only for tests, which write migration files or need a clean read.
     */
    public static function flushCache(): void
    {
        static::$latestAvailable = null;
    }
}
