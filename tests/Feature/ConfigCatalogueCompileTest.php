<?php

namespace Tests\Feature;

use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Services\Factors\BuiltInFactorCatalog;
use App\Services\Factors\Import\ConfigCatalogueCompiler;
use App\Services\Factors\LibraryFactorCatalog;
use Tests\TenantTestCase;

/**
 * GHG-04 phase 2 — the built-in catalogue compiled into the factor library, and
 * Scope 1/2 resolving from the database.
 *
 * Scope 1 and 2 priced activity from config while Manual Entry and Scope 3 priced
 * it from emission_factors. Records from the config path stored provenance as the
 * label string "Built-in Scope 1 catalogue v1"; records from the database path
 * stored an emission_factor_id. Only the second is provenance an assurer can
 * follow — the first is prose describing provenance, with nothing behind it.
 *
 * THE PROPERTY THAT MAKES THIS SAFE
 *
 * Compiling must not restate a single figure. Values come from
 * BuiltInFactorCatalog::entries(), which prices through the same factorFor() the
 * entry pages use, so a compiled row carries exactly the number the page would
 * have calculated. test_every_catalogue_entry_resolves_to_the_same_value is the
 * assertion this whole change rests on: if it fails, switching the resolver has
 * silently changed client numbers.
 */
class ConfigCatalogueCompileTest extends TenantTestCase
{
    private function compile(bool $pretend = false): array
    {
        return app(ConfigCatalogueCompiler::class)
            ->compile(app(BuiltInFactorCatalog::class), $pretend);
    }

    private function catalog(): BuiltInFactorCatalog
    {
        return app(BuiltInFactorCatalog::class);
    }

    private function library(): LibraryFactorCatalog
    {
        return app(LibraryFactorCatalog::class);
    }

    /**
     * Enumeration must agree with the resolver the entry pages actually call.
     * If these drift, compiled rows stop matching what the browser calculated
     * and every Scope 1 figure quietly moves.
     */
    public function test_enumeration_prices_identically_to_the_live_resolver(): void
    {
        $catalog = $this->catalog();
        $mismatched = [];

        foreach ($catalog->entries() as $entry) {
            if ($entry['scope'] !== 1) {
                continue;
            }

            $resolved = $catalog->resolve(1, $entry['source'], $entry['unit']);

            if ($resolved === null || abs($resolved->value - $entry['value']) > 1e-12) {
                $mismatched[] = $entry['source'].' ['.$entry['unit'].']';
            }
        }

        $this->assertSame([], $mismatched, 'entries() and resolve() price differently.');
    }

    /**
     * THE safety assertion. Every catalogue entry must resolve from the database
     * to the same number the config catalogue gives.
     *
     * Tolerance is 1e-6 relative, not exact: factor_value is decimal(20,10) and
     * some catalogue values carry eleven decimals, so storage rounds them. The
     * observed worst case is 4.6e-8 — on a million-tonne inventory that is a few
     * grams. Anything above 1e-6 would be a real change and must fail.
     */
    public function test_every_catalogue_entry_resolves_to_the_same_value(): void
    {
        $this->compile();

        $drifted = [];

        foreach ($this->catalog()->entries() as $entry) {
            $resolved = $this->library()->resolve(
                $entry['scope'],
                $entry['is_grid'] ? BuiltInFactorCatalog::GRID_SOURCE : $entry['source'],
                $entry['unit'],
                $entry['is_grid'] ? ['region' => $entry['region']] : []
            );

            if ($resolved === null) {
                $drifted[] = 'unresolvable: '.$entry['source'].' ['.$entry['unit'].']';

                continue;
            }

            if ($entry['value'] == 0.0) {
                if ($resolved->value != 0.0) {
                    $drifted[] = 'zero became '.$resolved->value.': '.$entry['source'];
                }

                continue;
            }

            $relative = abs($resolved->value - $entry['value']) / abs($entry['value']);

            if ($relative > 1e-6) {
                $drifted[] = sprintf('%s [%s] config=%s db=%s', $entry['source'], $entry['unit'], $entry['value'], $resolved->value);
            }
        }

        $this->assertSame([], $drifted, 'Compiling changed a figure. Switching the resolver is NOT safe.');
    }

    /**
     * The point of the exercise: a foreign key rather than a label string.
     */
    public function test_every_entry_resolves_with_a_factor_id(): void
    {
        $this->compile();

        $unlocked = [];

        foreach ($this->catalog()->entries() as $entry) {
            $resolved = $this->library()->resolve(
                $entry['scope'],
                $entry['is_grid'] ? BuiltInFactorCatalog::GRID_SOURCE : $entry['source'],
                $entry['unit'],
                $entry['is_grid'] ? ['region' => $entry['region']] : []
            );

            if ($resolved?->emissionFactorId === null) {
                $unlocked[] = $entry['source'].' ['.$entry['unit'].']';
            }
        }

        $this->assertSame([], $unlocked, 'Entries resolved without an emission_factor_id to lock.');
    }

    /**
     * A zero from a published catalogue is a measurement — a battery-electric van
     * has no direct Scope 1 emissions. A zero on a seeded placeholder row is not,
     * and must still be refused.
     */
    public function test_a_published_zero_resolves_but_a_placeholder_zero_does_not(): void
    {
        $this->compile();

        $bev = $this->library()->resolve(1, 'Fleet - Battery Electric', 'km');
        $this->assertNotNull($bev, 'A published zero factor was refused.');
        $this->assertSame(0.0, $bev->value);
        $this->assertNotNull($bev->emissionFactorId);

        $placeholder = EmissionSource::create(['name' => 'Placeholder '.uniqid(), 'scope' => 3]);
        EmissionFactor::create([
            'emission_source_id' => $placeholder->id,
            'unit' => 'unit', 'factor_value' => 0, 'is_active' => true,
        ]);

        $this->assertNull(
            $this->library()->resolve(3, $placeholder->name, 'unit'),
            'A seeded placeholder zero was treated as a measurement.'
        );
    }

    /**
     * GHG-18. The 22 regional grid factors existed only in a config array, so
     * grid electricity was generic for every client outside the default region.
     */
    public function test_the_regional_grid_factors_become_queryable_rows(): void
    {
        $this->compile();

        $grid = EmissionSource::where('name', BuiltInFactorCatalog::GRID_SOURCE)->first();
        $this->assertNotNull($grid);

        $regions = EmissionFactor::where('emission_source_id', $grid->id)
            ->where('is_active', true)
            ->distinct()
            ->pluck('region');

        $this->assertGreaterThanOrEqual(20, $regions->count(), 'Regional grid factors are missing.');
        $this->assertContains('UAE (Dubai / DEWA)', $regions->all());
        $this->assertContains('France', $regions->all());
    }

    /**
     * Different regions must give different numbers — French nuclear and South
     * African coal differ by more than an order of magnitude, and pricing both at
     * a global average is the failure this closes.
     */
    public function test_grid_regions_price_differently(): void
    {
        $this->compile();

        $france = $this->library()->resolve(2, 'EV Charging (Third-Party)', 'kWh', ['region' => 'France']);
        $southAfrica = $this->library()->resolve(2, 'EV Charging (Third-Party)', 'kWh', ['region' => 'South Africa']);

        $this->assertNotNull($france);
        $this->assertNotNull($southAfrica);
        $this->assertGreaterThan($france->value * 5, $southAfrica->value);
    }

    /**
     * All 13 grid-priced sources share one regional factor, so a record's
     * emission_factor_id points at the grid row that priced it rather than at a
     * synthetic per-source row.
     */
    public function test_every_grid_source_resolves_to_the_same_regional_factor(): void
    {
        $this->compile();

        $ids = [];
        foreach (['EV Charging (Third-Party)', 'Green Tariff Electricity', 'Landlord-Supplied Electricity'] as $name) {
            $resolved = $this->library()->resolve(2, $name, 'kWh', ['region' => 'UK']);
            $this->assertNotNull($resolved, "{$name} did not resolve for a known region.");
            $ids[] = $resolved->emissionFactorId;
        }

        $this->assertCount(1, array_unique($ids), 'Grid sources resolved to different factor rows.');
    }

    /**
     * A Scope 2 entry with no region must NOT fall back to a generic row: that
     * would price one region's electricity at another's and present it as the
     * client's own figure.
     */
    public function test_grid_electricity_without_a_region_does_not_resolve(): void
    {
        $this->compile();

        $this->assertNull(
            $this->library()->resolve(2, BuiltInFactorCatalog::GRID_SOURCE, 'kWh'),
            'Grid electricity was priced without a region.'
        );
    }

    /**
     * Attribution comes from the catalogue's own citation. An entry citing nobody
     * gets no organisation — filing it under IPCC because most of the catalogue
     * is IPCC would put a citation on a client's figure that nobody published.
     */
    public function test_unattributed_entries_are_marked_rather_than_guessed(): void
    {
        $result = $this->compile();

        $this->assertGreaterThan(0, $result['unattributed']);

        $rows = EmissionFactor::where('dataset_name', ConfigCatalogueCompiler::UNATTRIBUTED)->get();
        $this->assertGreaterThan(0, $rows->count());

        foreach ($rows as $row) {
            $this->assertNull($row->organization_id, 'An unattributed factor was given a publisher.');
        }
    }

    public function test_recompiling_supersedes_rather_than_duplicating(): void
    {
        $this->compile();
        $active = EmissionFactor::where('dataset_name', ConfigCatalogueCompiler::DATASET)->where('is_active', true)->count();

        $second = $this->compile();

        $this->assertSame(
            $active,
            EmissionFactor::where('dataset_name', ConfigCatalogueCompiler::DATASET)->where('is_active', true)->count(),
            'Recompiling left more than one active row per entry.'
        );
        $this->assertGreaterThan(0, $second['superseded']);
    }

    /**
     * Compiling must not touch an imported publisher's rows for the same
     * activity — two publishers disagreeing is the reason for holding both.
     */
    public function test_it_leaves_imported_publisher_rows_alone(): void
    {
        $defraActive = EmissionFactor::where('dataset_name', 'DEFRA/DESNZ')->where('is_active', true)->count();

        $this->compile();

        $this->assertSame(
            $defraActive,
            EmissionFactor::where('dataset_name', 'DEFRA/DESNZ')->where('is_active', true)->count(),
            'Compiling the built-in catalogue retired an imported publisher\'s factors.'
        );
    }
}
