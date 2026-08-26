<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * GHG-03b — Scope 2 factor derivation end to end, plus GHG-09.
 *
 * Scope 2 needs two things Scope 1 does not: the grid region an `isGrid` source
 * is priced at, and any factor the user typed by hand. Both live only in the
 * entry form, so both are posted and asserted here.
 */
class Scope2FactorResolutionTest extends TenantTestCase
{
    /** UAE (Abu Dhabi / ADWEC) grid factor, kgCO2/kWh. */
    private const ADWEC = 0.4041;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Scope2 Test Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Scope2 Test User',
            'email' => 'scope2-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['create-emission-record', 'edit-emission-record', 'list-emission-records']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /**
     * 10,000 kWh at the Abu Dhabi grid factor.
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 2,
            'emissionSourceSelect' => 'Purchased Electricity (Location-Based)',
            'activityData' => 10000,
            'activityUnit' => 'kWh',
            'scope2Region' => 'UAE (Abu Dhabi / ADWEC)',
            'co2eValue' => round(10000 * self::ADWEC / 1000, 4),
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
        ], $overrides);
    }

    private function latestRecord(): EmissionRecord
    {
        return EmissionRecord::where('company_id', $this->company->id)->latest('id')->firstOrFail();
    }

    public function test_a_grid_entry_gets_its_factor_derived_from_the_posted_region(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(self::ADWEC / 1000, (float) $record->emission_factor, 1e-9);
        $this->assertSame('active', $record->status);
    }

    /**
     * The point of posting the region: a wrong client total is now caught.
     */
    public function test_a_wrong_client_total_is_caught_and_held_for_review(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload(['co2eValue' => 999]))->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(10000 * self::ADWEC / 1000, (float) $record->co2e_value, 0.001);
        $this->assertSame('draft', $record->status);
    }

    /**
     * Without a region the server cannot price a grid source, so the record
     * saves unverified rather than being priced at some default region.
     */
    public function test_a_grid_entry_without_a_region_saves_unverified(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload(['scope2Region' => null]))->assertOk();

        $this->assertNull($this->latestRecord()->emission_factor);
    }

    /**
     * MWh must not be priced as though it were kWh — that was the 1000x class of
     * error the label-keyed conversion allowed.
     */
    public function test_megawatt_hours_are_converted_before_pricing(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'activityData' => 10,
            'activityUnit' => 'MWh',
            'co2eValue' => round(10 * 1000 * self::ADWEC / 1000, 4),
        ]))->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(1000 * self::ADWEC / 1000, (float) $record->emission_factor, 1e-9);
        $this->assertSame('active', $record->status);
    }

    /**
     * A hand-entered factor is still verified — checking the total against the
     * user's own factor catches arithmetic and stale-bundle errors even though
     * it cannot vouch for the factor itself. The provenance says so.
     */
    public function test_a_user_supplied_factor_is_used_and_labelled_as_such(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'scope2Region' => 'Custom (enter manually)',
            'scope2FactorOverride' => 0.55,
            'co2eValue' => round(10000 * 0.55 / 1000, 4),
        ]))->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(0.55 / 1000, (float) $record->emission_factor, 1e-9);
        $this->assertStringContainsString('user-supplied', $record->factor_dataset);
        $this->assertSame('active', $record->status);
    }

    /**
     * A total that disagrees with the user's OWN factor is still a mismatch.
     */
    public function test_a_user_supplied_factor_still_catches_a_wrong_total(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'scope2Region' => 'Custom (enter manually)',
            'scope2FactorOverride' => 0.55,
            'co2eValue' => 999,
        ]))->assertOk();

        $this->assertSame('draft', $this->latestRecord()->status);
    }

    /**
     * The Custom region with nothing entered means "not stated yet", not zero.
     */
    public function test_the_custom_region_without_a_factor_saves_unverified(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'scope2Region' => 'Custom (enter manually)',
        ]))->assertOk();

        $this->assertNull($this->latestRecord()->emission_factor);
    }

    /**
     * GHG-09: editing a record must not erase the unit it recorded. Manual Entry
     * has no unit field, so an update that omits it has to leave it alone.
     */
    public function test_updating_a_record_without_a_unit_preserves_the_existing_one(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())->assertOk();
        $record = $this->latestRecord();
        $this->assertSame('kWh', $record->activity_unit);

        $this->putJson(route('emission_records.update', $record->id), [
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 2,
            'emissionSourceSelect' => 'Purchased Electricity (Location-Based)',
            'activityData' => 20000,
            'emissionFactor' => self::ADWEC / 1000,
            'co2eValue' => round(20000 * self::ADWEC / 1000, 4),
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
        ])->assertOk();

        $this->assertSame('kWh', $record->fresh()->activity_unit, 'The unit was erased by an update.');
    }

    /**
     * And an update that does supply a unit must be able to change it.
     */
    public function test_an_update_can_change_the_unit_when_it_supplies_one(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())->assertOk();
        $record = $this->latestRecord();

        $this->putJson(route('emission_records.update', $record->id), [
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 2,
            'emissionSourceSelect' => 'Purchased Electricity (Location-Based)',
            'activityData' => 10,
            'activityUnit' => 'MWh',
            'emissionFactor' => 1000 * self::ADWEC / 1000,
            'co2eValue' => round(10 * 1000 * self::ADWEC / 1000, 4),
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
        ])->assertOk();

        $this->assertSame('MWh', $record->fresh()->activity_unit);
    }
}
