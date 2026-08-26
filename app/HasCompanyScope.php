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
            // This is the COMPANY boundary, and it operates entirely inside one
            // client's database. The boundary between CLIENTS is the database
            // itself — nothing here can cross it, and nothing here is what
            // stops it being crossed.
            //
            // A company context was resolved (an ordinary user, or an account
            // owner who picked one of their companies): scope every query to it.
            $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
            if ($companyId) {
                $builder->where('company_id', $companyId);

                return;
            }

            // No company context. Decide based on who is asking.
            $user = auth()->user();

            // Unauthenticated context (console commands, seeders, queue jobs, or
            // routes that legitimately bypass the scope via withoutGlobalScope):
            // leave the query unscoped. Trusted, and already confined to
            // whichever tenant database the caller is connected to.
            if (! $user) {
                return;
            }

            // Account owners see every company in their own account. That is
            // the whole of their reach: the connection they are on belongs to
            // their tenant, so "unscoped" here means "all of this client's
            // companies", never anyone else's.
            if ($user->is_account_owner) {
                return;
            }

            // Authenticated, not an account owner, and no company bound — a
            // user with company_id = NULL, or whose company is inactive.
            // Without this guard the query would run unscoped and expose every
            // company in the account, so deny everything instead.
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
