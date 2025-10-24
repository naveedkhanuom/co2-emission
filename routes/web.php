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


Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::resources([
    'roles' => RoleController::class,
    'users' => UserController::class,
]);




Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
Route::get('companies/{id}', [CompanyController::class, 'show'])->name('companies.show');
Route::get('companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');


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
    Route::get('/data', [EmissionRecordController::class,'getData'])->name('emission_records.data');
    Route::post('/store-or-update', [EmissionRecordController::class,'storeOrUpdate'])->name('emission_records.storeOrUpdate');
    Route::get('/{emissionRecord}', [EmissionRecordController::class,'show']);
    Route::delete('/{emissionRecord}', [EmissionRecordController::class,'destroy']);
});

Route::prefix('reports')->group(function() {
    Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/data', [App\Http\Controllers\ReportController::class, 'getData'])->name('reports.data');
    Route::post('/store-or-update', [App\Http\Controllers\ReportController::class, 'storeOrUpdate'])->name('reports.storeOrUpdate');
    Route::get('/{id}', [App\Http\Controllers\ReportController::class, 'show']);
    Route::delete('/{id}', [App\Http\Controllers\ReportController::class, 'destroy']);
});















