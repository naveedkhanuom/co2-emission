<?php

namespace Tests\Feature;

use App\Auditable;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmissionFactor;
use App\Models\ReportingPeriod;
use App\Models\User;
use Tests\TenantTestCase;

/**
 * GHG-05 — what the change history covers.
 *
 * The trail already covered emission records, factors, targets, the boundary
 * assessment and the MRV tables. It did not cover the things that decide whether
 * a figure may change at all, or who may change it: the reporting-period lock,
 * user accounts, the company boundary, and the settings and instruments that
 * feed the calculation.
 */
class AuditCoverageTest extends TenantTestCase
{
    /**
     * The models an assurer would expect to find in the trail.
     *
     * Kept as an explicit list so removing the trait from one of them fails here
     * rather than going unnoticed.
     */
    public static function auditedModels(): array
    {
        return [
            // Governance: declares a year final.
            'ReportingPeriod' => [\App\Models\ReportingPeriod::class],
            // Access control.
            'User' => [\App\Models\User::class],
            // Organisational boundary and the settings that drive calculations.
            'Company' => [\App\Models\Company::class],
            'CompanySetting' => [\App\Models\CompanySetting::class],
            // Anything that changes a reported figure.
            'EmissionRecord' => [\App\Models\EmissionRecord::class],
            'EmissionFactor' => [\App\Models\EmissionFactor::class],
            'EioFactor' => [\App\Models\EioFactor::class],
            'EmissionSource' => [\App\Models\EmissionSource::class],
            'EnergyAttributeCertificate' => [\App\Models\EnergyAttributeCertificate::class],
            // Attribution and structure. Facility/department names are foreign
            // keys in all but name, so a rename is a data event worth recording.
            'Supplier' => [\App\Models\Supplier::class],
            'Facilities' => [\App\Models\Facilities::class],
            'Department' => [\App\Models\Department::class],
            'Site' => [\App\Models\Site::class],
            // Commitments.
            'Target' => [\App\Models\Target::class],
        ];
    }

    /**
     * @dataProvider auditedModels
     */
    public function test_the_model_is_audited(string $model): void
    {
        $this->assertContains(
            Auditable::class,
            class_uses_recursive($model),
            $model.' is not covered by the audit trail.'
        );
    }

    /**
     * Locking a year is the governance action; it has to leave a record of who.
     */
    public function test_locking_a_reporting_period_is_recorded(): void
    {
        $company = Company::create([
            'name' => 'Audit Coverage Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $period = ReportingPeriod::create([
            'company_id' => $company->id,
            'year' => 2031,
            'status' => 'open',
        ]);

        $period->update(['status' => 'locked']);

        $entry = AuditLog::where('auditable_type', ReportingPeriod::class)
            ->where('auditable_id', $period->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry, 'Locking a reporting period left no audit entry.');
        $this->assertSame('locked', $entry->new_values['status'] ?? null);
        $this->assertSame('open', $entry->old_values['status'] ?? null);
    }

    /**
     * The trail must not become a store of credential material.
     */
    public function test_a_password_change_is_never_written_into_the_trail(): void
    {
        $company = Company::create([
            'name' => 'Audit Secret Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Secret User',
            'email' => 'secret-'.uniqid().'@example.test',
            'password' => bcrypt('original-password'),
            'company_id' => $company->id,
        ]);

        $user->update([
            'name' => 'Renamed User',
            'password' => bcrypt('a-new-password'),
        ]);

        $entries = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->get();

        $this->assertNotEmpty($entries, 'User changes are not audited at all.');

        foreach ($entries as $entry) {
            foreach (['old_values', 'new_values'] as $side) {
                $values = $entry->{$side} ?? [];
                $this->assertArrayNotHasKey('password', $values, "A password reached the audit trail via {$side}.");
                $this->assertArrayNotHasKey('remember_token', $values, "A token reached the audit trail via {$side}.");
            }
        }

        // The non-sensitive part of the same change is still recorded.
        $this->assertTrue(
            $entries->contains(fn ($e) => ($e->new_values['name'] ?? null) === 'Renamed User'),
            'Excluding secrets also dropped the rest of the change.'
        );
    }

    /**
     * A factor change restates every report built on it, so it has to be
     * attributable. (Already covered before this change — pinned so it stays.)
     */
    public function test_changing_an_emission_factor_is_recorded(): void
    {
        $factor = EmissionFactor::withoutGlobalScopes()->firstOrFail();
        $original = $factor->factor_value;

        $factor->update(['factor_value' => (float) $original + 0.001]);

        $this->assertNotNull(
            AuditLog::where('auditable_type', EmissionFactor::class)
                ->where('auditable_id', $factor->id)
                ->where('event', 'updated')
                ->latest('id')
                ->first(),
            'An emission factor change left no audit entry.'
        );

        $factor->update(['factor_value' => $original]);
    }
}
