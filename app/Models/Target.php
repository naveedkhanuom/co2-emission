<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Target extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'site_id',
        'name',
        'type',
        'scope',
        'target_year',
        'baseline_year',
        'baseline_emissions',
        'target_emissions',
        'reduction_percent',
        'strategy',
        'review_frequency',
        'responsible_person',
        'status',
        'description',
        'created_by',
    ];

    protected $casts = [
        'baseline_year' => 'integer',
        'target_year' => 'integer',
        'baseline_emissions' => 'decimal:2',
        'target_emissions' => 'decimal:2',
        'reduction_percent' => 'decimal:2',
        'company_id' => 'integer',
        'site_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}


