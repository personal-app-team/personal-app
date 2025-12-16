<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefix',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function specialties()
    {
        return $this->hasMany(Specialty::class);
    }

    // Проверка доступности для наших исполнителей
    public function hasOurExecutors()
    {
        return $this->specialties()
            ->whereHas('users')
            ->exists();
    }

    public function workRequests()
    {
        return $this->hasMany(WorkRequest::class);
    }

    // Проверка доступности для подрядчика
    public function hasContractorExecutors($contractorId = null)
    {
        $query = $this->specialties()
            ->whereHas('contractorRates', function($q) {
                $q->where('is_active', true);
            });

        if ($contractorId) {
            $query->whereHas('contractorRates', function($q) use ($contractorId) {
                $q->where('contractor_id', $contractorId)
                  ->where('is_active', true);
            });
        }

        return $query->exists();
    }

    // Получить всех подрядчиков с доступными ставками для этой категории
    public function availableContractors()
    {
        return \App\Models\Contractor::whereHas('contractorRates', function($q) {
            $q->whereHas('specialty', function($q) {
                $q->where('category_id', $this->id);
            })->where('is_active', true);
        })->get();
    }

    // Добавим метод для генерации номера заявки
    public function generateRequestNumber($requestId)
    {
        return $this->prefix . '-' . $requestId . '/' . now()->year;
    }
}
