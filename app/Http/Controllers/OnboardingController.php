<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * First-run setup wizard for a company.
 *
 * The platform's users are ordinary company staff (facility, finance, ops) who
 * are NOT carbon-accounting experts. This wizard asks only about their business
 * in plain language and configures the carbon side (scopes, industry factor
 * templates) for them, so they never face a blank, jargon-filled screen.
 *
 * Progress is stored in the existing `company_settings` table (no schema
 * change) via Company::setSetting().
 *
 * WHERE THIS SITS IN THE SEQUENCE
 *
 * This wizard settles the ORGANISATIONAL boundary — which entities count as
 * yours (`consolidation_approach`). It does not settle the OPERATIONAL one —
 * which sources and Scope 3 categories are in the inventory — and under the GHG
 * Protocol both are required before a figure means anything.
 *
 * The operational half is the Boundary Advisor at /boundary, and until this
 * handoff existed nothing led a new client to it: they finished setup, landed
 * on an empty dashboard, and had to find the boundary in the sidebar on their
 * own. So `save()` ends by sending them there, having collected
 * `business_description` on the way — the one field the Advisor requires and
 * this wizard did not ask for. Arriving with it already filled in is the
 * difference between the Advisor's first screen being a form and being a
 * confirmation.
 */
class OnboardingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * The `onboarding_stage` setting's one meaningful value: this company has
     * finished the wizard and been handed to the Boundary Advisor, which has
     * not yet been activated.
     *
     * A stage rather than a boolean because the sequence is expected to grow —
     * the next link in the chain is "boundary done, gaps not yet closed" — and
     * because "which step are they on" is the question the dashboard will want
     * to ask. BoundaryController::activate() clears it.
     */
    public const STAGE_BOUNDARY = 'boundary';

    /**
     * Plain-language business activities → the GHG scope they belong to.
     * The user only sees the friendly label; the scope is inferred for them.
     */
    private const ACTIVITY_SCOPES = [
        'electricity' => 2, // We use electricity
        'onsite_fuel' => 1, // We burn fuel on-site (gas boilers, diesel generators, furnaces)
        'vehicles' => 1, // We own/operate vehicles
        'refrigerants' => 1, // We use refrigeration or air-conditioning
        'business_travel' => 3, // Our staff travel for work (flights, hotels, taxis)
        'employee_commute' => 3, // Our staff commute to work
        'waste' => 3, // We produce waste
        'purchased_goods' => 3, // We buy goods, materials or services
        'freight' => 3, // We ship or receive goods
    ];

    /**
     * Resolve the company the signed-in user belongs to (null for a
     * account owner with no single company selected).
     */
    private function resolveCompany(): ?Company
    {
        $user = Auth::user();
        if ($user && $user->company_id) {
            return $user->company;
        }

        return current_company();
    }

    /**
     * A company still needs onboarding when it has not finished the wizard AND
     * has no data yet. The data check protects existing tenants from ever being
     * pushed back through setup.
     */
    public function needsOnboarding(?Company $company): bool
    {
        if (! $company) {
            return false;
        }

        if ($company->getSetting('onboarding_completed', false)) {
            return false;
        }

        $siteCount = Site::withoutGlobalScope('company')
            ->where('company_id', $company->id)->count();
        if ($siteCount > 0) {
            return false;
        }

        $recordCount = EmissionRecord::withoutGlobalScope('company')
            ->where('company_id', $company->id)->count();

        return $recordCount === 0;
    }

    /**
     * Show the wizard.
     */
    public function index()
    {
        $company = $this->resolveCompany();

        // Account owners (no single company) have nothing to set up here.
        if (! $company) {
            return redirect()->route('home');
        }

        // Already set up — don't make them repeat it.
        if (! $this->needsOnboarding($company)) {
            return redirect()->route('home');
        }

        $industries = $this->industryOptions();
        $activities = $this->activityOptions();
        $boundaries = $this->boundaryOptions();
        $gwpOptions = $this->gwpOptions();

        // Sensible defaults for the "reporting basis" step.
        $defaultBaseYear = (int) $company->getSetting('base_year', now()->year);
        $defaultBoundary = $company->getSetting('consolidation_approach', config('boundary.default'));
        $defaultGwp = $company->getSetting('gwp_version', config('gwp.default', 'ar6'));
        $currentYear = now()->year;

        return view('onboarding.index', compact(
            'company', 'industries', 'activities', 'boundaries', 'gwpOptions',
            'defaultBaseYear', 'defaultBoundary', 'defaultGwp', 'currentYear'
        ));
    }

    /**
     * Persist the wizard answers, configure the company, and create its sites.
     */
    public function save(Request $request)
    {
        $company = $this->resolveCompany();

        if (! $company) {
            return response()->json(['success' => false, 'message' => 'No company to set up.'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry_type' => 'required|in:'.implode(',', array_keys($this->industryOptions())),
            // Optional here, but bounded by what the Boundary Advisor accepts
            // (BoundaryController::start()): anything this wizard stores has to
            // be usable there without being re-typed, and a two-word answer is
            // not enough for the interview to produce a useful boundary.
            'business_description' => 'nullable|string|min:10|max:1000',
            'country' => 'nullable|string|max:255',
            'employee_count' => 'nullable|integer|min:0',
            'fiscal_year_start' => 'nullable|string|max:10',
            'sites' => 'required|array|min:1',
            'sites.*.name' => 'required|string|max:255',
            'sites.*.location' => 'nullable|string|max:255',
            'activities' => 'required|array|min:1',
            'activities.*' => 'in:'.implode(',', array_keys(self::ACTIVITY_SCOPES)),
            'base_year' => 'nullable|integer|min:2000|max:'.(now()->year + 1),
            'consolidation_approach' => 'nullable|in:'.implode(',', array_keys($this->boundaryOptions())),
            'gwp_version' => 'nullable|in:'.implode(',', array_keys($this->gwpOptions())),
        ], [
            'sites.required' => 'Please add at least one location.',
            'sites.*.name.required' => 'Each location needs a name.',
            'activities.required' => 'Please pick at least one thing your business does.',
        ]);

        // Which scopes apply, inferred from the plain-language activities.
        $scopes = collect($validated['activities'])
            ->map(fn ($a) => self::ACTIVITY_SCOPES[$a])
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Update the company profile.
        $company->update([
            'name' => $validated['name'],
            'industry_type' => $validated['industry_type'],
            // Coalesce rather than overwrite: a client who skipped the field
            // should not lose a description they already had.
            'business_description' => $validated['business_description'] ?? $company->business_description,
            'country' => $validated['country'] ?? $company->country,
            'employee_count' => $validated['employee_count'] ?? $company->employee_count,
            'size' => $this->sizeFromEmployees($validated['employee_count'] ?? null) ?? $company->size,
            'fiscal_year_start' => $validated['fiscal_year_start'] ?? $company->fiscal_year_start,
            'scopes_enabled' => $scopes,
        ]);

        // Create the sites (skip any that already exist by name for safety).
        $existing = Site::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->all();

        foreach ($validated['sites'] as $site) {
            if (in_array(mb_strtolower(trim($site['name'])), $existing, true)) {
                continue;
            }
            Site::create([
                'company_id' => $company->id,
                'name' => $site['name'],
                'location' => $site['location'] ?? null,
            ]);
        }

        // Reporting basis — stored as settings (no schema change) and consumed by
        // the disclosure report (boundary), Gwp::versionForCompany (gwp_version),
        // and future targets / year-over-year comparison (base_year).
        $baseYear = (int) ($validated['base_year'] ?? now()->year);

        $company->setSetting('base_year', $baseYear, 'integer');
        $company->setSetting('consolidation_approach', $validated['consolidation_approach'] ?? config('boundary.default'), 'string');
        $company->setSetting('gwp_version', $validated['gwp_version'] ?? config('gwp.default', 'ar6'), 'string');

        $this->recordBaseYearPeriod($company, $baseYear);

        // Remember what they told us, and mark setup complete.
        $company->setSetting('onboarding_activities', $validated['activities'], 'json');
        $company->setSetting('onboarding_completed', true, 'boolean');

        // Setup is done; the boundary is not. BoundaryController reads this to
        // greet them as a continuation rather than a cold screen, and clears it
        // on activation. See STAGE_BOUNDARY.
        $company->setSetting('onboarding_stage', self::STAGE_BOUNDARY, 'string');

        return response()->json([
            'success' => true,
            'message' => 'Your account is set up. Now let’s scope what you need to measure.',
            'redirect' => route('boundary.index'),
        ]);
    }

    /**
     * Make the chosen base year a real reporting period, not only a setting.
     *
     * The wizard has always written the `base_year` company setting, but
     * ReportingPeriod::baseYearFor() reads the `reporting_periods` TABLE — so
     * until a row existed it answered null however carefully the client filled
     * the wizard in, and the year-over-year and target screens behaved as
     * though no base year had ever been chosen. The row was only created if
     * someone later happened to visit /reporting-periods and set it again.
     *
     * ReportingPeriodController::setBaseYear() writes both directions; this is
     * the other door into the same state, so it does the same thing.
     *
     * The scope is bypassed and company_id matched explicitly, the way
     * ReportingPeriod's own static helpers do it — this runs during first-run
     * setup, where the company context is the thing still being established.
     */
    private function recordBaseYearPeriod(Company $company, int $year): void
    {
        // Only one base year per company.
        ReportingPeriod::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->update(['is_base_year' => false]);

        $period = ReportingPeriod::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('year', $year)
            ->first();

        // Never reopen a locked year: a client can re-run setup, and a signed
        // off inventory is not something the wizard gets to unfreeze.
        if ($period) {
            $period->update(['is_base_year' => true]);

            return;
        }

        ReportingPeriod::create([
            'company_id' => $company->id,
            'year' => $year,
            'status' => 'open',
            'is_base_year' => true,
        ]);
    }

    /**
     * Let a user skip setup (still marks it done so they aren't nagged).
     */
    public function skip()
    {
        $company = $this->resolveCompany();
        if ($company) {
            $company->setSetting('onboarding_completed', true, 'boolean');
        }

        return redirect()->route('home');
    }

    /**
     * Industry enum values (must match the companies table) → friendly labels.
     */
    private function industryOptions(): array
    {
        return [
            'manufacturing' => 'Manufacturing',
            'energy' => 'Energy & Utilities',
            'transportation' => 'Transportation & Logistics',
            'agriculture' => 'Agriculture',
            'construction' => 'Construction',
            'retail' => 'Retail & Wholesale',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'technology' => 'Technology & IT',
            'finance' => 'Finance & Insurance',
            'hospitality' => 'Hospitality & Tourism',
            'mining' => 'Mining',
            'chemical' => 'Chemicals',
            'textile' => 'Textiles',
            'food_beverage' => 'Food & Beverage',
            'other' => 'Something else',
        ];
    }

    /**
     * Plain-language activity options for the UI (label + helper text).
     */
    private function activityOptions(): array
    {
        return [
            'electricity' => ['icon' => 'fa-bolt',          'label' => 'We use electricity',                'help' => 'Offices, factories, shops — anywhere on a power bill.'],
            'onsite_fuel' => ['icon' => 'fa-fire',          'label' => 'We burn fuel on-site',              'help' => 'Gas boilers, diesel generators, furnaces, heating.'],
            'vehicles' => ['icon' => 'fa-truck',         'label' => 'We own or operate vehicles',        'help' => 'Company cars, vans, trucks, forklifts.'],
            'refrigerants' => ['icon' => 'fa-snowflake',     'label' => 'We use refrigeration or A/C',       'help' => 'Air-conditioning, cold storage, chillers.'],
            'business_travel' => ['icon' => 'fa-plane',         'label' => 'Our staff travel for work',         'help' => 'Flights, hotels, taxis, rental cars.'],
            'employee_commute' => ['icon' => 'fa-person-walking', 'label' => 'Our staff commute to work',         'help' => 'How employees get to and from the workplace.'],
            'waste' => ['icon' => 'fa-trash',         'label' => 'We produce waste',                  'help' => 'General waste, recycling, wastewater.'],
            'purchased_goods' => ['icon' => 'fa-box',           'label' => 'We buy goods, materials or services', 'help' => 'Raw materials, supplies, outsourced services.'],
            'freight' => ['icon' => 'fa-dolly',         'label' => 'We ship or receive goods',          'help' => 'Inbound and outbound transport of products.'],
        ];
    }

    /**
     * Organizational boundary options (machine key => friendly label), sourced
     * from config so the disclosure report agrees on the same labels.
     */
    private function boundaryOptions(): array
    {
        return config('boundary.labels', ['operational_control' => 'Operational control']);
    }

    /**
     * Reporting standard (GWP set) options (key => human label), newest first.
     * These feed Gwp::versionForCompany via the `gwp_version` setting.
     */
    private function gwpOptions(): array
    {
        $labels = config('gwp.labels', []);

        return collect(\App\Support\Gwp::VERSIONS)
            ->mapWithKeys(fn ($v) => [$v => $labels[$v] ?? strtoupper($v)])
            ->all();
    }

    /**
     * Map an employee headcount to the company "size" enum.
     */
    private function sizeFromEmployees(?int $count): ?string
    {
        if ($count === null) {
            return null;
        }

        return match (true) {
            $count < 50 => 'small',
            $count < 250 => 'medium',
            $count < 1000 => 'large',
            default => 'enterprise',
        };
    }
}
