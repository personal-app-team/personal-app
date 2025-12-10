<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'default_payer_company',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // === ИСПРАВЛЕННЫЕ СВЯЗИ ===
    
    public function addresses()
    {
        return $this->belongsToMany(Address::class)
                    ->using(AddressProject::class);
    }

    public function purposes()
    {
        return $this->hasMany(Purpose::class);
    }

    public function workRequests()
    {
        return $this->hasMany(WorkRequest::class);
    }

    // Связи через purposes
    public function payerCompanies()
    {
        return $this->hasManyThrough(PurposePayerCompany::class, Purpose::class);
    }

    public function addressRules()
    {
        return $this->hasManyThrough(PurposeAddressRule::class, Purpose::class);
    }

    // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===
    
    // Если у проекта нет назначений - используем компанию по умолчанию
    public function getDefaultPayerCompany()
    {
        return $this->default_payer_company;
    }

    // Количество активных назначений
    public function getActivePurposesCountAttribute()
    {
        return $this->purposes()->where('is_active', true)->count();
    }

    // Добавить в класс Project
    public function getAddressesCountAttribute()
    {
        return $this->addresses()->count();
    }

    public function getPurposesCountAttribute()
    {
        return $this->purposes()->count();
    }
}
