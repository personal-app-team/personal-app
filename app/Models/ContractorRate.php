<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractorRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'contractor_id',
        'category_id',
        'specialty_name',
        'hourly_rate',
        'rate_type',
        'is_active',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // === СВЯЗИ ===
    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // === СКОПЫ ===
    public function scopeForMass($query)
    {
        return $query->where('rate_type', 'mass');
    }

    public function scopeForPersonalized($query)
    {
        return $query->where('rate_type', 'personalized');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // === МЕТОДЫ ===
    public function isMass()
    {
        return $this->rate_type === 'mass';
    }

    public function isPersonalized()
    {
        return $this->rate_type === 'personalized';
    }

    // accessors
    public function getRateDisplayAttribute()
    {
        return "{$this->rate_amount} ₽ " . match($this->rate_type) {
            'hourly' => '/час',
            'daily' => '/день',
            'project' => 'проект',
            default => '',
        } . " ({$this->specialty?->name})";
    }

    /**
     * Полное название
     */
    public function getFullNameAttribute()
    {
        return $this->category?->name . ' - ' . $this->specialty_name . 
               ' (' . ($this->isMass() ? 'Массовая' : 'Персонализированная') . ')';
    }
}
