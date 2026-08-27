<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * GHG-03c — Scope 3 factor derivation, the last scope where a client's figure
 * could not be checked.
 *
 * Scope 1 and 2 derive their factor from config, through BuiltInFactorCatalog.
 * Scope 3 has no config catalogue and never will: its 94 sources are seeded
 * reference data that clients can extend. So it resolves from the client's own
 * factor library instead, and until it did, a Scope 3 entry stored the
 * browser's total with nothing beside it for EmissionFigureVerifier to check.
 *
 * The zero-factor tests are the ones that matter most. Eleven seeded Scope 3
 * factors are category-level placeholders worth 0 — pricing activity against
 * one would report a confident, arithmetically checkable ZERO for real
 * emissions, which is worse than storing no factor at all.
 */
class Scope3FactorResolutionTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Scope3 Test Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Scope3 Test User',
            'email' => 'scope3-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['create-emission-record', 'list-emission-records']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 3,
            'emissionSourceSelect' => 'Scope 3 - 6. Business Travel',
            'activityData' => 10000,
            'activityUnit' => 'km',
            'co2eValue' => 1.8,          // 10,000 km × 0.00018 tCO2e/km
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
            'scope3_category_id' => null,
            'calculation_method' => 'activity-based',
        ], $overrides);
    }

    private function latestRecord(): ?EmissionRecord
    {
        return EmissionRecord::where('company_id', $this->company->id)->latest('id')->first();
    }

    public function test_an_entry_without_a_factor_gets_one_from_the_library(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())->assertOk();

        $record = $this->latestRecord();

        $this->assertNotNull($record);
        $this->assertNotNull($record->emission_factor, 'Scope 3 must no longer store a bare total.');
        $this->assertEqualsWithDelta(0.00018, (float) $record->emission_factor, 0.0000001);
    }

    public function test_the_record_records_where_its_factor_came_from(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())->assertOk();

        $record = $this->latestRecord();

        $this->assertNotEmpty(
            $record->factor_dataset,
            'A derived factor has to say where it came from, or it cannot be defended.'
        );
    }

    /**
     * The point of deriving the factor at all: with one stored, the verifier
     * can check activity × factor against the total the browser sent.
     */
    public function test_a_wrong_client_total_is_caught_and_held_for_review(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload(['co2eValue' => 999]))->assertOk();

        $record = $this->latestRecord();

        $this->assertNotSame(
            'active',
            $record->status,
            'A total that disagrees with activity x factor must not pass as active.'
        );
    }

    public function test_a_correct_total_is_left_alone(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())->assertOk();

        $this->assertSame('active', $this->latestRecord()->status);
    }

    public function test_a_supplied_factor_is_never_overwritten(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'emissionFactor' => 0.00025,
            'co2eValue' => 2.5,
        ]))->assertOk();

        $this->assertEqualsWithDelta(
            0.00025,
            (float) $this->latestRecord()->emission_factor,
            0.0000001,
            'A factor the caller supplied must survive.'
        );
    }

    /**
     * A category-level placeholder is worth 0 because the category cannot have
     * a per-unit factor, not because its emissions are nil. Pricing against it
     * would report a checkable zero for real activity.
     */
    public function test_a_zero_placeholder_factor_is_refused(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'emissionSourceSelect' => 'Scope 3 - 1. Purchased Goods & Services',
            'activityUnit' => 'unit',
            'co2eValue' => 12.5,
        ]))->assertOk();

        $record = $this->latestRecord();

        $this->assertNull(
            $record->emission_factor,
            'A zero placeholder must be treated as no factor, never priced against.'
        );
        $this->assertNotSame(
            0.0,
            (float) $record->co2e_value,
            'The entry must keep its own figure rather than being zeroed.'
        );
    }

    public function test_an_unknown_source_still_saves_without_a_factor(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'emissionSourceSelect' => 'Something The Library Has Never Heard Of',
        ]))->assertOk();

        $record = $this->latestRecord();

        $this->assertNotNull($record, 'An unresolvable source must still save.');
        $this->assertNull($record->emission_factor);
    }

    public function test_a_mismatched_unit_resolves_nothing_rather_than_guessing(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'activityUnit' => 'furlongs',
        ]))->assertOk();

        $this->assertNull(
            $this->latestRecord()->emission_factor,
            'A unit the library does not hold must not be priced with another unit\'s number.'
        );
    }

    /**
     * Source names are not unique across scopes. Resolving must match the
     * scope too, or a Scope 1 name could be priced with a Scope 3 number.
     */
    public function test_resolution_is_scoped_to_the_declared_scope(): void
    {
        $scope1Source = EmissionSource::where('scope', 1)->first();

        if (! $scope1Source) {
            $this->markTestSkipped('No Scope 1 source seeded.');
        }

        $this->postJson(route('emission-records.store'), $this->payload([
            'emissionSourceSelect' => $scope1Source->name,
            'activityUnit' => 'km',
            'co2eValue' => 1.8,
        ]))->assertOk();

        $record = $this->latestRecord();

        // Declared Scope 3, but the name belongs to a Scope 1 source, so the
        // Scope 3 library holds nothing for it.
        $this->assertNull($record->emission_factor);
    }
}
