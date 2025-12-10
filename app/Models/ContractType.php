<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code', 
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function taxStatuses()
    {
        return $this->hasMany(TaxStatus::class);
    }

    public function employmentHistories()
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    public function contractors()
    {
        return $this->hasMany(Contractor::class);
    }

    public function getDefaultTaxStatusAttribute()
    {
        return $this->taxStatuses()->where('is_default', true)->first();
    }
}
