<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EmissionSource;
use App\Support\Auditing;
use Tests\TenantTestCase;

/**
 * TEN-11 — every new tenant was born with 485 rows of audit trail that were not
 * theirs.
 *
 * TenantDatabaseSeeder runs with the Auditable trait active, so seeding wrote an
 * audit entry per seeded row: 271 EmissionFactor creates, 190 + 13
 * EmissionSource creates and updates, 11 EioFactor creates. A brand-new client
 * opened their change history and found 485 entries with user_id = NULL before
 * they had done anything at all.
 *
 * The audit trail is the artefact an ISO 14064-3 assurer reads to answer "who
 * changed this figure, and when". Machine-generated rows that no person caused
 * dilute the one thing it is for.
 */
class SeedingAuditNoiseTest extends TenantTestCase
{
    private function auditCount(): int
    {
        return AuditLog::withoutGlobalScopes()->count();
    }

    public function test_writes_inside_without_are_not_audited(): void
    {
        $before = $this->auditCount();

        Auditing::without(function () {
            EmissionSource::create([
                'name' => 'Seeded Source '.uniqid(),
                'scope' => 1,
                'description' => 'reference data',
            ]);
        });

        $this->assertSame(
            $before,
            $this->auditCount(),
            'Seeding reference data still wrote to the audit trail.'
        );
    }

    /**
     * The suppression must be narrow. If it leaked past the callback, ordinary
     * user edits would stop being recorded — which is far worse than the noise
     * it was introduced to remove.
     */
    public function test_auditing_resumes_after_the_callback(): void
    {
        Auditing::without(fn () => null);

        $before = $this->auditCount();

        EmissionSource::create([
            'name' => 'User Source '.uniqid(),
            'scope' => 1,
            'description' => 'entered by a person',
        ]);

        $this->assertSame(
            $before + 1,
            $this->auditCount(),
            'Auditing did not resume after suppression, so real changes go unrecorded.'
        );
    }

    /**
     * A throwing callback must not leave auditing off for the rest of the
     * process — that would silently disable the audit trail for everything
     * downstream of one failed seed.
     */
    public function test_auditing_resumes_even_when_the_callback_throws(): void
    {
        try {
            Auditing::without(function () {
                throw new \RuntimeException('seed blew up');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertTrue(Auditing::enabled(), 'A failed seed left auditing disabled process-wide.');
    }

    public function test_nesting_restores_the_outer_state_rather_than_assuming_true(): void
    {
        Auditing::without(function () {
            Auditing::without(fn () => null);

            $this->assertFalse(
                Auditing::enabled(),
                'A nested suppression re-enabled auditing inside an outer one.'
            );
        });

        $this->assertTrue(Auditing::enabled());
    }
}
