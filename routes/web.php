<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use App\Http\Controllers\CompanyController;


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











