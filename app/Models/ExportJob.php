<?php

namespace App\Models;

use App\HasCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportJob extends Model
{
    use HasFactory, HasCompanyScope;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'format',
        'status',
        'file_path',
        'file_size',
        'filters',
        'error_message',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

