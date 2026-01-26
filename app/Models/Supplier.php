<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

class Supplier extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'contact_person',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'industry',
        'data_quality',
        'emission_factors',
        'status',
        'last_data_submission',
        'notes'
    ];

    protected $casts = [
        'emission_factors' => 'array',
        'last_data_submission' => 'datetime',
    ];

    /**
     * Get the company that owns this supplier.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all emission records for this supplier.
     */
    public function emissionRecords()
    {
        return $this->hasMany(EmissionRecord::class);
    }

    /**
     * Get total emissions for this supplier.
     */
    public function getTotalEmissions($year = null)
    {
        $query = $this->emissionRecords();
        
        if ($year) {
            $query->whereYear('entry_date', $year);
        }
        
        return $query->sum('co2e_value');
    }

    /**
     * Update data quality based on emission records.
     */
    public function updateDataQuality()
    {
        $primaryCount = $this->emissionRecords()
            ->where('data_quality', 'primary')
            ->count();
        
        $totalCount = $this->emissionRecords()->count();
        
        if ($totalCount > 0) {
            $primaryPercentage = ($primaryCount / $totalCount) * 100;
            
            if ($primaryPercentage >= 70) {
                $this->data_quality = 'primary';
            } elseif ($primaryPercentage >= 30) {
                $this->data_quality = 'secondary';
            } else {
                $this->data_quality = 'estimated';
            }
            
            $this->save();
        }
    }

    /**
     * Check if supplier has submitted data recently.
     */
    public function hasRecentData($days = 365)
    {
        if (!$this->last_data_submission) {
            return false;
        }

        return $this->last_data_submission->diffInDays(now()) <= $days;
    }

    /**
     * Get emissions by scope 3 category for this supplier.
     */
    public function getEmissionsByCategory($year = null)
    {
        $query = $this->emissionRecords()
            ->selectRaw('scope3_category_id, SUM(co2e_value) as total')
            ->groupBy('scope3_category_id')
            ->with('scope3Category');

        if ($year) {
            $query->whereYear('entry_date', $year);
        }

        return $query->get();
    }

    /**
     * Get all surveys for this supplier.
     */
    public function surveys()
    {
        return $this->hasMany(SupplierSurvey::class);
    }

    /**
     * Get data quality score (0-100).
     */
    public function getDataQualityScore()
    {
        $totalRecords = $this->emissionRecords()->count();
        
        if ($totalRecords === 0) {
            return 0;
        }

        $primaryCount = $this->emissionRecords()->where('data_quality', 'primary')->count();
        $secondaryCount = $this->emissionRecords()->where('data_quality', 'secondary')->count();
        
        // Score: Primary = 100 points, Secondary = 50 points, Estimated = 0 points
        $score = (($primaryCount * 100) + ($secondaryCount * 50)) / $totalRecords;
        
        return round($score, 2);
    }
}
