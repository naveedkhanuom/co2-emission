<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmissionRecord;
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
 */
class OnboardingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Plain-language business activities → the GHG scope they belong to.
     * The user only sees the friendly label; the scope is inferred for them.
     */
    private const ACTIVITY_SCOPES = [
        'electricity'      => 2, // We use electricity
        'onsite_fuel'      => 1, // We burn fuel on-site (gas boilers, diesel generators, furnaces)
        'vehicles'         => 1, // We own/operate vehicles
        'refrigerants'     => 1, // We use refrigeration or air-conditioning
        'business_travel'  => 3, // Our staff travel for work (flights, hotels, taxis)
        'employee_commute' => 3, // Our staff commute to work
        'waste'            => 3, // We produce waste
        'purchased_goods'  => 3, // We buy goods, materials or services
        'freight'          => 3, // We ship or receive goods
    ];

    /**
     * Resolve the company the signed-in user belongs to (null for a
     * super-admin with no single company selected).
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
        if (!$company) {
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

        // Super-admins (no single company) have nothing to set up here.
        if (!$company) {
            return redirect()->route('home');
        }

        // Already set up — don't make them repeat it.
        if (!$this->needsOnboarding($company)) {
            return redirect()->route('home');
        }

        $industries = $this->industryOptions();
        $activities = $this->activityOptions();

        return view('onboarding.index', compact('company', 'industries', 'activities'));
    }

    /**
     * Persist the wizard answers, configure the company, and create its sites.
     */
    public function save(Request $request)
    {
        $company = $this->resolveCompany();

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'No company to set up.'], 422);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'industry_type'     => 'required|in:' . implode(',', array_keys($this->industryOptions())),
            'country'           => 'nullable|string|max:255',
            'employee_count'    => 'nullable|integer|min:0',
            'fiscal_year_start' => 'nullable|string|max:10',
            'sites'             => 'required|array|min:1',
            'sites.*.name'      => 'required|string|max:255',
            'sites.*.location'  => 'nullable|string|max:255',
            'activities'        => 'required|array|min:1',
            'activities.*'      => 'in:' . implode(',', array_keys(self::ACTIVITY_SCOPES)),
        ], [
            'sites.required'      => 'Please add at least one location.',
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
            'name'              => $validated['name'],
            'industry_type'     => $validated['industry_type'],
            'country'           => $validated['country'] ?? $company->country,
            'employee_count'    => $validated['employee_count'] ?? $company->employee_count,
            'size'              => $this->sizeFromEmployees($validated['employee_count'] ?? null) ?? $company->size,
            'fiscal_year_start' => $validated['fiscal_year_start'] ?? $company->fiscal_year_start,
            'scopes_enabled'    => $scopes,
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
                'name'       => $site['name'],
                'location'   => $site['location'] ?? null,
            ]);
        }

        // Remember what they told us, and mark setup complete.
        $company->setSetting('onboarding_activities', $validated['activities'], 'json');
        $company->setSetting('onboarding_completed', true, 'boolean');

        return response()->json([
            'success'  => true,
            'message'  => 'Your account is set up. Welcome aboard!',
            'redirect' => route('home'),
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
            'manufacturing'  => 'Manufacturing',
            'energy'         => 'Energy & Utilities',
            'transportation' => 'Transportation & Logistics',
            'agriculture'    => 'Agriculture',
            'construction'   => 'Construction',
            'retail'         => 'Retail & Wholesale',
            'healthcare'     => 'Healthcare',
            'education'      => 'Education',
            'technology'     => 'Technology & IT',
            'finance'        => 'Finance & Insurance',
            'hospitality'    => 'Hospitality & Tourism',
            'mining'         => 'Mining',
            'chemical'       => 'Chemicals',
            'textile'        => 'Textiles',
            'food_beverage'  => 'Food & Beverage',
            'other'          => 'Something else',
        ];
    }

    /**
     * Plain-language activity options for the UI (label + helper text).
     */
    private function activityOptions(): array
    {
        return [
            'electricity'      => ['icon' => 'fa-bolt',          'label' => 'We use electricity',                'help' => 'Offices, factories, shops — anywhere on a power bill.'],
            'onsite_fuel'      => ['icon' => 'fa-fire',          'label' => 'We burn fuel on-site',              'help' => 'Gas boilers, diesel generators, furnaces, heating.'],
            'vehicles'         => ['icon' => 'fa-truck',         'label' => 'We own or operate vehicles',        'help' => 'Company cars, vans, trucks, forklifts.'],
            'refrigerants'     => ['icon' => 'fa-snowflake',     'label' => 'We use refrigeration or A/C',       'help' => 'Air-conditioning, cold storage, chillers.'],
            'business_travel'  => ['icon' => 'fa-plane',         'label' => 'Our staff travel for work',         'help' => 'Flights, hotels, taxis, rental cars.'],
            'employee_commute' => ['icon' => 'fa-person-walking','label' => 'Our staff commute to work',         'help' => 'How employees get to and from the workplace.'],
            'waste'            => ['icon' => 'fa-trash',         'label' => 'We produce waste',                  'help' => 'General waste, recycling, wastewater.'],
            'purchased_goods'  => ['icon' => 'fa-box',           'label' => 'We buy goods, materials or services','help' => 'Raw materials, supplies, outsourced services.'],
            'freight'          => ['icon' => 'fa-dolly',         'label' => 'We ship or receive goods',          'help' => 'Inbound and outbound transport of products.'],
        ];
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
            $count < 50   => 'small',
            $count < 250  => 'medium',
            $count < 1000 => 'large',
            default       => 'enterprise',
        };
    }
}
