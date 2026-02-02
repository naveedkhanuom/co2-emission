<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            // Super Admin and Admin have full access to everything
            if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
                return true;
            }
            return null;
        });
        
        Paginator::useBootstrapFive();
    }
}
