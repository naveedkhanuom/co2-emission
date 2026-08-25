<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * GHG-03 end to end.
 *
 * The Scope 1 entry page computes CO2e in the browser and posts only the total.
 * Before this, the server stored no factor, so EmissionFigureVerifier had
 * nothing to check the total against and took the browser's word for it.
 *
 * These tests go through the real store() endpoint, so they cover the wiring as
 * well as the arithmetic.
 */
class Scope1FactorResolutionTest extends TestCase
{
    use DatabaseTransactions;

    /** Diesel (Stationary) per litre, derived from config/scope1_sources.php. */
    private const DIESEL_LITRE_FACTOR = 0.0026847237;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Factor Test Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Factor Test User',
            'email' => 'factor-test-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['create-emission-record', 'list-emission-records']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 1,
            'emissionSourceSelect' => 'Diesel (Stationary)',
            'activityData' => 1000,
            'activityUnit' => 'liters',
            'co2eValue' => 2.6847,
            'confidenceLevel' => 'medium',
            'dataSource' => 'manual',
        ], $overrides);
    }

    private function latestRecord(): EmissionRecord
    {
        return EmissionRecord::where('company_id', $this->company->id)
            ->latest('id')
            ->firstOrFail();
    }

    public function test_an_entry_without_a_factor_gets_one_derived_server_side(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())
            ->assertOk();

        $record = $this->latestRecord();

        $this->assertNotNull($record->emission_factor, 'No factor was derived.');
        $this->assertEqualsWithDelta(
            self::DIESEL_LITRE_FACTOR,
            (float) $record->emission_factor,
            1e-9
        );
    }

    /**
     * The point of the whole exercise: a browser-supplied total that does not
     * match the server's own arithmetic is now caught, corrected, and held for
     * review instead of landing in a report as fact.
     */
    public function test_a_wrong_client_total_is_now_caught_and_held_for_review(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload(['co2eValue' => 99]))
            ->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(2.6847, (float) $record->co2e_value, 0.001);
        $this->assertSame('draft', $record->status);
        $this->assertNotEmpty($record->notes, 'The discrepancy should be explained on the record.');
    }

    /**
     * A correct total is left exactly as posted and stays active — the guard
     * must not push legitimate entries into the review queue.
     */
    public function test_a_correct_total_is_left_alone_and_stays_active(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())
            ->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(2.6847, (float) $record->co2e_value, 0.0005);
        $this->assertSame('active', $record->status);
    }

    /**
     * Manual Entry supplies its own factor from the database library. The
     * catalogue fills gaps; it does not overrule a caller.
     */
    public function test_a_supplied_factor_is_never_overwritten(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'emissionFactor' => 0.00300,
            'co2eValue' => 3.0,
        ]))->assertOk();

        $record = $this->latestRecord();

        $this->assertEqualsWithDelta(0.003, (float) $record->emission_factor, 1e-9);
    }

    /**
     * An unrecognised source must leave the record exactly as it would have been
     * before this change — saved, just not verifiable.
     */
    public function test_an_unknown_source_still_saves_without_a_factor(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'emissionSourceSelect' => 'Unobtainium Combustion',
        ]))->assertOk();

        $record = $this->latestRecord();

        $this->assertNull($record->emission_factor);
        $this->assertEqualsWithDelta(2.6847, (float) $record->co2e_value, 0.001);
    }

    /**
     * Without a unit, Diesel could be litres or gallons — a factor roughly 3.8x
     * apart. Deriving one anyway would corrupt the figure, so the record stays
     * unverifiable instead.
     */
    public function test_a_missing_unit_leaves_the_factor_unresolved(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload([
            'activityUnit' => null,
        ]))->assertOk();

        $this->assertNull($this->latestRecord()->emission_factor);
    }

    /**
     * Provenance: the record has to say where its factor came from.
     */
    public function test_the_record_records_where_its_factor_came_from(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())
            ->assertOk();

        $record = $this->latestRecord();

        $this->assertNotEmpty($record->factor_dataset);
        $this->assertStringContainsString('IPCC 74100', $record->factor_dataset);
    }

    /**
     * The derived factor must survive the round trip through the column at full
     * precision — this is what decimal(10,4) was silently destroying.
     */
    public function test_the_derived_factor_survives_the_database_round_trip(): void
    {
        $this->postJson(route('emission-records.store'), $this->payload())
            ->assertOk();

        $stored = (float) $this->latestRecord()->fresh()->emission_factor;

        $this->assertEqualsWithDelta(
            self::DIESEL_LITRE_FACTOR,
            $stored,
            self::DIESEL_LITRE_FACTOR * 0.0001,
            'The stored factor lost precision on write.'
        );
    }
}
