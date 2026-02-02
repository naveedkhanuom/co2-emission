<?php

namespace App\Http\Controllers;

use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\EmissionFactor;
use App\Models\FactorOrganization;
use App\Models\Company;
use App\Models\Site;
use App\Models\Country;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmissionRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-emission-records|create-emission-record|edit-emission-record|delete-emission-record', ['only' => ['index', 'scopeEntry', 'getData', 'show']]);
        $this->middleware('permission:create-emission-record', ['only' => ['store', 'storeOrUpdate']]);
        $this->middleware('permission:edit-emission-record', ['only' => ['update']]);
        $this->middleware('permission:delete-emission-record', ['only' => ['destroy']]);
    }

    /**
     * Store uploaded supporting documents and return stored paths.
     *
     * @return array<int, string>
     */
    private function storeSupportingDocuments(Request $request, int|string|null $companyId): array
    {
        if (!$request->hasFile('supporting_documents')) {
            return [];
        }

        $files = $request->file('supporting_documents');
        if (!is_array($files)) {
            $files = [$files];
        }

        $stored = [];
        $folder = 'supporting-documents/' . ($companyId ?: 'unknown') . '/' . now()->format('Y/m');

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }
            $stored[] = $file->storePublicly($folder, 'public');
        }

        return $stored;
    }

    /**
     * Get current company ID from context.
     */
    protected function getCurrentCompanyId()
    {
        if (app()->bound('current_company_id')) {
            return app('current_company_id');
        }
        
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }
        
        return null;
    }
    public function index()
    {
        // Load emission sources grouped by scope for dynamic dropdowns
        $scope1Sources = EmissionSource::where(function ($query) {
            $query->where('scope', 1)->orWhereNull('scope');
        })->with('emissionFactors')->orderBy('name')->get();

        $scope2Sources = EmissionSource::where(function ($query) {
            $query->where('scope', 2)->orWhereNull('scope');
        })->with('emissionFactors')->orderBy('name')->get();

        $scope3Sources = EmissionSource::where(function ($query) {
            $query->where('scope', 3)->orWhereNull('scope');
        })->with('emissionFactors')->orderBy('name')->get();

        $factorOrganizations = FactorOrganization::orderBy('name')->get();
        $defaultOrganizationId = FactorOrganization::where('code', 'IPCC')->value('id') ?: $factorOrganizations->first()?->id;
        $countrySpecificOrgId = FactorOrganization::where('code', 'COUNTRY')->value('id');

        // Company country (used to prefer country-specific factors when available)
        $company = $this->getCurrentCompanyId() ? Company::find($this->getCurrentCompanyId()) : null;
        $companyCountryDisplay = $company?->country ?? null;
        $companyCountryCode = function_exists('country_to_factor_region')
            ? country_to_factor_region($companyCountryDisplay)
            : 'default';

        // Countries list for country-specific factors (Settings → Countries)
        $countries = Country::where('is_active', true)->orderBy('name')->get();

        // Build emission factors map for JavaScript:
        //   [sourceName][orgId] => [ { region, unit, factor }, ... ]
        // Front-end will pick: country-specific (if selected) -> default -> first
        $emissionFactorsMap = [];
        $allSources = EmissionSource::with(['emissionFactors' => function ($q) {
            $q->orderByRaw("CASE WHEN COALESCE(region,'default') = 'default' THEN 1 ELSE 0 END")
              ->orderBy('id', 'desc');
        }, 'emissionFactors.country'])->get();

        foreach ($allSources as $source) {
            foreach ($source->emissionFactors as $factor) {
                $orgId = $factor->organization_id ?: 0;
                $key = (string) $orgId;
                if (!isset($emissionFactorsMap[$source->name][$key])) {
                    $emissionFactorsMap[$source->name][$key] = [];
                }
                $emissionFactorsMap[$source->name][$key][] = [
                    // Prefer Countries module code when set, else fallback to legacy region string
                    'region' => $factor->country?->code ?? ($factor->region ?? 'default'),
                    'unit' => $factor->unit,
                    'factor' => (float) $factor->factor_value,
                ];
            }
        }

        $allEmissionSourceNames = collect()
            ->merge($scope1Sources->pluck('name'))
            ->merge($scope2Sources->pluck('name'))
            ->merge($scope3Sources->pluck('name'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return view('emission_records.index', [
            'scope1Sources' => $scope1Sources,
            'scope2Sources' => $scope2Sources,
            'scope3Sources' => $scope3Sources,
            'emissionFactorsMap' => $emissionFactorsMap,
            'factorOrganizations' => $factorOrganizations,
            'defaultOrganizationId' => $defaultOrganizationId,
            'allEmissionSourceNames' => $allEmissionSourceNames,
            'emissionSourceNamesByScope' => [
                1 => $scope1Sources->pluck('name')->values()->toArray(),
                2 => $scope2Sources->pluck('name')->values()->toArray(),
                3 => $scope3Sources->pluck('name')->values()->toArray(),
            ],
            'companyCountryCode' => $companyCountryCode,
            'companyCountryDisplay' => $companyCountryDisplay,
            'countries' => $countries,
            'countrySpecificOrgId' => $countrySpecificOrgId,
        ]);
    }

    public function scopeEntry()
    {
        // Load emission sources grouped by scope
        // Get sources specific to each scope, plus any sources without a scope (general sources)
        $scope1Sources = EmissionSource::where(function($query) {
            $query->where('scope', 1)->orWhereNull('scope');
        })->with('emissionFactors')->orderBy('name')->get();
        
        $scope2Sources = EmissionSource::where(function($query) {
            $query->where('scope', 2)->orWhereNull('scope');
        })->with('emissionFactors')->orderBy('name')->get();
        
        $scope3Sources = EmissionSource::where(function($query) {
            $query->where('scope', 3)->orWhereNull('scope');
        })->with('emissionFactors')->orderBy('name')->get();
        
        $factorOrganizations = FactorOrganization::orderBy('name')->get();
        $defaultOrganizationId = FactorOrganization::where('code', 'IPCC')->value('id') ?: $factorOrganizations->first()?->id;

        // Build emission factors map for JavaScript: [sourceName][orgId] => { unit, factor }
        $emissionFactorsMap = [];
        $allSources = EmissionSource::with(['emissionFactors' => function ($q) {
            $q->orderBy('id', 'desc');
        }])->get();
        foreach ($allSources as $source) {
            foreach ($source->emissionFactors as $factor) {
                $orgId = $factor->organization_id ?: 0;
                $emissionFactorsMap[$source->name][(string) $orgId] = [
                    'unit' => $factor->unit,
                    'factor' => (float) $factor->factor_value,
                ];
            }
        }
        
        return view('scope_entry.index', [
            'scope1Sources' => $scope1Sources,
            'scope2Sources' => $scope2Sources,
            'scope3Sources' => $scope3Sources,
            'emissionFactorsMap' => $emissionFactorsMap,
            'factorOrganizations' => $factorOrganizations,
            'defaultOrganizationId' => $defaultOrganizationId,
        ]);
    }

    public function getData()
    {
        $records = EmissionRecord::with(['company', 'site', 'user']);

        return DataTables::of($records)
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-info viewBtn" data-id="'.$row->id.'">View</button>
                    <button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->editColumn('co2e_value', fn ($row) => number_format($row->co2e_value, 4))
            ->make(true);
    }


    public function store(Request $request)
    {
        // Get current company ID
        $companyId = $this->getCurrentCompanyId();

        // Determine status
        $status = $request->input('status', 'active');

        // If multiple entries are sent
        if ($request->has('entries')) {

            $entries = $request->input('entries');

            // Validate each entry
            foreach ($entries as $index => $data) {
                $validator = Validator::make($data, [
                    'entryDate'             => 'required|date',
                    'facilitySelect'        => 'required|string|max:50',
                    'siteSelect'            => 'nullable|exists:sites,id',
                    'scopeSelect'           => 'required|in:1,2,3',
                    'emissionSourceSelect'  => 'required|string|max:100',
                    'emission_source_other' => 'required_if:emissionSourceSelect,__other__|nullable|string|max:255',
                    'co2eValue'             => 'required|numeric|min:0',
                    'confidenceLevel'       => 'required|in:low,medium,high,estimated',
                    'departmentSelect'      => 'nullable|string|max:100',
                    'dataSource'            => 'required|in:manual,import,api',
                    'entryNotes'            => 'nullable|string|max:1000',
                    // Scope 3 specific fields
                    'scope3_category_id'    => 'nullable|exists:scope3_categories,id',
                    'supplier_id'           => 'nullable|exists:suppliers,id',
                    'calculation_method'    => 'nullable|in:activity-based,spend-based,hybrid',
                    'data_quality'          => 'nullable|in:primary,secondary,estimated',
                    'spend_amount'          => 'nullable|numeric|min:0',
                    'spend_currency'        => 'nullable|string|size:3',
                    'factor_organization_id'=> 'nullable|exists:factor_organizations,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'errors' => ['row' => $index + 1, 'messages' => $validator->errors()]
                    ], 422);
                }
            }

            // Validate sites and suppliers belong to current company if provided
            foreach ($entries as $index => $data) {
                if (!empty($data['siteSelect'])) {
                    $site = Site::find($data['siteSelect']);
                    if (!$site || $site->company_id != $companyId) {
                        return response()->json([
                            'status' => false,
                            'errors' => ['row' => $index + 1, 'messages' => ['siteSelect' => ['The selected site does not belong to your company.']]]
                        ], 422);
                    }
                }
                
                if (!empty($data['supplier_id'])) {
                    $supplier = \App\Models\Supplier::find($data['supplier_id']);
                    if (!$supplier || $supplier->company_id != $companyId) {
                        return response()->json([
                            'status' => false,
                            'errors' => ['row' => $index + 1, 'messages' => ['supplier_id' => ['The selected supplier does not belong to your company.']]]
                        ], 422);
                    }
                }
            }

            // Save all entries
            foreach ($entries as $data) {
                $emissionSourceName = ($data['emissionSourceSelect'] ?? '') === '__other__'
                    ? trim($data['emission_source_other'] ?? '')
                    : ($data['emissionSourceSelect'] ?? '');
                if ($emissionSourceName !== '') {
                    EmissionSource::firstOrCreate(
                        ['name' => $emissionSourceName],
                        ['scope' => (int) ($data['scopeSelect'] ?? 1), 'description' => 'Added from emission record']
                    );
                }

                $entryData = [
                    'company_id'        => $companyId,
                    'entry_date'        => $data['entryDate'],
                    'facility'          => $data['facilitySelect'],
                    'site_id'           => !empty($data['siteSelect']) ? $data['siteSelect'] : null,
                    'scope'             => $data['scopeSelect'],
                    'emission_source'   => $emissionSourceName,
                    'co2e_value'        => $data['co2eValue'],
                    'factor_organization_id' => $data['factor_organization_id'] ?? null,
                    'confidence_level'  => $data['confidenceLevel'] ?? 'medium',
                    'department'        => $data['departmentSelect'] ?? null,
                    'data_source'       => $data['dataSource'] ?? 'manual',
                    'notes'             => $data['entryNotes'] ?? null,
                    'created_by'        => auth()->id(),
                    'status'            => $status,
                ];
                
                // Add Scope 3 fields if scope is 3
                if ($data['scopeSelect'] == 3) {
                    $entryData['scope3_category_id'] = $data['scope3_category_id'] ?? null;
                    $entryData['supplier_id'] = $data['supplier_id'] ?? null;
                    $entryData['calculation_method'] = $data['calculation_method'] ?? 'activity-based';
                    $entryData['data_quality'] = $data['data_quality'] ?? 'estimated';
                    $entryData['spend_amount'] = $data['spend_amount'] ?? null;
                    $entryData['spend_currency'] = $data['spend_currency'] ?? 'USD';
                }
                
                EmissionRecord::create($entryData);
            }

            return response()->json([
                'status' => true,
                'message' => count($entries) . ' records saved successfully'
            ]);
        }

        // Single entry validation
        $validator = Validator::make($request->all(), [
            'entryDate'             => 'required|date',
            'facilitySelect'        => 'required|string|max:50',
            'siteSelect'            => 'nullable|exists:sites,id',
            'scopeSelect'           => 'required|in:1,2,3',
            'emissionSourceSelect'  => 'required|string|max:100',
            'emission_source_other' => 'required_if:emissionSourceSelect,__other__|nullable|string|max:255',
            'activityData'          => 'required|numeric|min:0',
            'emissionFactor'        => 'required|numeric|min:0',
            'co2eValue'             => 'required|numeric|min:0',
            'factor_organization_id'=> 'nullable|exists:factor_organizations,id',
            'confidenceLevel'       => 'required|in:low,medium,high,estimated',
            'departmentSelect'      => 'nullable|string|max:100',
            'dataSource'            => 'required|in:manual,import,api,meter,invoice,estimate',
            'entryNotes'            => 'nullable|string|max:1000',
            // Scope 3 specific fields
            'scope3_category_id'    => 'nullable|exists:scope3_categories,id',
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'calculation_method'    => 'nullable|in:activity-based,spend-based,hybrid',
            'data_quality'          => 'nullable|in:primary,secondary,estimated',
            'spend_amount'          => 'nullable|numeric|min:0',
            'spend_currency'        => 'nullable|string|size:3',
            'sector_code'           => 'nullable|string|max:50',
            'country'               => 'nullable|string|max:3',
            // Supporting documents
            'supporting_documents'      => 'nullable|array',
            'supporting_documents.*'    => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get current company ID
        $companyId = $this->getCurrentCompanyId();

        // Validate site belongs to current company if provided
        if ($request->siteSelect) {
            $site = Site::find($request->siteSelect);
            if (!$site || $site->company_id != $companyId) {
                return response()->json([
                    'status' => false,
                    'errors' => ['siteSelect' => ['The selected site does not belong to your company.']]
                ], 422);
            }
        }

        // Resolve emission source name (allow "Other" with free-text)
        $emissionSourceName = ($request->emissionSourceSelect ?? '') === '__other__'
            ? trim($request->emission_source_other ?? '')
            : ($request->emissionSourceSelect ?? '');
        if ($emissionSourceName !== '') {
            EmissionSource::firstOrCreate(
                ['name' => $emissionSourceName],
                ['scope' => (int) ($request->scopeSelect ?? 1), 'description' => 'Added from emission record']
            );
        }

        // Prepare data array
        $data = [
            'company_id'        => $companyId,
            'entry_date'        => $request->entryDate,
            'facility'          => $request->facilitySelect,
            'site_id'           => $request->siteSelect ?: null,
            'scope'             => $request->scopeSelect,
            'emission_source'   => $emissionSourceName,
            'activity_data'     => $request->activityData,
            'emission_factor'   => $request->emissionFactor,
            'factor_organization_id' => $request->factor_organization_id,
            'co2e_value'        => $request->co2eValue,
            'confidence_level'  => $request->confidenceLevel,
            'department'        => $request->departmentSelect ?: null,
            'data_source'       => $request->dataSource,
            'notes'             => $request->entryNotes,
            'created_by'        => auth()->id(),
            'status'            => $status,
        ];
        
        // Add Scope 3 fields if scope is 3
        if ($request->scopeSelect == 3) {
            $data['scope3_category_id'] = $request->scope3_category_id ?? null;
            $data['supplier_id'] = $request->supplier_id ?? null;
            $data['calculation_method'] = $request->calculation_method ?? 'activity-based';
            $data['data_quality'] = $request->data_quality ?? 'estimated';
            $data['spend_amount'] = $request->spend_amount ?? null;
            $data['spend_currency'] = $request->spend_currency ?? 'USD';
            
            // If spend-based, calculate emissions if not provided
            if ($data['calculation_method'] === 'spend-based' && $data['spend_amount'] && !$request->co2eValue) {
                // Try to get EIO factor and calculate
                if ($request->sector_code) {
                    $emissions = \App\Models\EioFactor::calculateFromSpend(
                        $data['spend_amount'],
                        $request->sector_code,
                        $request->country ?? 'USA',
                        $data['spend_currency']
                    );
                    if ($emissions) {
                        // Convert from kg to tonnes if needed (EIO factors typically return kg)
                        $data['co2e_value'] = $emissions / 1000; // Convert kg to tonnes
                    }
                }
            }
        }
        
        // Validate supplier belongs to current company if provided
        if ($request->supplier_id) {
            $supplier = \App\Models\Supplier::find($request->supplier_id);
            if (!$supplier || $supplier->company_id != $companyId) {
                return response()->json([
                    'status' => false,
                    'errors' => ['supplier_id' => ['The selected supplier does not belong to your company.']]
                ], 422);
            }
        }
        
        // Save documents (if any)
        $storedDocs = $this->storeSupportingDocuments($request, $companyId);
        if (!empty($storedDocs)) {
            $data['supporting_documents'] = $storedDocs;
        }

        // Save single entry
        EmissionRecord::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Emission record saved successfully'
        ]);
    }



    public function show(EmissionRecord $emissionRecord)
    {
        return response()->json(
            $emissionRecord->load(['company', 'site', 'user'])
        );
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                    => 'nullable|exists:emission_records,id',
            'entryDate'             => 'required|date',
            'facilitySelect'        => 'required|string|max:50',
            'siteSelect'            => 'nullable|exists:sites,id',
            'scopeSelect'           => 'required|in:1,2,3',
            'emissionSourceSelect'  => 'required|string|max:100',
            'activityData'          => 'required|numeric|min:0',
            'emissionFactor'        => 'required|numeric|min:0',
            'co2eValue'             => 'required|numeric|min:0',
            'factor_organization_id'=> 'nullable|exists:factor_organizations,id',
            'confidenceLevel'       => 'required|in:low,medium,high,estimated',
            'departmentSelect'      => 'nullable|string|max:100',
            'dataSource'            => 'required|in:manual,import,api,meter,invoice,estimate',
            'entryNotes'            => 'nullable|string|max:1000',
            'status'                => 'nullable|in:active,draft',
            // Scope 3 specific fields
            'scope3_category_id'    => 'nullable|exists:scope3_categories,id',
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'calculation_method'    => 'nullable|in:activity-based,spend-based,hybrid',
            'data_quality'          => 'nullable|in:primary,secondary,estimated',
            'spend_amount'          => 'nullable|numeric|min:0',
            'spend_currency'        => 'nullable|string|size:3',
            'sector_code'           => 'nullable|string|max:50',
            'country'               => 'nullable|string|max:3',
            // Supporting documents
            'supporting_documents'      => 'nullable|array',
            'supporting_documents.*'    => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'entry_date'        => $request->entryDate,
            'facility'          => $request->facilitySelect,
            'site_id'           => $request->siteSelect ?: null,
            'scope'             => $request->scopeSelect,
            'emission_source'   => $request->emissionSourceSelect,
            'activity_data'     => $request->activityData,
            'emission_factor'   => $request->emissionFactor,
            'factor_organization_id' => $request->factor_organization_id,
            'co2e_value'        => $request->co2eValue,
            'confidence_level'  => $request->confidenceLevel,
            'department'        => $request->departmentSelect ?: null,
            'data_source'       => $request->dataSource,
            'notes'             => $request->entryNotes,
            'status'            => $request->status ?? 'active',
        ];

        // Add Scope 3 fields if scope is 3
        if ($request->scopeSelect == 3) {
            $data['scope3_category_id'] = $request->scope3_category_id ?? null;
            $data['supplier_id'] = $request->supplier_id ?? null;
            $data['calculation_method'] = $request->calculation_method ?? 'activity-based';
            $data['data_quality'] = $request->data_quality ?? 'estimated';
            $data['spend_amount'] = $request->spend_amount ?? null;
            $data['spend_currency'] = $request->spend_currency ?? 'USD';
            
            // If spend-based, calculate emissions if not provided
            if ($data['calculation_method'] === 'spend-based' && $data['spend_amount'] && !$request->co2eValue) {
                // Try to get EIO factor and calculate
                if ($request->sector_code) {
                    $emissions = \App\Models\EioFactor::calculateFromSpend(
                        $data['spend_amount'],
                        $request->sector_code,
                        $request->country ?? 'USA',
                        $data['spend_currency']
                    );
                    if ($emissions) {
                        // EIO factors typically return kg CO2e, convert to tonnes
                        $data['co2e_value'] = $emissions / 1000;
                    }
                }
            }
        }

        // Get current company ID
        $companyId = $this->getCurrentCompanyId();

        // Validate site belongs to current company if provided
        if ($request->siteSelect) {
            $site = Site::find($request->siteSelect);
            if (!$site || $site->company_id != $companyId) {
                return response()->json([
                    'status' => false,
                    'errors' => ['siteSelect' => ['The selected site does not belong to your company.']]
                ], 422);
            }
        }
        
        // Validate supplier belongs to current company if provided
        if ($request->supplier_id) {
            $supplier = \App\Models\Supplier::find($request->supplier_id);
            if (!$supplier || $supplier->company_id != $companyId) {
                return response()->json([
                    'status' => false,
                    'errors' => ['supplier_id' => ['The selected supplier does not belong to your company.']]
                ], 422);
            }
        }

        if ($request->has('id') && $request->id) {
            // Update existing record
            $record = EmissionRecord::findOrFail($request->id);
            
            // Ensure record belongs to current company
            if ($record->company_id != $companyId) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this record'
                ], 403);
            }
            
            // Store new documents (append)
            $storedDocs = $this->storeSupportingDocuments($request, $companyId);
            if (!empty($storedDocs)) {
                $existing = $record->supporting_documents ?? [];
                if (!is_array($existing)) {
                    $existing = [];
                }
                $data['supporting_documents'] = array_values(array_unique(array_merge($existing, $storedDocs)));
            }

            $record->update($data);
            
            return response()->json([
                'status'  => true,
                'message' => 'Emission record updated successfully',
                'data'    => $record
            ]);
        } else {
            // Create new record
            $data['company_id'] = $companyId;
            $data['created_by'] = auth()->id();

            $storedDocs = $this->storeSupportingDocuments($request, $companyId);
            if (!empty($storedDocs)) {
                $data['supporting_documents'] = $storedDocs;
            }

            $record = EmissionRecord::create($data);
            
            return response()->json([
                'status'  => true,
                'message' => 'Emission record created successfully',
                'data'    => $record
            ]);
        }
    }

    public function update(Request $request, EmissionRecord $emissionRecord)
    {
        $validator = Validator::make($request->all(), [
            'entryDate'             => 'required|date',
            'facilitySelect'        => 'required|string|max:50',
            'siteSelect'            => 'nullable|exists:sites,id',
            'scopeSelect'           => 'required|in:1,2,3',
            'emissionSourceSelect'  => 'required|string|max:100',
            'activityData'          => 'required|numeric|min:0',
            'emissionFactor'        => 'required|numeric|min:0',
            'co2eValue'             => 'required|numeric|min:0',
            'factor_organization_id'=> 'nullable|exists:factor_organizations,id',
            'confidenceLevel'       => 'required|in:low,medium,high,estimated',
            'departmentSelect'      => 'nullable|string|max:100',
            'dataSource'            => 'required|in:manual,import,api,meter,invoice,estimate',
            'entryNotes'            => 'nullable|string|max:1000',
            'status'                => 'nullable|in:active,draft',
            // Scope 3 specific fields
            'scope3_category_id'    => 'nullable|exists:scope3_categories,id',
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'calculation_method'    => 'nullable|in:activity-based,spend-based,hybrid',
            'data_quality'          => 'nullable|in:primary,secondary,estimated',
            'spend_amount'          => 'nullable|numeric|min:0',
            'spend_currency'        => 'nullable|string|size:3',
            // Supporting documents
            'supporting_documents'      => 'nullable|array',
            'supporting_documents.*'    => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get current company ID for validation
        $companyId = $this->getCurrentCompanyId();
        
        // Validate supplier belongs to current company if provided
        if ($request->supplier_id) {
            $supplier = \App\Models\Supplier::find($request->supplier_id);
            if (!$supplier || $supplier->company_id != $companyId) {
                return response()->json([
                    'status' => false,
                    'errors' => ['supplier_id' => ['The selected supplier does not belong to your company.']]
                ], 422);
            }
        }

        $data = [
            'entry_date'        => $request->entryDate,
            'facility'          => $request->facilitySelect,
            'site_id'           => $request->siteSelect ?: null,
            'scope'             => $request->scopeSelect,
            'emission_source'   => $request->emissionSourceSelect,
            'activity_data'     => $request->activityData,
            'emission_factor'   => $request->emissionFactor,
            'factor_organization_id' => $request->factor_organization_id,
            'co2e_value'        => $request->co2eValue,
            'confidence_level'  => $request->confidenceLevel,
            'department'        => $request->departmentSelect ?: null,
            'data_source'       => $request->dataSource,
            'notes'             => $request->entryNotes,
            'status'            => $request->status ?? $emissionRecord->status,
        ];
        
        // Add Scope 3 fields if scope is 3
        if ($request->scopeSelect == 3) {
            $data['scope3_category_id'] = $request->scope3_category_id ?? null;
            $data['supplier_id'] = $request->supplier_id ?? null;
            $data['calculation_method'] = $request->calculation_method ?? 'activity-based';
            $data['data_quality'] = $request->data_quality ?? 'estimated';
            $data['spend_amount'] = $request->spend_amount ?? null;
            $data['spend_currency'] = $request->spend_currency ?? 'USD';
        } else {
            // Clear Scope 3 fields if scope is not 3
            $data['scope3_category_id'] = null;
            $data['supplier_id'] = null;
            $data['calculation_method'] = null;
            $data['data_quality'] = null;
            $data['spend_amount'] = null;
            $data['spend_currency'] = 'USD';
        }

        // Store new documents (append)
        $companyId = $this->getCurrentCompanyId();
        $storedDocs = $this->storeSupportingDocuments($request, $companyId);
        if (!empty($storedDocs)) {
            $existing = $emissionRecord->supporting_documents ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }
            $data['supporting_documents'] = array_values(array_unique(array_merge($existing, $storedDocs)));
        }

        $emissionRecord->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Emission record updated successfully',
            'data'    => $emissionRecord->fresh()
        ]);
    }

    public function destroy(EmissionRecord $emissionRecord)
    {
        $emissionRecord->delete();

        return response()->json([
            'message' => 'Emission record deleted successfully'
        ]);
    }
}
