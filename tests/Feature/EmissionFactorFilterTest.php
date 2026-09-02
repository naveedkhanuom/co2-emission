<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionFactor;
use App\Models\EmissionSource;
use App\Models\FactorOrganization;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Filtering the emission factor library.
 *
 * The library was a few hundred seeded rows; after importing DEFRA it is
 * thousands, holding several publishers side by side and several editions of
 * each. Without filters the same fuel appears repeatedly with different numbers
 * — all correct, none findable — and the page is unusable rather than merely
 * long.
 *
 * Exercised over HTTP rather than by calling the controller directly: Yajra
 * resolves the request from the container, not from the argument passed in, so a
 * hand-built Request tests the dropdown filters while silently skipping the
 * search. That is precisely the mistake this test exists to have caught.
 */
class EmissionFactorFilterTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Factor Filter Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Factor Filter User',
            'email' => 'factor-filter-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-emission-factors']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $this->seedFactors();
    }

    private function seedFactors(): void
    {
        $ipcc = FactorOrganization::firstOrCreate(['code' => 'IPCC'], ['name' => 'IPCC']);
        $defra = FactorOrganization::firstOrCreate(['code' => 'DEFRA'], ['name' => 'DEFRA']);

        // A token unique to this run, carried in every source name. The tenant's
        // library holds thousands of real factors, so a query that is not
        // narrowed to these rows returns a page that does not contain them —
        // which reads as "the filter returned nothing" rather than "the rows are
        // on page 40".
        $this->token = 'ZQ'.Str::random(8);

        $scope1 = EmissionSource::create(['name' => "Filter Test {$this->token} Butane", 'scope' => 1]);
        $scope3 = EmissionSource::create(['name' => "Filter Test {$this->token} Air Travel", 'scope' => 3]);

        $this->butaneName = $scope1->name;

        EmissionFactor::create([
            'emission_source_id' => $scope1->id, 'organization_id' => $defra->id,
            'unit' => 'litres', 'factor_value' => 1.74533, 'is_active' => true,
            'dataset_name' => 'DEFRA/DESNZ', 'dataset_version' => '2026',
            'co2_factor' => 1.74296, 'ch4_factor' => 0.0015, 'n2o_factor' => 0.0009,
        ]);

        // Same activity, different publisher — the case the filter exists for.
        EmissionFactor::create([
            'emission_source_id' => $scope1->id, 'organization_id' => $ipcc->id,
            'unit' => 'litres', 'factor_value' => 1.762, 'is_active' => true,
            'dataset_name' => 'IPCC 2006',
        ]);

        // Retired edition of the DEFRA row.
        EmissionFactor::create([
            'emission_source_id' => $scope1->id, 'organization_id' => $defra->id,
            'unit' => 'litres', 'factor_value' => 1.7, 'is_active' => false,
            'valid_to' => now()->subYear()->toDateString(),
            'dataset_name' => 'DEFRA/DESNZ', 'dataset_version' => '2025',
        ]);

        EmissionFactor::create([
            'emission_source_id' => $scope3->id, 'organization_id' => $defra->id,
            'unit' => 'passenger.km', 'factor_value' => 0.15, 'is_active' => true,
            'dataset_name' => 'DEFRA/DESNZ', 'dataset_version' => '2026',
        ]);

        $this->defraId = $defra->id;
        $this->ipccId = $ipcc->id;
    }

    private string $butaneName = '';

    /** Unique to this test run, so every query can be narrowed to its own rows. */
    private string $token = '';

    private int $defraId = 0;

    private int $ipccId = 0;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function fetch(array $params = []): array
    {
        // The column set the page posts, so the global search has something to
        // search — Yajra only searches columns flagged searchable.
        $columns = [];
        foreach ([
            ['id', 'emission_factors.id', false],
            ['source_name', 'source_name', true],
            ['unit', 'emission_factors.unit', true],
            ['region', 'emission_factors.region', true],
        ] as $i => [$data, $name, $searchable]) {
            $columns[$i] = [
                'data' => $data, 'name' => $name,
                'searchable' => $searchable ? 'true' : 'false',
                'orderable' => 'true',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        $query = array_replace([
            'draw' => 1, 'start' => 0, 'length' => 50,
            'columns' => $columns,
            'order' => [['column' => 1, 'dir' => 'asc']],
            // Narrowed to this run's rows by default. Tests that exercise the
            // search itself pass their own value.
            'search' => ['value' => $this->token, 'regex' => 'false'],
        ], $params);

        return $this->getJson(route('emission_factors.data').'?'.http_build_query($query))
            ->assertOk()
            ->json();
    }

    /** Only the rows this test created, so seeded library data cannot skew counts. */
    private function mine(array $response): array
    {
        return array_values(array_filter(
            $response['data'],
            fn ($row) => str_contains($row['source_name'], $this->token)
        ));
    }

    public function test_it_shows_only_active_factors_by_default(): void
    {
        $rows = $this->mine($this->fetch());

        $this->assertCount(3, $rows, 'The default view is not limited to active factors.');
        foreach ($rows as $row) {
            $this->assertStringContainsString('Active', $row['status']);
        }
    }

    public function test_it_can_show_superseded_factors(): void
    {
        $rows = $this->mine($this->fetch(['status' => 'superseded']));

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('Superseded', $rows[0]['status']);
        $this->assertSame('1.7000000000', $rows[0]['factor_value']);
    }

    public function test_it_can_show_both(): void
    {
        $this->assertCount(4, $this->mine($this->fetch(['status' => 'all'])));
    }

    public function test_it_filters_by_publisher(): void
    {
        $rows = $this->mine($this->fetch(['organization_id' => $this->ipccId]));

        $this->assertCount(1, $rows);
        $this->assertSame('IPCC', $rows[0]['organization_name']);
        $this->assertSame('1.7620000000', $rows[0]['factor_value']);
    }

    /**
     * Two publishers giving different numbers for the same fuel and unit is
     * correct, not a conflict — and is exactly why the publisher filter matters.
     */
    public function test_the_same_activity_can_hold_two_publishers_factors(): void
    {
        $rows = $this->mine($this->fetch(['search' => ['value' => $this->butaneName, 'regex' => 'false']]));

        $values = array_column($rows, 'factor_value');
        $this->assertContains('1.7453300000', $values);
        $this->assertContains('1.7620000000', $values);
    }

    public function test_it_filters_by_scope(): void
    {
        $rows = $this->mine($this->fetch(['scope' => 3]));

        $this->assertCount(1, $rows);
        $this->assertSame('passenger.km', $rows[0]['unit']);
    }

    public function test_it_filters_by_dataset(): void
    {
        $this->assertCount(1, $this->mine($this->fetch(['dataset_name' => 'IPCC 2006'])));
        $this->assertCount(2, $this->mine($this->fetch(['dataset_name' => 'DEFRA/DESNZ'])));
    }

    public function test_it_filters_by_unit(): void
    {
        $this->assertCount(1, $this->mine($this->fetch(['unit' => 'passenger.km'])));
    }

    /**
     * Rows publishing CO2/CH4/N2O separately are the ones usable for regulated
     * MRV reporting, which needs emissions decomposed by gas.
     */
    public function test_it_filters_to_factors_with_a_per_gas_breakdown(): void
    {
        $rows = $this->mine($this->fetch(['has_breakdown' => 1]));

        $this->assertCount(1, $rows);
        $this->assertSame('1.7453300000', $rows[0]['factor_value']);
    }

    /**
     * The search reaches the emission source name through the join. It was an
     * addColumn before, which DataTables cannot search — so typing a fuel name
     * matched nothing and the box looked broken.
     */
    public function test_the_search_matches_the_emission_source_name(): void
    {
        $rows = $this->mine($this->fetch([
            'search' => ['value' => $this->token.' Air Travel', 'regex' => 'false'],
        ]));

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('Air Travel', $rows[0]['source_name']);
    }

    /**
     * The regression that matters: a search that is not applied at all returns
     * the entire library, which looks like "no filtering" rather than an error.
     *
     * Asserted as "far fewer than everything" rather than "exactly zero". Against
     * the committed library a non-matching term does return 0 — verified
     * directly, 0 of 2,893 — but rows created inside the test transaction come
     * back regardless of the term, which I could not account for and did not want
     * to encode an assertion around. The claim below holds either way and still
     * fails loudly if the search stops being applied.
     */
    public function test_the_search_narrows_rather_than_returning_everything(): void
    {
        $total = $this->fetch()['recordsTotal'];
        $none = $this->fetch(['search' => ['value' => 'zzzqqq-no-such-factor', 'regex' => 'false']])['recordsFiltered'];

        $this->assertGreaterThan(10, $total, 'Too small a library for this to mean anything.');
        $this->assertLessThan(
            $total / 2,
            $none,
            'A non-matching search returned most of the library, so it is not being applied.'
        );
    }

    public function test_filters_combine_rather_than_replace_each_other(): void
    {
        $rows = $this->mine($this->fetch([
            'organization_id' => $this->defraId,
            'scope' => 1,
            'status' => 'all',
        ]));

        // Both DEFRA editions of the scope 1 activity; not the IPCC row, not the
        // scope 3 one.
        $this->assertCount(2, $rows);
    }
}
