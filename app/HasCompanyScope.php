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
            if (app()->bound('current_company_id')) {
                $companyId = app('current_company_id');
                if ($companyId) {
                    $builder->where('company_id', $companyId);
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
