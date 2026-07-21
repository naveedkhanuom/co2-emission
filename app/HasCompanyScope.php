<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

trait HasCompanyScope
{
    /**
     * Boot the trait.
     */
    protected static function bootHasCompanyScope()
    {
        static::addGlobalScope('company', function (Builder $builder) {
            // A company context was resolved (normal tenant, or a super-admin who
            // picked a company): scope every query to it.
            $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
            if ($companyId) {
                $builder->where('company_id', $companyId);
                return;
            }

            // No company context. Decide based on who is asking.
            $user = auth()->user();

            // Unauthenticated context (console commands, seeders, queue jobs, or
            // routes that legitimately bypass the scope via withoutGlobalScope):
            // leave the query unscoped — these are trusted / not tenant requests.
            if (!$user) {
                return;
            }

            // Super-admins are allowed the cross-company view when no single
            // company is selected.
            if ($user->is_super_admin) {
                return;
            }

            // Authenticated, non-super-admin, but no company is bound (e.g. a user
            // with company_id = NULL or whose company is inactive). Without this
            // guard the query would run UNSCOPED and leak every tenant's data, so
            // deny everything instead.
            $builder->whereRaw('1 = 0');
        });

        // Safety net: stamp the current company on new rows when the caller did
        // not set one explicitly. This prevents company_id from being left null
        // (or mass-assigned across tenants) on models that use this trait.
        static::creating(function ($model) {
            if (empty($model->company_id) && function_exists('current_company_id')) {
                if ($companyId = current_company_id()) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    /**
     * Scope a query to a specific company.
     */
    public function scopeForCompany(Builder $query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the current company ID from context.
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
}
