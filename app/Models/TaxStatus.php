<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_type_id',
        'name',
        'tax_rate',
        'description',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:3',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function employmentHistories()
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    public function contractors()
    {
        return $this->hasMany(Contractor::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    // Accessor для отображения ставки в процентах
    public function getTaxRatePercentAttribute()
    {
        return $this->tax_rate * 100 . '%';
    }
}
