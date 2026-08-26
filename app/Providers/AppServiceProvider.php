<?php

namespace App\Providers;

use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Support\EnvironmentGuard;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Route parameter => company-scoped model, for routes that rely on implicit
     * route-model binding. See bindCompanyScopedModel() for why these need
     * explicit bindings.
     *
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const COMPANY_SCOPED_BINDINGS = [
        'emissionRecord' => EmissionRecord::class,
        'facility' => Facilities::class,
    ];

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
        EnvironmentGuard::assertNotDebuggingInProduction(
            $this->app->environment(),
            (bool) config('app.debug')
        );

        EnvironmentGuard::assertSessionCookieIsNotSharedAcrossTenants(
            config('session.domain'),
            (array) config('tenancy.central_domains', [])
        );

        Gate::before(function ($user, $ability) {
            // Super Admin and Admin have full access to everything
            if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
                return true;
            }

            return null;
        });

        foreach (self::COMPANY_SCOPED_BINDINGS as $parameter => $model) {
            $this->bindCompanyScopedModel($parameter, $model);
        }

        Paginator::useBootstrapFive();
    }

    /**
     * Explicit route-model binding for a model using HasCompanyScope.
     *
     * SetCompanyConnection is appended to the `web` middleware group, so it runs
     * AFTER SubstituteBindings. That means during implicit binding the
     * `current_company_id` container instance is not bound yet, and
     * HasCompanyScope falls through to its `whereRaw('1 = 0')` branch for any
     * user who is not an account owner — a plain user got a 404 on their OWN rows.
     *
     * Binding explicitly fixes it without reordering global middleware (which
     * would change model resolution for every route in the app). The company is
     * resolved from the session/user here, exactly as the middleware does, and
     * the tenant check is applied deliberately rather than relying on a global
     * scope that has not been initialised yet.
     */
    private function bindCompanyScopedModel(string $parameter, string $model): void
    {
        Route::bind($parameter, function ($value) use ($model) {
            $query = $model::withoutGlobalScope('company');
            $companyId = $this->resolveCompanyIdForBinding();

            if ($companyId) {
                $query->where('company_id', $companyId);
            } elseif (! auth()->user()?->is_account_owner) {
                // No company context and not an account owner: deny rather than
                // resolve unscoped, mirroring HasCompanyScope.
                abort(404);
            }

            return $query->findOrFail($value);
        });
    }

    /**
     * The company this request belongs to, resolved the same way
     * SetCompanyConnection resolves it — session first, then the user's own
     * company. Runs at binding time, when the container instance the middleware
     * sets is not yet available.
     */
    private function resolveCompanyIdForBinding(): int|string|null
    {
        $request = request();

        if ($request && $request->hasSession() && $request->session()->get('current_company_id')) {
            return $request->session()->get('current_company_id');
        }

        return auth()->user()?->company_id;
    }
}
