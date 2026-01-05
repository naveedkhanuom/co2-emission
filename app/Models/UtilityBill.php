<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtilityBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'site_id',
        'file_path',
        'bill_type',
        'supplier_name',
        'bill_date',
        'consumption',
        'consumption_unit',
        'cost',
        'raw_text',
        'raw_response',
        'extracted_data',
        'created_by',
        'emission_record_id',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'bill_date' => 'date',
        'consumption' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
