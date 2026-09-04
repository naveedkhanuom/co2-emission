<?php

namespace App\Console\Commands;

use App\Models\BoundaryAssessment;
use App\Models\BoundaryItem;
use App\Models\Company;
use App\Models\Department;
use App\Models\EioFactor;
use App\Models\EmissionFactor;
use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\EnergyAttributeCertificate;
use App\Models\Facilities;
use App\Models\ReportingPeriod;
use App\Models\Scope3Category;
use App\Models\Supplier;
use App\Models\Target;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Boundary\BoundaryCoverageService;
use App\Services\EmissionEnrichmentService;
use App\Support\Auditing;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Loads a worked example inventory into one company, for demos and for working
 * on screens that have nothing to show against an empty database.
 *
 * WHAT THIS IS NOT
 *
 * It is not part of provisioning. TenantDatabaseSeeder gives a new client
 * reference data — factors, sources, roles, countries — and deliberately stops
 * there, because everything past that point is the client's own inventory.
 * This command writes exactly the kind of rows that seeder refuses to: a
 * company's facilities, its meter readings, its locked years. So it is a
 * separate, explicitly invoked command that names the tenant it is about to
 * write into, and it refuses to run in production without --force.
 *
 * WHY THE NUMBERS ARE NOT RANDOM
 *
 * Every figure is derived from a reproducible wobble over a published factor,
 * so two runs produce the same inventory and a diff of the tables shows only
 * what actually changed. A demo whose totals move on every reload is useless
 * for checking that a report still adds up.
 *
 * Records are written through EmissionEnrichmentService, the same path the
 * entry screens and the spreadsheet import use, so they carry a locked
 * emission_factor_id, real provenance and a stamped GWP basis — not
 * hand-written co2e values that no verifier ever saw. If the demo data is
 * wrong, it is wrong in the same way real data would be, which is the point.
 *
 * RE-RUNNING
 *
 * Idempotent. Every row it writes is tagged, and a re-run removes its own
 * previous records before writing fresh ones. It never deletes an untagged
 * record, so a demo company that someone has also typed into by hand keeps
 * what they typed.
 */
class SeedDemoData extends Command
{
    /**
     * Stamped into the notes of every emission record this command writes, and
     * into the description of every facility, department, supplier and target.
     *
     * This is the whole of the command's claim on the database: a re-run
     * deletes what carries the tag and nothing else. Change it and the next run
     * will orphan, not replace, everything the previous one wrote.
     */
    public const TAG = '[demo-data]';

    protected $signature = 'demo:seed
        {tenant : The client account to load the demo inventory into, e.g. "acme"}
        {--company= : Company id or exact name inside that account. Defaults to the account owner\'s company}
        {--years=3 : Reporting years to generate, the last of which is the current one}
        {--force : Allow the command to run outside local and testing}';

    protected $description = 'Load a worked example inventory (facilities, periods, emission records) into one company';

    public function handle(): int
    {
        // Demo data is indistinguishable from real data once it is in the
        // table — it is written through the same service, with the same
        // provenance. That is what makes it useful, and it is exactly why it
        // must not appear in a client's production account by accident.
        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->components->error(
                'Refusing to run in the "'.app()->environment().'" environment. This command writes '
                .'invented emission records that are indistinguishable from real ones once saved. '
                .'Pass --force if you genuinely mean to.'
            );

            return self::FAILURE;
        }

        $tenantId = (string) $this->argument('tenant');
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->components->error("No such client account: \"{$tenantId}\". Run `php artisan tenants:list` to see them.");

            return self::FAILURE;
        }

        $years = max(1, (int) $this->option('years'));

        try {
            return $tenant->run(fn () => $this->seedTenant($years));
        } catch (Throwable $e) {
            $this->components->error('Demo seeding failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Everything below here runs inside the tenant's own database.
     */
    protected function seedTenant(int $years): int
    {
        $company = $this->resolveCompany();

        if (! $company) {
            return self::FAILURE;
        }

        // HasCompanyScope reads this to scope reads and to stamp company_id on
        // writes. Without it the command would run unscoped — harmless for a
        // one-company account, wrong the moment the tenant holds several.
        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        $owner = User::where('company_id', $company->id)->orderBy('id')->first()
            ?? User::orderBy('id')->first();

        $currentYear = (int) now()->year;
        $baseYear = $currentYear - ($years - 1);

        $this->components->info("Loading demo inventory into {$company->name} ({$baseYear}–{$currentYear})");

        // Auditing off for the bulk write, on for the governance actions at the
        // end. Several hundred machine-written rows would bury the handful of
        // entries that mean something — see App\Support\Auditing, which exists
        // for exactly this. The locks below are then recorded properly and
        // attributed to the owner, so the change history a demo opens reads
        // like a short human story rather than an import log.
        $counts = Auditing::without(function () use ($company, $owner, $baseYear, $currentYear) {
            $this->describeCompany($company);

            $facilities = $this->createFacilities($company);
            $suppliers = $this->createSuppliers($company);
            $certificate = $this->createCertificate($company, $owner, $currentYear);

            $removed = $this->removePreviousRecords($company);
            if ($removed > 0) {
                $this->components->twoColumnDetail('Previous demo records removed', (string) $removed);
            }

            return $this->createRecords(
                $company, $owner, $facilities, $suppliers, $certificate, $baseYear, $currentYear
            );
        });

        if ($owner) {
            // Everything below this line is a governance act — deciding the
            // boundary, closing a year, setting a target — and the audit trail
            // is where an assurer looks for who did it. Attributing them to a
            // person is the whole reason auditing was left on for this phase.
            auth()->setUser($owner);
        }

        $boundary = $this->createBoundary($company, $owner, $currentYear);
        $periods = $this->createPeriods($company, $owner, $baseYear, $currentYear);
        $targets = $this->createTargets($company, $owner, $baseYear);

        $this->summarise($company, $counts, $boundary, $periods, $targets);

        return self::SUCCESS;
    }

    /**
     * The company the demo belongs to: --company if given, otherwise the
     * account owner's own company, otherwise the first one in the account.
     */
    protected function resolveCompany(): ?Company
    {
        $wanted = $this->option('company');

        if ($wanted !== null && $wanted !== '') {
            $company = is_numeric($wanted)
                ? Company::find((int) $wanted)
                : Company::where('name', $wanted)->first();

            if (! $company) {
                $this->components->error("No company \"{$wanted}\" in this account.");
            }

            return $company;
        }

        $company = User::where('is_account_owner', true)->orderBy('id')->first()?->company
            ?? Company::orderBy('id')->first();

        if (! $company) {
            $this->components->error('This account has no companies to load a demo into.');
        }

        return $company;
    }

    /**
     * Fill in the company profile the reporting screens read from.
     *
     * Only blanks are filled. A demo must not overwrite a name, a country or a
     * scope selection that someone set on purpose — those are the fields most
     * likely to have been configured before anyone thought to load sample data.
     */
    protected function describeCompany(Company $company): void
    {
        $defaults = [
            'industry_type' => 'manufacturing',
            'business_description' => 'Manufacture and regional distribution of precast concrete and steel components.',
            'sub_industry' => 'Building materials',
            'isic_code' => '2395',
            'country' => 'United Arab Emirates',
            'address' => 'Sheikh Zayed Road, Trade Centre 1, Dubai',
            'contact_person' => 'Sustainability Office',
            'size' => 'large',
            'employee_count' => 620,
            'annual_revenue' => 184000000,
            'currency' => 'AED',
            'timezone' => 'Asia/Dubai',
            'fiscal_year_start' => '01-01',
            'reporting_standards' => ['GHG Protocol', 'ISO 14064-1', 'CSRD/ESRS E1'],
            'scopes_enabled' => [1, 2, 3],
        ];

        $fill = [];
        foreach ($defaults as $key => $value) {
            if (blank($company->getAttribute($key))) {
                $fill[$key] = $value;
            }
        }

        if ($fill !== []) {
            $company->fill($fill)->save();
        }
    }

    /**
     * Sites, and the departments inside them.
     *
     * firstOrCreate on the name, because emission records link to a facility by
     * name as well as by id — recreating the row on every run would leave the
     * previous run's records pointing at a facility id that no longer exists.
     *
     * @return array<string, Facilities>
     */
    protected function createFacilities(Company $company): array
    {
        $blueprint = [
            [
                'name' => 'Head Office — Dubai',
                'city' => 'Dubai',
                'address' => 'Sheikh Zayed Road, Trade Centre 1',
                'mrv_enabled' => false,
                'departments' => ['Administration', 'Finance', 'Information Technology'],
            ],
            [
                'name' => 'Jebel Ali Plant',
                'city' => 'Jebel Ali',
                'address' => 'Jebel Ali Industrial Area 1, Plot 42',
                // The MRV layer is opt-in per facility: this is the regulated
                // site, so it is the one that carries a permit number and can
                // produce an EAD facility report.
                'mrv_enabled' => true,
                'economic_licence_number' => 'DED-778241',
                'environmental_permit_no' => 'EAD-2024-00931',
                'parent_entity' => 'Acme Industries Ltd',
                'coordinates' => '25.0110, 55.0610',
                'primary_sector' => 'Manufacturing',
                'primary_activity' => 'Manufacture of cement, lime and precast concrete products',
                'departments' => ['Production', 'Maintenance', 'Utilities'],
            ],
            [
                'name' => 'Al Quoz Distribution Centre',
                'city' => 'Dubai',
                'address' => 'Al Quoz Industrial Area 3, Warehouse 17',
                'mrv_enabled' => false,
                'departments' => ['Logistics', 'Warehouse'],
            ],
        ];

        $facilities = [];

        foreach ($blueprint as $spec) {
            $departments = $spec['departments'];
            unset($spec['departments']);

            $facility = Facilities::firstOrCreate(
                ['company_id' => $company->id, 'name' => $spec['name']],
                $spec + [
                    'country' => 'United Arab Emirates',
                    'description' => self::TAG.' Demo facility.',
                ]
            );

            foreach ($departments as $department) {
                Department::firstOrCreate(
                    ['company_id' => $company->id, 'facility_id' => $facility->id, 'name' => $department],
                    ['description' => self::TAG.' Demo department.']
                );
            }

            $facilities[$facility->name] = $facility;
        }

        return $facilities;
    }

    /**
     * Value-chain counterparties, so the Scope 3 rows point at someone.
     *
     * @return array<string, Supplier>
     */
    protected function createSuppliers(Company $company): array
    {
        $blueprint = [
            ['name' => 'Gulf Steel Trading LLC', 'industry' => 'Metals & metal products', 'data_quality' => 'secondary'],
            ['name' => 'Emirates Freight Services', 'industry' => 'Transportation services', 'data_quality' => 'primary'],
            ['name' => 'Al Bayan Waste Management', 'industry' => 'Waste management', 'data_quality' => 'primary'],
            ['name' => 'Northern Aggregates FZE', 'industry' => 'Quarrying', 'data_quality' => 'estimated'],
        ];

        $suppliers = [];

        foreach ($blueprint as $spec) {
            $suppliers[$spec['name']] = Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => $spec['name']],
                $spec + [
                    'country' => 'United Arab Emirates',
                    'city' => 'Dubai',
                    'status' => 'active',
                    'notes' => self::TAG.' Demo supplier.',
                ]
            );
        }

        return $suppliers;
    }

    /**
     * One renewable certificate covering the head office's electricity for the
     * current year, so the Scope 2 dual-reporting columns have something in
     * them. A location-based figure with no market-based counterpart is a
     * perfectly valid inventory, and also a demo of half a feature.
     */
    protected function createCertificate(Company $company, ?User $owner, int $year): EnergyAttributeCertificate
    {
        return EnergyAttributeCertificate::firstOrCreate(
            ['company_id' => $company->id, 'certificate_number' => 'IREC-AE-'.$year.'-004417'],
            [
                'type' => 'irec',
                'name' => 'I-REC — Head Office supply '.$year,
                'supplier_name' => 'Emirates Green Power',
                'energy_carrier' => 'electricity',
                'mwh_volume' => 1180,
                // Zero: a retired I-REC is claimed as fully renewable, so the
                // market-based figure for the covered volume is nil. That is the
                // whole point of the dual report — the gap between this and the
                // grid-average location-based figure is the claim being made.
                'emission_factor' => 0,
                'region' => 'United Arab Emirates',
                'vintage_year' => $year,
                'valid_from' => Carbon::create($year, 1, 1)->toDateString(),
                'valid_to' => Carbon::create($year, 12, 31)->toDateString(),
                'status' => 'active',
                'created_by' => $owner?->id,
                'notes' => self::TAG.' Demo certificate.',
            ]
        );
    }

    /**
     * Delete this command's own previous output, and only that.
     *
     * A mass delete rather than per-model, so it does not fire several hundred
     * audit deletions for rows whose creation was never audited either.
     */
    protected function removePreviousRecords(Company $company): int
    {
        return EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('notes', 'like', '%'.self::TAG.'%')
            ->delete();
    }

    /**
     * The activity streams the demo company reports against.
     *
     * Each is a real emission source from the seeded catalogue paired with the
     * unit its factor is published per — the pairing matters, because
     * EmissionFigureVerifier holds back any record whose activity unit and
     * factor unit disagree, and a demo that arrived entirely in the review
     * queue would be a demo of the wrong thing.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function streams(): array
    {
        return [
            // ---- Scope 1: direct combustion, fleet and fugitive ----
            [
                'scope' => 1, 'source' => 'Natural Gas Combustion', 'unit' => 'm³',
                'facility' => 'Jebel Ali Plant', 'department' => 'Utilities',
                'base' => 41000, 'season' => 'winter', 'trend' => 0.94,
                'data_source' => 'meter', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Kiln and boiler gas, plant meter read.',
            ],
            [
                'scope' => 1, 'source' => 'Diesel (Stationary Combustion)', 'unit' => 'liters',
                'facility' => 'Jebel Ali Plant', 'department' => 'Maintenance',
                'base' => 3600, 'season' => 'flat', 'trend' => 0.91,
                'data_source' => 'invoice', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Standby generator fuel, delivery notes.',
            ],
            [
                'scope' => 1, 'source' => 'Company Fleet - Diesel', 'unit' => 'liters',
                'facility' => 'Al Quoz Distribution Centre', 'department' => 'Logistics',
                'base' => 8900, 'season' => 'flat', 'trend' => 0.96,
                'data_source' => 'invoice', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Delivery fleet fuel cards.',
            ],
            [
                'scope' => 1, 'source' => 'Company Fleet - Gasoline', 'unit' => 'liters',
                'facility' => 'Head Office — Dubai', 'department' => 'Administration',
                'base' => 1350, 'season' => 'flat', 'trend' => 0.88,
                'data_source' => 'invoice', 'quality' => 'secondary', 'confidence' => 'medium',
                'note' => 'Pool cars, being replaced with EVs.',
            ],
            [
                // Annual rather than monthly: refrigerant loss is established by
                // a service log at year end, not read off a meter every month.
                'scope' => 1, 'source' => 'Refrigerant Leakage (HFCs)', 'unit' => 'kg',
                'facility' => 'Jebel Ali Plant', 'department' => 'Maintenance',
                'base' => 31, 'cadence' => 'annual', 'trend' => 0.9,
                'data_source' => 'estimate', 'quality' => 'estimated', 'confidence' => 'low',
                'note' => 'R-410A top-up recorded against the annual service log.',
            ],

            // ---- Scope 2: purchased electricity, location-based ----
            [
                'scope' => 2, 'source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh',
                'facility' => 'Jebel Ali Plant', 'department' => 'Utilities',
                'base' => 486000, 'season' => 'summer', 'trend' => 0.95,
                'data_source' => 'meter', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Plant supply, monthly utility invoice.',
            ],
            [
                'scope' => 2, 'source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh',
                'facility' => 'Head Office — Dubai', 'department' => 'Administration',
                'base' => 98000, 'season' => 'summer', 'trend' => 0.93,
                'data_source' => 'meter', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Office supply, monthly utility invoice.',
                // Only the head office volume is covered by the I-REC, which is
                // why the market-based column is not simply the whole inventory.
                'certificate' => true,
            ],
            [
                'scope' => 2, 'source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh',
                'facility' => 'Al Quoz Distribution Centre', 'department' => 'Warehouse',
                'base' => 71000, 'season' => 'summer', 'trend' => 0.97,
                'data_source' => 'meter', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Warehouse supply, monthly utility invoice.',
            ],

            // ---- Scope 3: six of the fifteen categories ----
            [
                'scope' => 3, 'category' => '3.3', 'source' => 'S3.3 - WTT Diesel', 'unit' => 'liters',
                'facility' => 'Al Quoz Distribution Centre', 'department' => 'Logistics',
                'base' => 8900, 'season' => 'flat', 'trend' => 0.96,
                'data_source' => 'estimate', 'quality' => 'secondary', 'confidence' => 'medium',
                'note' => 'Well-to-tank on the fleet diesel reported under Scope 1.',
            ],
            [
                'scope' => 3, 'category' => '3.3', 'source' => 'S3.3 - T&D Losses (Electricity)', 'unit' => 'kWh',
                'facility' => 'Jebel Ali Plant', 'department' => 'Utilities',
                'base' => 486000, 'season' => 'summer', 'trend' => 0.95,
                'data_source' => 'estimate', 'quality' => 'secondary', 'confidence' => 'medium',
                'note' => 'Grid transmission and distribution losses on plant supply.',
            ],
            [
                'scope' => 3, 'category' => '3.4', 'source' => 'S3.4 - Freight Road (HGV, average)', 'unit' => 'tonne-km',
                'facility' => 'Al Quoz Distribution Centre', 'department' => 'Logistics',
                'base' => 214000, 'season' => 'flat', 'trend' => 1.02,
                'supplier' => 'Emirates Freight Services',
                'data_source' => 'supplier-survey', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Contracted haulier tonne-kilometres, reported quarterly.',
            ],
            [
                'scope' => 3, 'category' => '3.5', 'source' => 'S3.5 - General Waste to Landfill', 'unit' => 'tonnes',
                'facility' => 'Jebel Ali Plant', 'department' => 'Production',
                'base' => 47, 'season' => 'flat', 'trend' => 0.85,
                'supplier' => 'Al Bayan Waste Management',
                'data_source' => 'supplier-survey', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Weighbridge tickets from the waste contractor.',
            ],
            [
                'scope' => 3, 'category' => '3.6', 'source' => 'Scope 3 - 6. Business Travel', 'unit' => 'km',
                'facility' => 'Head Office — Dubai', 'department' => 'Administration',
                // Travel goes UP while everything else comes down. A demo where
                // every line falls at the same rate tells the viewer nothing
                // about whether the charts respond to the data.
                'base' => 62000, 'season' => 'flat', 'trend' => 1.11,
                'data_source' => 'import', 'quality' => 'secondary', 'confidence' => 'medium',
                'note' => 'Travel management company extract.',
            ],
            [
                'scope' => 3, 'category' => '3.7', 'source' => 'Scope 3 - 7. Employee Commuting', 'unit' => 'km',
                'facility' => 'Head Office — Dubai', 'department' => 'Information Technology',
                'base' => 148000, 'season' => 'flat', 'trend' => 0.97,
                'data_source' => 'estimate', 'quality' => 'estimated', 'confidence' => 'low',
                'note' => 'Modelled from the staff travel survey and headcount.',
            ],
            [
                'scope' => 3, 'category' => '3.1', 'source' => 'S3.1 - Water Supply', 'unit' => 'm³',
                'facility' => 'Jebel Ali Plant', 'department' => 'Utilities',
                'base' => 3900, 'season' => 'summer', 'trend' => 0.94,
                'data_source' => 'meter', 'quality' => 'primary', 'confidence' => 'high',
                'note' => 'Process and washdown water, plant meter.',
            ],
        ];
    }

    /**
     * Spend-based streams, priced from the environmentally-extended input-output
     * table rather than from an activity factor.
     *
     * Kept separate because they are a different calculation: there is no
     * activity quantity and no unit to dimension-check, so
     * EmissionFigureVerifier deliberately skips them. Every real inventory has
     * some of these — the top of Scope 3 is almost always spend — and a demo
     * without any would misrepresent what the Scope 3 screens have to cope with.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function spendStreams(): array
    {
        return [
            [
                'category' => '3.1', 'sector' => 'MANUF',
                'source' => 'Scope 3 - 1. Purchased Goods & Services',
                'facility' => 'Head Office — Dubai', 'department' => 'Finance',
                'supplier' => 'Gulf Steel Trading LLC',
                'base' => 386000, 'trend' => 0.98, 'currency' => 'USD',
                'note' => 'Steel and cement purchases, priced from the spend ledger.',
            ],
            [
                'category' => '3.2', 'sector' => 'CONST',
                'source' => 'Scope 3 - 2. Capital Goods',
                'facility' => 'Jebel Ali Plant', 'department' => 'Production',
                'supplier' => 'Northern Aggregates FZE',
                'base' => 74000, 'trend' => 1.04, 'currency' => 'USD',
                'note' => 'Plant and machinery capital spend.',
            ],
        ];
    }

    /**
     * Write the inventory.
     *
     * @param  array<string, Facilities>  $facilities
     * @param  array<string, Supplier>  $suppliers
     * @return array{records: int, drafts: int, skipped: array<int, string>}
     */
    protected function createRecords(
        Company $company,
        ?User $owner,
        array $facilities,
        array $suppliers,
        EnergyAttributeCertificate $certificate,
        int $baseYear,
        int $currentYear
    ): array {
        $enricher = app(EmissionEnrichmentService::class);
        $categories = Scope3Category::pluck('id', 'code');
        $departments = Department::where('company_id', $company->id)->get();

        $written = 0;
        $drafts = 0;
        $skipped = [];

        foreach ($this->streams() as $stream) {
            $factor = $this->factorFor($stream['source'], $stream['unit']);

            if (! $factor) {
                $skipped[] = $stream['source'].' ('.$stream['unit'].')';

                continue;
            }

            $facility = $facilities[$stream['facility']];
            $department = $departments->first(fn ($d) => $d->facility_id === $facility->id
                && $d->name === $stream['department']);
            $perUnit = $factor->valueInTonnes();

            foreach (range($baseYear, $currentYear) as $year) {
                $months = $this->monthsFor($year, $currentYear, ($stream['cadence'] ?? 'monthly') === 'annual');

                foreach ($months as $month) {
                    $activity = $this->activity($stream, $year, $baseYear, $month);

                    // The record is held back in the open year's most recent
                    // month, the way a real one would be: the figures are in,
                    // nobody has signed them off yet. It gives the review queue
                    // and the "awaiting review" counters something to show.
                    $pending = $year === $currentYear && $month === max($months);

                    $data = [
                        'company_id' => $company->id,
                        'entry_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                        'facility' => $facility->name,
                        'facility_id' => $facility->id,
                        'department' => $department?->name,
                        'department_id' => $department?->id,
                        'scope' => $stream['scope'],
                        'scope3_category_id' => isset($stream['category'])
                            ? ($categories[$stream['category']] ?? null)
                            : null,
                        'supplier_id' => isset($stream['supplier'])
                            ? $suppliers[$stream['supplier']]->id
                            : null,
                        'emission_source' => $stream['source'],
                        'activity_data' => $activity,
                        'activity_unit' => $stream['unit'],
                        'emission_factor' => $perUnit,
                        'co2e_value' => round($activity * $perUnit, 4),
                        'calculation_method' => 'activity-based',
                        'data_quality' => $stream['quality'],
                        'data_source' => $stream['data_source'],
                        'confidence_level' => $stream['confidence'],
                        'status' => $pending ? 'draft' : 'active',
                        'created_by' => $owner?->id,
                        'notes' => self::TAG.' '.$stream['note'],
                    ];

                    // A closed year arrives already signed off, because that is
                    // what locking a year means — the approvals happened before
                    // the period was closed, not after.
                    if ($year < $currentYear) {
                        $data['approved_by'] = $owner?->id;
                        $data['approved_at'] = Carbon::create($year + 1, 1, 31)->toDateTimeString();
                    }

                    $context = ['emission_factor_id' => $factor->id];

                    if (! empty($stream['certificate']) && $year === $currentYear) {
                        $context['energy_attribute_certificate_id'] = $certificate->id;
                    }

                    EmissionRecord::create($enricher->enrich($data, $context));

                    $written++;
                    $pending && $drafts++;
                }
            }
        }

        foreach ($this->spendStreams() as $stream) {
            $eio = EioFactor::where('sector_code', $stream['sector'])->where('is_active', true)->first();

            if (! $eio) {
                $skipped[] = $stream['source'].' (spend, '.$stream['sector'].')';

                continue;
            }

            $facility = $facilities[$stream['facility']];
            $department = $departments->first(fn ($d) => $d->facility_id === $facility->id
                && $d->name === $stream['department']);

            // eio_factors publish kg CO2e per currency unit; records are stored
            // in tonnes, the same basis EmissionFactor::valueInTonnes() returns.
            $perCurrencyUnit = (float) $eio->emission_factor / 1000;

            foreach (range($baseYear, $currentYear) as $year) {
                foreach ($this->monthsFor($year, $currentYear, false) as $month) {
                    $spend = $this->activity($stream, $year, $baseYear, $month);

                    $data = [
                        'company_id' => $company->id,
                        'entry_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                        'facility' => $facility->name,
                        'facility_id' => $facility->id,
                        'department' => $department?->name,
                        'department_id' => $department?->id,
                        'scope' => 3,
                        'scope3_category_id' => $categories[$stream['category']] ?? null,
                        'supplier_id' => $suppliers[$stream['supplier']]->id,
                        'emission_source' => $stream['source'],
                        'spend_amount' => $spend,
                        'spend_currency' => $stream['currency'],
                        'emission_factor' => $perCurrencyUnit,
                        'co2e_value' => round($spend * $perCurrencyUnit, 4),
                        'calculation_method' => 'spend-based',
                        'data_quality' => 'secondary',
                        'data_source' => 'import',
                        'confidence_level' => 'low',
                        'status' => 'active',
                        'created_by' => $owner?->id,
                        'notes' => self::TAG.' '.$stream['note'],
                    ];

                    if ($year < $currentYear) {
                        $data['approved_by'] = $owner?->id;
                        $data['approved_at'] = Carbon::create($year + 1, 1, 31)->toDateTimeString();
                    }

                    EmissionRecord::create($enricher->enrich($data));
                    $written++;
                }
            }
        }

        return ['records' => $written, 'drafts' => $drafts, 'skipped' => $skipped];
    }

    /**
     * Which months of a year the demo has data for.
     *
     * The current year stops at the last COMPLETE month. A demo that reports a
     * full month of electricity on the third of September is a demo that says
     * out loud the numbers are made up.
     *
     * @return array<int, int>
     */
    protected function monthsFor(int $year, int $currentYear, bool $annual): array
    {
        if ($annual) {
            // An annual figure belongs to a year that has finished. The open
            // year has no December to book it against yet.
            return $year < $currentYear ? [12] : [];
        }

        return range(1, $year < $currentYear ? 12 : max(1, (int) now()->month - 1));
    }

    /**
     * One month's activity: a base rate, moved by the year's trend, the season,
     * and a small reproducible wobble.
     *
     * @param  array<string, mixed>  $stream
     */
    protected function activity(array $stream, int $year, int $baseYear, int $month): float
    {
        $trend = ($stream['trend'] ?? 0.95) ** ($year - $baseYear);
        $seasonal = $this->seasonality($stream['season'] ?? 'flat')[$month - 1];
        $wobble = $this->wobble($stream['source'].'|'.$stream['facility'].'|'.$year.'|'.$month);

        return round($stream['base'] * $trend * $seasonal * $wobble, 2);
    }

    /**
     * Month-by-month multipliers. Deliberately Gulf-shaped: cooling load peaks
     * in July and August, gas for process heat does the opposite.
     *
     * @return array<int, float>
     */
    protected function seasonality(string $profile): array
    {
        return match ($profile) {
            'summer' => [0.82, 0.84, 0.92, 1.02, 1.14, 1.22, 1.28, 1.26, 1.13, 0.99, 0.88, 0.82],
            'winter' => [1.18, 1.14, 1.03, 0.93, 0.84, 0.78, 0.76, 0.78, 0.87, 0.97, 1.09, 1.16],
            default => array_fill(0, 12, 1.0),
        };
    }

    /**
     * A deterministic wobble in [0.94, 1.06], so a series looks measured rather
     * than calculated — but lands on the same numbers every run.
     *
     * Derived from a hash rather than mt_rand() on purpose: a re-run has to
     * reproduce the inventory exactly, or every diff of the demo database is
     * noise and no report can be checked against the last one.
     */
    protected function wobble(string $key): float
    {
        return 0.94 + (crc32($key) % 1201) / 10000;
    }

    /**
     * The catalogue row a stream is priced from.
     *
     * Matched on the source name AND the unit. A name alone carries rows for
     * several units — litres and gallons, kWh and MMBtu — and picking the wrong
     * one produces a figure that verifies arithmetically and means nothing.
     */
    protected function factorFor(string $source, string $unit): ?EmissionFactor
    {
        static $cache = [];

        $key = $source.'|'.$unit;

        return $cache[$key] ??= EmissionFactor::with('organization')
            ->whereHas('emissionSource', fn ($q) => $q->where('name', $source))
            ->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower(trim($unit))])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * The boundary the company says it is measuring against.
     *
     * Without one, coverage is 0 of 0 and Data Health can only offer generic
     * advice — which makes the demo silent about the question the product
     * actually answers, "am I measuring the things I said I would". So the
     * assessment is built to be partly unmet on purpose:
     *
     *   - most included items are covered by the records above,
     *   - a few are included and have no data, so the gap list is populated,
     *   - the eight irrelevant Scope 3 categories are screened and excluded
     *     WITH a written reason, because an exclusion without one is the thing
     *     an assurer challenges.
     *
     * A demo boundary at 100% coverage would be a demo of a company with
     * nothing left to do, which is not a company that needs this software.
     */
    protected function createBoundary(Company $company, ?User $owner, int $year): array
    {
        $assessment = BoundaryAssessment::firstOrCreate(
            ['company_id' => $company->id, 'reporting_year' => $year, 'version' => 1],
            [
                'status' => 'active',
                'generator' => 'template',
                'confidence' => 0.78,
                'generated_at' => now(),
                'completed_by' => $owner?->id,
                'completed_at' => now(),
                'summary' => self::TAG.' Screened against the building-materials template: '
                    .'three sites, one regulated, all fifteen Scope 3 categories assessed.',
                'profile_snapshot' => [
                    'industry_type' => $company->industry_type,
                    'sub_industry' => $company->sub_industry,
                    'employee_count' => $company->employee_count,
                    'country' => $company->country,
                ],
            ]
        );

        $sources = EmissionSource::pluck('id', 'name');
        $categories = Scope3Category::pluck('id', 'code');

        foreach ($this->boundaryItems() as $spec) {
            BoundaryItem::firstOrCreate(
                [
                    'boundary_assessment_id' => $assessment->id,
                    'suggested_name' => $spec['name'],
                ],
                [
                    'company_id' => $company->id,
                    'scope' => $spec['scope'],
                    'scope3_category_id' => isset($spec['category'])
                        ? ($categories[$spec['category']] ?? null)
                        : null,
                    'emission_source_id' => isset($spec['source'])
                        ? ($sources[$spec['source']] ?? null)
                        : null,
                    'suggested_unit' => $spec['unit'] ?? null,
                    'materiality' => $spec['materiality'] ?? 'medium',
                    'relevance' => $spec['decision'] === 'excluded' ? 'not_relevant' : 'relevant',
                    'decision' => $spec['decision'],
                    'exclusion_reason' => $spec['reason'] ?? null,
                    'rationale' => $spec['rationale'] ?? null,
                    'source' => 'template',
                    'confidence' => 0.8,
                    'accepted_by' => $owner?->id,
                    'accepted_at' => now(),
                ]
            );
        }

        return app(BoundaryCoverageService::class)->forCompany($company->fresh(), $year);
    }

    /**
     * The checklist itself.
     *
     * Scope 1 and 2 items name a catalogue emission source, because that is
     * what BoundaryCoverageService matches records against for those scopes —
     * there is no emission_source_id on emission_records, so coverage is
     * decided on the source NAME. Scope 3 items carry a category id instead,
     * which is exact.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function boundaryItems(): array
    {
        return [
            // ---- Scope 1 ----
            ['scope' => 1, 'name' => 'Kiln and boiler natural gas', 'source' => 'Natural Gas Combustion', 'unit' => 'm³', 'materiality' => 'high', 'decision' => 'included'],
            ['scope' => 1, 'name' => 'Standby generator diesel', 'source' => 'Diesel (Stationary Combustion)', 'unit' => 'liters', 'materiality' => 'medium', 'decision' => 'included'],
            ['scope' => 1, 'name' => 'Delivery fleet diesel', 'source' => 'Company Fleet - Diesel', 'unit' => 'liters', 'materiality' => 'high', 'decision' => 'included'],
            ['scope' => 1, 'name' => 'Pool car petrol', 'source' => 'Company Fleet - Gasoline', 'unit' => 'liters', 'materiality' => 'low', 'decision' => 'included'],
            ['scope' => 1, 'name' => 'Refrigerant top-up', 'source' => 'Refrigerant Leakage (HFCs)', 'unit' => 'kg', 'materiality' => 'medium', 'decision' => 'included'],
            [
                // Deliberately uncovered: agreed in the boundary, never entered.
                // This is the row that makes the coverage figure mean something.
                'scope' => 1, 'name' => 'Forklift LPG', 'source' => 'LPG / Propane Combustion', 'unit' => 'liters',
                'materiality' => 'low', 'decision' => 'included',
                'rationale' => 'Warehouse forklifts run on bottled LPG; cylinder counts are held by the site team.',
            ],

            // ---- Scope 2 ----
            ['scope' => 2, 'name' => 'Purchased electricity', 'source' => 'Purchased Electricity (Location-based)', 'unit' => 'kWh', 'materiality' => 'high', 'decision' => 'included'],
            [
                'scope' => 2, 'name' => 'Purchased process steam', 'source' => 'Purchased Steam', 'unit' => 'MJ',
                'materiality' => 'medium', 'decision' => 'included',
                'rationale' => 'Steam is bought from the neighbouring plant; metering was installed mid-year.',
            ],

            // ---- Scope 3: all fifteen categories screened ----
            ['scope' => 3, 'category' => '3.1', 'name' => 'Purchased goods, services and water', 'materiality' => 'high', 'decision' => 'included'],
            ['scope' => 3, 'category' => '3.2', 'name' => 'Capital goods', 'materiality' => 'medium', 'decision' => 'included'],
            ['scope' => 3, 'category' => '3.3', 'name' => 'Fuel- and energy-related activities', 'materiality' => 'medium', 'decision' => 'included'],
            ['scope' => 3, 'category' => '3.4', 'name' => 'Upstream transport and distribution', 'materiality' => 'high', 'decision' => 'included'],
            ['scope' => 3, 'category' => '3.5', 'name' => 'Waste generated in operations', 'materiality' => 'medium', 'decision' => 'included'],
            ['scope' => 3, 'category' => '3.6', 'name' => 'Business travel', 'materiality' => 'low', 'decision' => 'included'],
            ['scope' => 3, 'category' => '3.7', 'name' => 'Employee commuting', 'materiality' => 'low', 'decision' => 'included'],
            [
                'scope' => 3, 'category' => '3.8', 'name' => 'Upstream leased assets',
                'materiality' => 'low', 'decision' => 'excluded',
                'reason' => 'All three sites are owned freehold; no assets are leased in during the reporting period.',
            ],
            [
                'scope' => 3, 'category' => '3.9', 'name' => 'Downstream transport and distribution',
                'materiality' => 'medium', 'decision' => 'included',
                'rationale' => 'Customer-arranged collections from Al Quoz; haulier data not yet requested.',
            ],
            [
                'scope' => 3, 'category' => '3.10', 'name' => 'Processing of sold products',
                'materiality' => 'low', 'decision' => 'excluded',
                'reason' => 'Precast units are installed as supplied and undergo no further processing.',
            ],
            [
                'scope' => 3, 'category' => '3.11', 'name' => 'Use of sold products',
                'materiality' => 'low', 'decision' => 'excluded',
                'reason' => 'Products are inert in use and consume no energy over their service life.',
            ],
            [
                'scope' => 3, 'category' => '3.12', 'name' => 'End-of-life treatment of sold products',
                'materiality' => 'medium', 'decision' => 'included',
                'rationale' => 'Demolition and recycling rates to be estimated from sector data next cycle.',
            ],
            [
                'scope' => 3, 'category' => '3.13', 'name' => 'Downstream leased assets',
                'materiality' => 'low', 'decision' => 'excluded',
                'reason' => 'No assets are leased to third parties.',
            ],
            [
                'scope' => 3, 'category' => '3.14', 'name' => 'Franchises',
                'materiality' => 'low', 'decision' => 'excluded',
                'reason' => 'The business operates no franchises.',
            ],
            [
                'scope' => 3, 'category' => '3.15', 'name' => 'Investments',
                'materiality' => 'low', 'decision' => 'excluded',
                'reason' => 'No equity, debt or project finance holdings.',
            ],
        ];
    }

    /**
     * A reporting period per year: the earliest is the base year, every year
     * before the current one is locked.
     *
     * Locking happens here, after the records exist, and with auditing live —
     * because closing a year is a governance act and the audit trail is where
     * an assurer looks for it. It is also the honest order: you cannot lock a
     * year before its data has been entered.
     *
     * @return array<int, ReportingPeriod>
     */
    protected function createPeriods(Company $company, ?User $owner, int $baseYear, int $currentYear): array
    {
        $periods = [];

        foreach (range($baseYear, $currentYear) as $year) {
            $period = ReportingPeriod::firstOrCreate(
                ['company_id' => $company->id, 'year' => $year],
                [
                    'status' => 'open',
                    'is_base_year' => $year === $baseYear,
                    'note' => self::TAG.' Demo reporting period.',
                ]
            );

            if ($year < $currentYear && ! $period->isLocked()) {
                $period->update([
                    'status' => 'locked',
                    'locked_at' => Carbon::create($year + 1, 3, 31)->toDateTimeString(),
                    'locked_by' => $owner?->id,
                ]);
            }

            $periods[] = $period;
        }

        return $periods;
    }

    /**
     * A near-term reduction target and a long-term one, both measured against
     * the base year's actual total rather than a round number, so the progress
     * charts have something real to track against.
     *
     * @return array<int, Target>
     */
    protected function createTargets(Company $company, ?User $owner, int $baseYear): array
    {
        $baseline = round((float) EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereYear('entry_date', $baseYear)
            ->sum('co2e_value'), 2);

        $blueprint = [
            [
                'name' => 'Near-term: 42% absolute reduction by 2030',
                'type' => 'sbt',
                'scope' => '1,2',
                'target_year' => 2030,
                'reduction_percent' => 42,
                'strategy' => 'Electrify the pool fleet, recover kiln waste heat, contract renewable supply.',
                'review_frequency' => 'quarterly',
                'status' => 'on-track',
            ],
            [
                'name' => 'Net zero across all scopes by 2050',
                'type' => 'net-zero',
                'scope' => 'all',
                'target_year' => 2050,
                'reduction_percent' => 90,
                'strategy' => 'Value-chain engagement first, residual removals last.',
                'review_frequency' => 'annual',
                'status' => 'at-risk',
            ],
        ];

        $targets = [];

        foreach ($blueprint as $spec) {
            $targets[] = Target::firstOrCreate(
                ['company_id' => $company->id, 'name' => $spec['name']],
                $spec + [
                    'baseline_year' => $baseYear,
                    'baseline_emissions' => $baseline,
                    'target_emissions' => round($baseline * (1 - $spec['reduction_percent'] / 100), 2),
                    'responsible_person' => 'Sustainability Office',
                    'created_by' => $owner?->id,
                    'description' => self::TAG.' Demo target.',
                ]
            );
        }

        return $targets;
    }

    /**
     * @param  array{records: int, drafts: int, skipped: array<int, string>}  $counts
     * @param  array{total: int, covered: int, percent: float}  $boundary
     * @param  array<int, ReportingPeriod>  $periods
     * @param  array<int, Target>  $targets
     */
    protected function summarise(Company $company, array $counts, array $boundary, array $periods, array $targets): void
    {
        foreach ($counts['skipped'] as $missing) {
            $this->components->warn("No catalogue factor for {$missing} — that stream was skipped.");
        }

        $total = round((float) EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('notes', 'like', '%'.self::TAG.'%')
            ->sum('co2e_value'), 2);

        $locked = collect($periods)->filter->isLocked()->pluck('year')->implode(', ');

        $this->newLine();
        $this->components->twoColumnDetail('<fg=cyan>Company</>', $company->name.' (#'.$company->id.')');
        $this->components->twoColumnDetail('Emission records written', (string) $counts['records']);
        $this->components->twoColumnDetail('…awaiting review', (string) $counts['drafts']);
        $this->components->twoColumnDetail('Total demo inventory', number_format($total, 2).' tCO2e');
        $this->components->twoColumnDetail(
            'Boundary coverage',
            $boundary['covered'].' of '.$boundary['total'].' included items ('.$boundary['percent'].'%)'
        );
        $this->components->twoColumnDetail('Reporting periods', count($periods).' ('.($locked ?: 'none').' locked)');
        $this->components->twoColumnDetail('Targets', (string) count($targets));
        $this->newLine();
        $this->components->info('Re-run this command to rebuild the demo; it replaces only its own rows.');
    }
}
