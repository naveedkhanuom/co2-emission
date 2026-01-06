<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\EmissionSourceController;
use App\Http\Controllers\EmissionFactorController;
use App\Http\Controllers\EmissionRecordController;
use App\Http\Controllers\UtilityBillController;
use App\Http\Controllers\BillOCRController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacilitiesController;
use App\Http\Controllers\EmissionImportController;
use App\Http\Controllers\ReviewDataController;
use App\Http\Controllers\ImportHistoryController;
use App\Http\Controllers\TargetController;


Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::resources([
    'roles' => RoleController::class,
]);

// Users routes - data route must come before resource route
Route::get('users/data', [UserController::class, 'getData'])->name('users.data');
Route::resource('users', UserController::class);




Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
Route::get('companies/{id}', [CompanyController::class, 'show'])->name('companies.show');
Route::get('companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');



Route::resource('departments', DepartmentController::class);


Route::get('/facilities', [FacilitiesController::class, 'index'])->name('facilities.index');
Route::post('/facilities', [FacilitiesController::class, 'store'])->name('facilities.store');
Route::put('/facilities/{facility}', [FacilitiesController::class, 'update'])->name('facilities.update');
Route::delete('/facilities/{facility}', [FacilitiesController::class, 'destroy'])->name('facilities.destroy');



Route::prefix('sites')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/getSites', [SiteController::class, 'getSites'])->name('sites.getSites');
    Route::post('/storeOrUpdate', [SiteController::class, 'storeOrUpdate'])->name('sites.storeOrUpdate');
    Route::get('/{id}', [SiteController::class, 'show']);
    Route::delete('/{id}', [SiteController::class, 'destroy']);
});

Route::prefix('emission-sources')->name('emission_sources.')->group(function () {
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



Route::prefix('emission-records')->group(function() {
    Route::get('/', [EmissionRecordController::class,'index'])->name('emission_records.index');
    Route::get('/scope-entry', [EmissionRecordController::class,'scopeEntry'])->name('emission_records.scope_entry');
    Route::get('/data', [EmissionRecordController::class,'getData'])->name('emission_records.data');
    Route::post('/store', [EmissionRecordController::class,'store'])->name('emission-records.store');
    Route::post('/store-or-update', [EmissionRecordController::class,'storeOrUpdate'])->name('emission_records.storeOrUpdate');
    Route::get('/{emissionRecord}', [EmissionRecordController::class,'show']);
    Route::delete('/{emissionRecord}', [EmissionRecordController::class,'destroy']);
});


Route::get('emissions/import', [EmissionImportController::class, 'showImportForm'])->name('emissions.import.form');
Route::post('emissions/import', [EmissionImportController::class, 'import'])->name('emissions.import');
Route::get('emissions/sample', [EmissionImportController::class, 'downloadSample'])->name('emissions.sample');

// Import History Routes
Route::prefix('import-history')->name('import_history.')->middleware('auth')->group(function() {
    Route::get('/', [App\Http\Controllers\ImportHistoryController::class, 'index'])->name('index');
    Route::get('/data', [App\Http\Controllers\ImportHistoryController::class, 'getData'])->name('data');
    Route::get('/statistics', [App\Http\Controllers\ImportHistoryController::class, 'getStatistics'])->name('statistics');
    Route::get('/trend', [App\Http\Controllers\ImportHistoryController::class, 'getTrendData'])->name('trend');
    Route::get('/distribution', [App\Http\Controllers\ImportHistoryController::class, 'getStatusDistribution'])->name('distribution');
    Route::get('/{id}', [App\Http\Controllers\ImportHistoryController::class, 'show'])->name('show');
    Route::get('/{id}/logs', [App\Http\Controllers\ImportHistoryController::class, 'getLogs'])->name('logs');
    Route::delete('/{id}', [App\Http\Controllers\ImportHistoryController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-action', [App\Http\Controllers\ImportHistoryController::class, 'bulkAction'])->name('bulk_action');
});

Route::prefix('review-data')->name('review_data.')->group(function() {
    Route::get('/', [ReviewDataController::class, 'index'])->name('index');
    Route::get('/data', [ReviewDataController::class, 'getData'])->name('data');
    Route::get('/{id}', [ReviewDataController::class, 'show'])->name('show');
    Route::put('/{id}/status', [ReviewDataController::class, 'updateStatus'])->name('update_status');
    Route::post('/bulk-update', [ReviewDataController::class, 'bulkUpdate'])->name('bulk_update');
});

Route::prefix('targets')->name('targets.')->middleware('auth')->group(function() {
    Route::get('/', [TargetController::class, 'index'])->name('index');
});


Route::prefix('reports')->group(function() {
    Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/statistics', [App\Http\Controllers\ReportController::class, 'statistics'])->name('reports.statistics');
    Route::get('/data', [App\Http\Controllers\ReportController::class, 'getData'])->name('reports.data');
    Route::get('/json', [App\Http\Controllers\ReportController::class, 'getReportsJson'])->name('reports.json');
    Route::post('/store-or-update', [App\Http\Controllers\ReportController::class, 'storeOrUpdate'])->name('reports.storeOrUpdate');
    Route::get('/{id}', [App\Http\Controllers\ReportController::class, 'show']);
    Route::delete('/{id}', [App\Http\Controllers\ReportController::class, 'destroy']);
});





Route::middleware(['auth'])->group(function () {
    Route::get('/utility-bills', [UtilityBillController::class, 'index'])->name('utility.index');
    Route::get('/utility-bills/create', [UtilityBillController::class, 'create'])->name('utility.create');
    Route::post('/utility-bills/upload', [UtilityBillController::class, 'upload'])->name('utility.upload');
    
    Route::get('/bill-upload', [BillOCRController::class, 'showForm'])->name('bill.upload');
    Route::post('/bill-upload', [BillOCRController::class, 'upload'])->name('bill.upload.post');
});










