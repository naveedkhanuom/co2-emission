<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillOCRController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySwitcherController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DataQualityController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DisclosureReportController;
use App\Http\Controllers\EioFactorController;
use App\Http\Controllers\EmissionFactorController;
use App\Http\Controllers\EmissionImportController;
use App\Http\Controllers\EmissionRecordController;
use App\Http\Controllers\EmissionSourceController;
use App\Http\Controllers\EnergyAttributeCertificateController;
use App\Http\Controllers\FacilitiesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MrvReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewDataController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Scope1EntryController;
use App\Http\Controllers\Scope2EntryController;
use App\Http\Controllers\Scope3Controller;
use App\Http\Controllers\Scope3EntryController;
use App\Http\Controllers\ScopeClassifierController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierSurveyController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UtilityBillController;
use Illuminate\Support\Facades\Route;

// Public self-registration is disabled: this is a multi-tenant B2B app and a
// self-registered user would have no company (see HasCompanyScope). Provision
// users via the admin User management screens instead.
Auth::routes(['register' => false]);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Data Health — completeness / "what to do next" overview
    Route::get('/data-health', [App\Http\Controllers\DataHealthController::class, 'index'])->name('data_health.index');

    // General (global, app-wide) settings — app name & logo
    Route::get('/settings/general', [App\Http\Controllers\GeneralSettingController::class, 'edit'])->name('settings.general');
    Route::post('/settings/general', [App\Http\Controllers\GeneralSettingController::class, 'update'])->name('settings.general.update');

    // Base-year comparison report (uses the base year set on Reporting Periods)
    Route::get('/base-year-comparison', [App\Http\Controllers\BaseYearComparisonController::class, 'index'])->name('base_year_comparison.index');

    // First-run company setup wizard (plain-language onboarding for non-experts)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [App\Http\Controllers\OnboardingController::class, 'index'])->name('index');
        Route::post('/save', [App\Http\Controllers\OnboardingController::class, 'save'])->name('save');
        Route::post('/skip', [App\Http\Controllers\OnboardingController::class, 'skip'])->name('skip');
    });

    // Audit trail (read-only change history)
    Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');

    // Unit conversion helper (used by entry forms to normalise activity units)
    Route::get('/units', [App\Http\Controllers\UnitConversionController::class, 'units'])->name('units.list');
    Route::post('/units/convert', [App\Http\Controllers\UnitConversionController::class, 'convert'])->name('units.convert');

    // Analytics & Insights
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/breakdown', [AnalyticsController::class, 'breakdown'])->name('breakdown');
        Route::get('/intensity', [AnalyticsController::class, 'intensity'])->name('intensity');
        Route::get('/year-over-year', [AnalyticsController::class, 'yearOverYear'])->name('yoy');
        Route::get('/hotspots', [AnalyticsController::class, 'hotspots'])->name('hotspots');
    });

    // Roles & Permissions (Spatie)
    Route::resource('roles', RoleController::class);

    // Users - data route must come before resource route
    Route::get('users/data', [UserController::class, 'getData'])->name('users.data');
    Route::resource('users', UserController::class);
});

// Company Switcher Routes
Route::middleware(['auth'])->group(function () {
    Route::post('company/switch', [CompanySwitcherController::class, 'switch'])->name('company.switch');
    Route::get('company/accessible', [CompanySwitcherController::class, 'getAccessibleCompanies'])->name('company.accessible');
});

Route::middleware(['auth'])->group(function () {
    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('companies/{id}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('departments', DepartmentController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/facilities', [FacilitiesController::class, 'index'])->name('facilities.index');
    Route::post('/facilities', [FacilitiesController::class, 'store'])->name('facilities.store');
    Route::put('/facilities/{facility}', [FacilitiesController::class, 'update'])->name('facilities.update');
    Route::delete('/facilities/{facility}', [FacilitiesController::class, 'destroy'])->name('facilities.destroy');
});

Route::prefix('sites')->middleware('auth')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/getSites', [SiteController::class, 'getSites'])->name('sites.getSites');
    Route::post('/storeOrUpdate', [SiteController::class, 'storeOrUpdate'])->name('sites.storeOrUpdate');
    Route::get('/{id}', [SiteController::class, 'show']);
    Route::delete('/{id}', [SiteController::class, 'destroy']);
});

Route::prefix('emission-sources')->name('emission_sources.')->middleware('auth')->group(function () {
    Route::get('/', [EmissionSourceController::class, 'index'])->name('index');
    Route::get('/data', [EmissionSourceController::class, 'getData'])->name('data');
    Route::get('/{id}', [EmissionSourceController::class, 'show']);
    Route::post('/store-or-update', [EmissionSourceController::class, 'storeOrUpdate'])->name('storeOrUpdate');
    Route::delete('/{id}', [EmissionSourceController::class, 'destroy']);
});

Route::prefix('emission-factors')->name('emission_factors.')->group(function () {
    Route::get('/', [EmissionFactorController::class, 'index'])->name('index');
    Route::get('/data', [EmissionFactorController::class, 'getData'])->name('data');
    Route::get('/{id}', [EmissionFactorController::class, 'show']);
    Route::post('/store-or-update', [EmissionFactorController::class, 'storeOrUpdate'])->name('storeOrUpdate');
    Route::delete('/{id}', [EmissionFactorController::class, 'destroy']);
});

// Countries (Settings)
Route::prefix('countries')->name('countries.')->middleware('auth')->group(function () {
    Route::get('/', [CountryController::class, 'index'])->name('index');
    Route::get('/data', [CountryController::class, 'getData'])->name('data');
    Route::get('/{id}', [CountryController::class, 'show']);
    Route::post('/store-or-update', [CountryController::class, 'storeOrUpdate'])->name('storeOrUpdate');
    Route::delete('/{id}', [CountryController::class, 'destroy']);
});

Route::prefix('emission-records')->middleware('auth')->group(function () {
    Route::get('/', [EmissionRecordController::class, 'index'])->name('emission_records.index');
    Route::get('/scope-entry', [EmissionRecordController::class, 'scopeEntry'])->name('emission_records.scope_entry');
    Route::post('/quick-add', [EmissionRecordController::class, 'quickAdd'])->name('emission_records.quick_add');
    Route::get('/data', [EmissionRecordController::class, 'getData'])->name('emission_records.data');
    Route::post('/store', [EmissionRecordController::class, 'store'])->name('emission-records.store');
    Route::post('/store-or-update', [EmissionRecordController::class, 'storeOrUpdate'])->name('emission_records.storeOrUpdate');
    Route::get('/{emissionRecord}/document/{index}', [EmissionRecordController::class, 'downloadDocument'])->name('emission_records.document');
    Route::put('/{emissionRecord}', [EmissionRecordController::class, 'update'])->name('emission_records.update');
    Route::get('/{emissionRecord}', [EmissionRecordController::class, 'show']);
    Route::delete('/{emissionRecord}', [EmissionRecordController::class, 'destroy']);
});

// Scope 1 Entry (separate page — direct emissions, 3-step flow)
Route::prefix('scope1-entry')->name('scope1_entry.')->middleware('auth')->group(function () {
    Route::get('/', [Scope1EntryController::class, 'index'])->name('index');
    Route::get('/data', [Scope1EntryController::class, 'getData'])->name('data');
    Route::get('/stats', [Scope1EntryController::class, 'getStats'])->name('stats');
});

// Scope 2 Entry (purchased energy — electricity, heating, cooling, 3-step flow)
Route::prefix('scope2-entry')->name('scope2_entry.')->middleware('auth')->group(function () {
    Route::get('/', [Scope2EntryController::class, 'index'])->name('index');
    Route::get('/data', [Scope2EntryController::class, 'getData'])->name('data');
    Route::get('/stats', [Scope2EntryController::class, 'getStats'])->name('stats');
});

// Scope 3 Entry (indirect value chain — 15 categories, 3-step flow)
Route::prefix('scope3-entry')->name('scope3_entry.')->middleware('auth')->group(function () {
    Route::get('/', [Scope3EntryController::class, 'index'])->name('index');
    Route::get('/data', [Scope3EntryController::class, 'getData'])->name('data');
    Route::get('/stats', [Scope3EntryController::class, 'getStats'])->name('stats');
});

Route::middleware(['auth'])->group(function () {
    Route::get('emissions/import', [EmissionImportController::class, 'showImportForm'])->name('emissions.import.form');
    Route::post('emissions/import', [EmissionImportController::class, 'import'])->name('emissions.import');
    Route::get('emissions/sample', [EmissionImportController::class, 'downloadSample'])->name('emissions.sample');
});

// Import History Routes
Route::prefix('import-history')->name('import_history.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\ImportHistoryController::class, 'index'])->name('index');
    Route::get('/data', [App\Http\Controllers\ImportHistoryController::class, 'getData'])->name('data');
    Route::get('/statistics', [App\Http\Controllers\ImportHistoryController::class, 'getStatistics'])->name('statistics');
    Route::get('/trend', [App\Http\Controllers\ImportHistoryController::class, 'getTrendData'])->name('trend');
    Route::get('/distribution', [App\Http\Controllers\ImportHistoryController::class, 'getStatusDistribution'])->name('distribution');
    Route::get('/export', [App\Http\Controllers\ImportHistoryController::class, 'exportHistory'])->name('export');
    Route::get('/export-logs', [App\Http\Controllers\ImportHistoryController::class, 'exportLogsBulk'])->name('export_logs');
    Route::get('/sources', [App\Http\Controllers\ImportHistoryController::class, 'getImportSources'])->name('sources');
    Route::get('/{id}', [App\Http\Controllers\ImportHistoryController::class, 'show'])->name('show');
    Route::get('/{id}/logs', [App\Http\Controllers\ImportHistoryController::class, 'getLogs'])->name('logs');
    Route::get('/{id}/download', [App\Http\Controllers\ImportHistoryController::class, 'downloadFile'])->name('download');
    Route::get('/{id}/report', [App\Http\Controllers\ImportHistoryController::class, 'downloadReport'])->name('report');
    Route::post('/{id}/retry', [App\Http\Controllers\ImportHistoryController::class, 'retry'])->name('retry');
    Route::post('/{id}/cancel', [App\Http\Controllers\ImportHistoryController::class, 'cancel'])->name('cancel');
    Route::delete('/{id}', [App\Http\Controllers\ImportHistoryController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-action', [App\Http\Controllers\ImportHistoryController::class, 'bulkAction'])->name('bulk_action');
});

// Governance: reporting-period lock/freeze + base year
Route::prefix('reporting-periods')->name('reporting_periods.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\ReportingPeriodController::class, 'index'])->name('index');
    Route::post('/{year}/lock', [App\Http\Controllers\ReportingPeriodController::class, 'lock'])->name('lock');
    Route::post('/{year}/unlock', [App\Http\Controllers\ReportingPeriodController::class, 'unlock'])->name('unlock');
    Route::post('/{year}/base-year', [App\Http\Controllers\ReportingPeriodController::class, 'setBaseYear'])->name('base_year');
});

Route::prefix('review-data')->name('review_data.')->middleware('auth')->group(function () {
    Route::get('/', [ReviewDataController::class, 'index'])->name('index');
    Route::get('/data', [ReviewDataController::class, 'getData'])->name('data');
    Route::get('/{id}', [ReviewDataController::class, 'show'])->name('show');
    Route::put('/{id}/status', [ReviewDataController::class, 'updateStatus'])->name('update_status');
    Route::post('/bulk-update', [ReviewDataController::class, 'bulkUpdate'])->name('bulk_update');
});

Route::prefix('targets')->name('targets.')->middleware('auth')->group(function () {
    Route::get('/', [TargetController::class, 'index'])->name('index');
    Route::get('/data', [TargetController::class, 'getData'])->name('data');
    Route::post('/store-or-update', [TargetController::class, 'storeOrUpdate'])->name('storeOrUpdate');
    Route::get('/{id}', [TargetController::class, 'show'])->name('show');
    Route::delete('/{id}', [TargetController::class, 'destroy'])->name('destroy');
});

// Scope 3 Routes
Route::prefix('scope3')->name('scope3.')->middleware('auth')->group(function () {
    Route::get('/', [Scope3Controller::class, 'index'])->name('index');
    Route::get('/calculator', [Scope3Controller::class, 'calculator'])->name('calculator');
    Route::get('/calculator-easy', [Scope3Controller::class, 'calculatorImproved'])->name('calculator.easy');
    Route::get('/summary', [Scope3Controller::class, 'getScope3Summary'])->name('summary');
    Route::get('/categories', [Scope3Controller::class, 'getCategories'])->name('categories');
    Route::get('/category/{categoryId}', [Scope3Controller::class, 'getCategoryDetails'])->name('category.details');
});

// Scope Finder — helps users who don't know which scope an activity belongs to
Route::middleware(['auth'])->group(function () {
    Route::get('/scope-finder', [ScopeClassifierController::class, 'index'])->name('scope_classifier.index');
    Route::post('/scope-finder/classify', [ScopeClassifierController::class, 'classify'])->name('scope_classifier.classify');
});

// Boundary Advisor — works out WHAT a company needs to measure, before any
// number is entered. Produces a decided, justified inventory boundary.
Route::prefix('boundary')->name('boundary.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\BoundaryController::class, 'index'])->name('index');
    Route::post('/start', [App\Http\Controllers\BoundaryController::class, 'start'])->name('start');
    Route::post('/{assessment}/generate', [App\Http\Controllers\BoundaryController::class, 'generate'])->name('generate');
    Route::post('/{assessment}/accept-recommended', [App\Http\Controllers\BoundaryController::class, 'acceptRecommended'])->name('accept_recommended');
    Route::post('/{assessment}/activate', [App\Http\Controllers\BoundaryController::class, 'activate'])->name('activate');
    Route::patch('/items/{item}', [App\Http\Controllers\BoundaryController::class, 'decide'])->name('items.decide');
    Route::get('/{assessment}/statement', [App\Http\Controllers\BoundaryController::class, 'export'])->name('statement');
    Route::post('/restart', [App\Http\Controllers\BoundaryController::class, 'restart'])->name('restart');
});

// Supplier Routes
Route::prefix('suppliers')->name('suppliers.')->middleware('auth')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/data', [SupplierController::class, 'getData'])->name('data');
    Route::get('/list', [SupplierController::class, 'list'])->name('list');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::get('/{id}', [SupplierController::class, 'show'])->name('show');
    Route::put('/{id}', [SupplierController::class, 'update'])->name('update');
    Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/emissions', [SupplierController::class, 'getEmissionsSummary'])->name('emissions');
});

// Supplier Survey Routes
Route::prefix('supplier-surveys')->name('supplier_surveys.')->middleware('auth')->group(function () {
    Route::get('/', [SupplierSurveyController::class, 'index'])->name('index');
    Route::get('/data', [SupplierSurveyController::class, 'getData'])->name('data');
    Route::post('/', [SupplierSurveyController::class, 'store'])->name('store');
    Route::get('/{id}', [SupplierSurveyController::class, 'show'])->name('show');
    Route::put('/{id}/responses', [SupplierSurveyController::class, 'updateResponses'])->name('update_responses');
    Route::post('/{id}/send', [SupplierSurveyController::class, 'send'])->name('send');
    Route::post('/{id}/resend', [SupplierSurveyController::class, 'resendLink'])->name('resend');
    Route::post('/{id}/reminder', [SupplierSurveyController::class, 'sendReminder'])->name('reminder');
    Route::delete('/{id}', [SupplierSurveyController::class, 'destroy'])->name('destroy');
});

// Supplier Portal (public, token-based). Rate-limited: these are unauthenticated
// and resolve a survey purely by its token.
Route::prefix('supplier-portal')->name('supplier_portal.')->middleware('throttle:30,1')->group(function () {
    Route::get('/survey/{token}', [SupplierSurveyController::class, 'publicShow'])->name('survey.show');
    Route::post('/survey/{token}', [SupplierSurveyController::class, 'publicSubmit'])->name('survey.submit');
});

// EIO Factor Routes
Route::prefix('eio-factors')->name('eio_factors.')->middleware('auth')->group(function () {
    Route::get('/', [EioFactorController::class, 'index'])->name('index');
    Route::get('/data', [EioFactorController::class, 'getData'])->name('data');
    Route::get('/get-factor', [EioFactorController::class, 'getFactor'])->name('get_factor');
    Route::post('/calculate', [EioFactorController::class, 'calculate'])->name('calculate');
    Route::post('/', [EioFactorController::class, 'store'])->name('store');
    Route::put('/{id}', [EioFactorController::class, 'update'])->name('update');
    Route::delete('/{id}', [EioFactorController::class, 'destroy'])->name('destroy');
});

// Energy Attribute Certificates (market-based Scope 2 instruments)
Route::prefix('energy-certificates')->name('energy_certificates.')->middleware('auth')->group(function () {
    Route::get('/', [EnergyAttributeCertificateController::class, 'index'])->name('index');
    Route::get('/data', [EnergyAttributeCertificateController::class, 'getData'])->name('data');
    Route::get('/list', [EnergyAttributeCertificateController::class, 'list'])->name('list');
    Route::post('/store-or-update', [EnergyAttributeCertificateController::class, 'storeOrUpdate'])->name('storeOrUpdate');
    Route::get('/{id}', [EnergyAttributeCertificateController::class, 'show']);
    Route::delete('/{id}', [EnergyAttributeCertificateController::class, 'destroy']);
});

// Disclosure Reports (CSRD/ESRS E1, CDP, GRI 305)
Route::prefix('disclosure-reports')->name('disclosure.')->middleware('auth')->group(function () {
    Route::get('/', [DisclosureReportController::class, 'index'])->name('index');
    Route::get('/export', [DisclosureReportController::class, 'export'])->name('export');
});

// Regulated MRV workspace (EAD UAE / EU-ETS) — opt-in, facility-level
Route::prefix('mrv')->name('mrv.')->middleware('auth')->group(function () {
    Route::get('/', [MrvReportController::class, 'index'])->name('index');
    Route::post('/enable-facility', [MrvReportController::class, 'enableFacility'])->name('enableFacility');
    Route::post('/prefill', [MrvReportController::class, 'prefill'])->name('prefill');
    Route::get('/export', [MrvReportController::class, 'export'])->name('export');
    Route::post('/stream', [MrvReportController::class, 'saveStream'])->name('saveStream');
    Route::delete('/stream/{id}', [MrvReportController::class, 'deleteStream'])->name('deleteStream');
    Route::post('/report', [MrvReportController::class, 'saveReport'])->name('saveReport');
    Route::post('/source', [MrvReportController::class, 'saveSource'])->name('saveSource');
    Route::delete('/source/{id}', [MrvReportController::class, 'deleteSource'])->name('deleteSource');
    Route::post('/instrument', [MrvReportController::class, 'saveInstrument'])->name('saveInstrument');
    Route::delete('/instrument/{id}', [MrvReportController::class, 'deleteInstrument'])->name('deleteInstrument');
});

// Data Quality Routes
Route::prefix('data-quality')->name('data_quality.')->middleware('auth')->group(function () {
    Route::get('/', [DataQualityController::class, 'index'])->name('index');
    Route::get('/summary', [DataQualityController::class, 'getSummary'])->name('summary');
    Route::put('/record/{id}', [DataQualityController::class, 'updateQuality'])->name('update_quality');
});

Route::prefix('reports')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/statistics', [App\Http\Controllers\ReportController::class, 'statistics'])->name('reports.statistics');
    Route::get('/data', [App\Http\Controllers\ReportController::class, 'getData'])->name('reports.data');
    Route::get('/json', [App\Http\Controllers\ReportController::class, 'getReportsJson'])->name('reports.json');
    Route::post('/store-or-update', [App\Http\Controllers\ReportController::class, 'storeOrUpdate'])->name('reports.storeOrUpdate');

    // GHG Protocol Report (must come before /{id} route)
    Route::get('/ghg-protocol', [App\Http\Controllers\GHGReportController::class, 'index'])->name('reports.ghg_protocol');
    Route::get('/ghg-protocol/export', [App\Http\Controllers\GHGReportController::class, 'export'])->name('reports.ghg_protocol.export');

    // Templates
    Route::get('/templates/list', [App\Http\Controllers\ReportController::class, 'getTemplates'])->name('reports.templates.list');
    Route::post('/templates/store', [App\Http\Controllers\ReportController::class, 'storeTemplate'])->name('reports.templates.store');

    // Scheduled Reports
    Route::get('/scheduled/list', [App\Http\Controllers\ReportController::class, 'getScheduledReports'])->name('reports.scheduled.list');
    Route::post('/scheduled/store', [App\Http\Controllers\ReportController::class, 'storeScheduledReport'])->name('reports.scheduled.store');
    Route::post('/scheduled/{id}/run', [App\Http\Controllers\ReportController::class, 'runScheduledNow'])->name('reports.scheduled.run');

    // Export Jobs
    Route::get('/exports/list', [App\Http\Controllers\ReportController::class, 'getExportJobs'])->name('reports.exports.list');
    Route::post('/exports/store', [App\Http\Controllers\ReportController::class, 'storeExportJob'])->name('reports.exports.store');
    Route::get('/exports/{id}/download', [App\Http\Controllers\ReportController::class, 'downloadExportJob'])->name('reports.exports.download');

    // Dynamic routes (must come last)
    Route::get('/{id}/download/{format}', [App\Http\Controllers\ReportController::class, 'download'])->name('reports.download');
    Route::get('/{id}', [App\Http\Controllers\ReportController::class, 'show']);
    Route::delete('/{id}', [App\Http\Controllers\ReportController::class, 'destroy']);
    Route::post('/{id}/track-view', [App\Http\Controllers\ReportController::class, 'trackView'])->name('reports.trackView');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/utility-bills', [UtilityBillController::class, 'index'])->name('utility.index');
    Route::get('/utility-bills/create', [UtilityBillController::class, 'create'])->name('utility.create');
    Route::post('/utility-bills/upload', [UtilityBillController::class, 'upload'])->name('utility.upload');
    Route::get('/utility-bills/{utilityBill}/download', [UtilityBillController::class, 'download'])->name('utility.download');

    Route::get('/bill-upload', [BillOCRController::class, 'showForm'])->name('bill.upload');
    Route::post('/bill-upload', [BillOCRController::class, 'upload'])->name('bill.upload.post');

    // Data Source - Coming Soon
    Route::get('/data-source', function () {
        return view('data_source.coming_soon');
    })->name('data_source.index');

    // Ask Your Data — AI assistant over the company's emissions inventory
    Route::get('/ask', [App\Http\Controllers\AskController::class, 'index'])->name('assistant.index');
    Route::post('/ask', [App\Http\Controllers\AskController::class, 'ask'])->name('assistant.ask');

    // AI Document Extraction (Phase 1) — upload a file, AI extracts emission
    // line items, user reviews, saved as draft records into the Review queue.
    Route::prefix('ai/extract')->name('ai_extract.')->group(function () {
        Route::get('/', [App\Http\Controllers\DocumentExtractionController::class, 'index'])->name('index');
        Route::get('/history', [App\Http\Controllers\DocumentExtractionController::class, 'history'])->name('history');
        Route::post('/', [App\Http\Controllers\DocumentExtractionController::class, 'extract'])->name('extract');
        Route::post('/save', [App\Http\Controllers\DocumentExtractionController::class, 'store'])->name('store');
    });

    // In-app notifications (personal to the signed-in user)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/feed', [NotificationController::class, 'feed'])->name('feed');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('readAll');
        Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::get('/{id}/open', [NotificationController::class, 'open'])->name('open');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });
});
