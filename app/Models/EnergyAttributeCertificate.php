<?php

namespace App\Models;

use App\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasCompanyScope;

/**
 * A market-based Scope 2 instrument: REC / GO / I-REC / PPA / VPPA / green
 * tariff / supplier-specific or residual-mix factor. Supplies the emission
 * factor used for a Scope 2 record's market-based figure.
 */
class EnergyAttributeCertificate extends Model
{
    use Auditable, HasCompanyScope, HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'certificate_number',
        'supplier_name',
        'energy_carrier',
        'mwh_volume',
        'emission_factor',
        'region',
        'vintage_year',
        'valid_from',
        'valid_to',
        'retired_at',
        'status',
        'document_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'mwh_volume' => 'decimal:4',
        'emission_factor' => 'decimal:6',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'retired_at' => 'date',
        'vintage_year' => 'integer',
    ];

    public const TYPES = [
        'rec'               => 'REC (Renewable Energy Certificate)',
        'go'                => 'GO (Guarantee of Origin)',
        'irec'              => 'I-REC (International REC)',
        'ppa'               => 'PPA (Power Purchase Agreement)',
        'vppa'              => 'Virtual PPA',
        'green_tariff'      => 'Utility Green Tariff',
        'supplier_specific' => 'Supplier-Specific Factor',
        'residual_mix'      => 'Residual Mix Factor',
    ];

    public const CARRIERS = [
        'electricity' => 'Electricity',
        'heat'        => 'Heat / Hot Water',
        'steam'       => 'Steam',
        'cooling'     => 'Cooling',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function emissionRecords()
    {
        return $this->hasMany(EmissionRecord::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }
}
