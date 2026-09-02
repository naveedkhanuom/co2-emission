<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Support\TenantSchema;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

/**
 * TEN-06 — nothing migrated existing tenants, and nothing noticed.
 *
 * Jobs\MigrateDatabase runs once, at tenant creation. After that a new file in
 * database/migrations/tenant/ reaches NEW tenants only; existing clients keep
 * the old schema and throw on the first query touching a new column. The
 * tenants.schema_version column existed for exactly this and was written by
 * nothing and read by nothing — NULL on every row.
 *
 * The failure mode being closed is not "a tenant is behind" — that is a deploy
 * step, and deploy steps get missed. It is that being behind produced a 500 from
 * somewhere deep in a page, which reads as a bug in the feature rather than as
 * the missed step it is.
 */
class TenantSchemaDriftTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TenantSchema::flushCache();
    }

    protected function tearDown(): void
    {
        // The stamp lives on the tenant row in the CENTRAL database, which
        // DatabaseTransactions does not roll back — it wraps the tenant
        // connection. Left alone, a test that stamps a fake version would leave
        // the shared test tenant permanently "behind" and take every later test
        // in the suite offline with a 503.
        $tenant = Tenant::find(self::TEST_TENANT_ID);
        $tenant?->forceFill(['schema_version' => null])->saveQuietly();

        TenantSchema::flushCache();

        parent::tearDown();
    }

    private function tenant(): Tenant
    {
        return Tenant::find(self::TEST_TENANT_ID);
    }

    /**
     * Set the stamp on the tenant instance the MIDDLEWARE will read.
     *
     * Writing the row alone is not enough here: tenancy() binds one Tenant
     * object at initialisation and the middleware reads that, so a row updated
     * behind its back leaves the bound copy stale and the assertion tests
     * nothing. In production this does not arise — the tenant is resolved fresh
     * per request — so it is a fixture concern, not a behaviour one.
     */
    private function setStamp(?string $version): void
    {
        tenant()->forceFill(['schema_version' => $version])->saveQuietly();
    }

    public function test_the_latest_available_migration_is_read_from_disk(): void
    {
        $latest = TenantSchema::latestAvailable();

        $this->assertNotNull($latest, 'No tenant migrations were found on disk.');
        $this->assertSame(
            TenantSchema::versionOf(max(TenantSchema::availableMigrations())),
            $latest,
            'latestAvailable() is not the newest migration by name.'
        );
    }

    /**
     * The stored version is the timestamp prefix, not the whole filename: the
     * column is varchar(40) and migration names run to about 75 characters, so
     * storing the name truncated on write and every stamp silently failed.
     */
    public function test_the_version_is_the_timestamp_prefix_and_fits_the_column(): void
    {
        $version = TenantSchema::versionOf('2026_08_27_121426_add_facility_and_department_ids_to_emission_records_table');

        $this->assertSame('2026_08_27_121426', $version);
        $this->assertLessThanOrEqual(40, mb_strlen((string) TenantSchema::latestAvailable()));
    }

    public function test_stamping_records_what_the_database_has_run(): void
    {
        $tenant = $this->tenant();
        $this->assertNull($tenant->schema_version);

        TenantSchema::stampCurrentTenant($tenant);

        $this->assertSame(
            TenantSchema::versionOf(DB::table('migrations')->max('migration')),
            $tenant->fresh()->schema_version
        );
    }

    /**
     * A fully migrated tenant has nothing pending. If this fails, the test
     * tenant itself is behind and the rest of the suite is unreliable.
     */
    public function test_a_current_tenant_has_no_pending_migrations(): void
    {
        $this->assertSame([], TenantSchema::pendingForCurrentTenant());
    }

    public function test_a_tenant_stamped_behind_is_reported_as_behind(): void
    {
        $tenant = $this->tenant();
        $tenant->forceFill(['schema_version' => '1970_01_01_000000_ancient'])->saveQuietly();

        $this->assertTrue(TenantSchema::isBehind($tenant->fresh()));
    }

    public function test_a_tenant_stamped_current_is_not_behind(): void
    {
        $tenant = $this->tenant();
        $tenant->forceFill(['schema_version' => TenantSchema::latestAvailable()])->saveQuietly();

        $this->assertFalse(TenantSchema::isBehind($tenant->fresh()));
    }

    /**
     * The case that decides whether this can ship at all. Every tenant that
     * existed before the stamp has schema_version = NULL; treating "not yet
     * checked" as "behind" would take the entire live fleet offline on deploy.
     */
    public function test_an_unstamped_tenant_is_not_treated_as_behind(): void
    {
        $tenant = $this->tenant();
        $tenant->forceFill(['schema_version' => null])->saveQuietly();

        $this->assertFalse(
            TenantSchema::isBehind($tenant->fresh()),
            'An unstamped tenant was reported behind, which would 503 every existing client on deploy.'
        );
    }

    /**
     * A stale stamp on a database that IS up to date must not take the workspace
     * down. `tenants:migrate` migrates without stamping, so every tenant sits in
     * exactly this state between it and `schema:stamp` — and refusing on the
     * stamp alone turned that ordinary gap into an outage.
     */
    public function test_a_stale_stamp_on_a_current_database_is_corrected_not_refused(): void
    {
        $this->setStamp('1970_01_01_000000');

        $this->get('/login')->assertOk();

        $this->assertSame(
            TenantSchema::latestAvailable(),
            $this->tenant()->schema_version,
            'The stale stamp was not corrected, so the next request pays the check again.'
        );
    }

    /**
     * A tenant that is GENUINELY behind — a migration on disk its database has
     * not run — must still be refused. This is the case the guard exists for, and
     * the verification above must not swallow it.
     */
    public function test_a_genuinely_behind_tenant_is_refused(): void
    {
        $applied = DB::table('migrations')->orderByDesc('id')->first();
        DB::table('migrations')->where('id', $applied->id)->delete();

        try {
            $this->setStamp('1970_01_01_000000');

            $this->get('/login')
                ->assertStatus(503)
                ->assertSee('Being updated');
        } finally {
            DB::table('migrations')->insert([
                'migration' => $applied->migration,
                'batch' => $applied->batch,
            ]);
        }
    }

    public function test_a_current_tenant_is_served_normally(): void
    {
        $this->setStamp(TenantSchema::latestAvailable());

        $this->get('/login')->assertOk();
    }

    /**
     * An unstamped tenant must be served AND stamped on the way through, so the
     * fleet heals itself without anyone running a backfill.
     */
    public function test_an_unstamped_tenant_is_served_and_stamped_on_the_way_through(): void
    {
        $this->setStamp(null);

        $this->get('/login')->assertOk();

        $this->assertSame(
            TenantSchema::latestAvailable(),
            $this->tenant()->schema_version,
            'A request through an unstamped tenant did not record its schema version.'
        );
    }

    public function test_the_stamp_command_refuses_to_stamp_a_tenant_with_pending_migrations(): void
    {
        // Simulate a pending migration by removing an applied row, so the set
        // difference is non-empty without touching the schema itself.
        $applied = DB::table('migrations')->orderByDesc('id')->first();
        DB::table('migrations')->where('id', $applied->id)->delete();

        try {
            $this->assertNotSame([], TenantSchema::pendingForCurrentTenant());

            $this->artisan('schema:stamp')
                ->assertFailed();

            $this->assertNull(
                $this->tenant()->schema_version,
                'A tenant with pending migrations was stamped as current.'
            );
        } finally {
            DB::table('migrations')->insert([
                'migration' => $applied->migration,
                'batch' => $applied->batch,
            ]);
        }
    }

    public function test_the_stamp_command_stamps_a_current_tenant(): void
    {
        $this->artisan('schema:stamp')->assertSuccessful();

        $this->assertSame(TenantSchema::latestAvailable(), $this->tenant()->schema_version);
    }
}
