<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\IntercomController;
use App\Http\Controllers\ZohoMailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ZohoMailWebhookController;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

Route::resource('clients', ClientController::class);


Auth::routes();

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::resources([
    'roles' => RoleController::class,
    'users' => UserController::class,
]);










