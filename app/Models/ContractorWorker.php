<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractorWorker extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'mass_personnel_report_id',
        'full_name',
        'notes',
        'photo_missing_reason', // Заполняется диспетчером при подтверждении
        'is_confirmed',         // Подтвержден диспетчером
        'confirmed_by',         // Кто подтвердил (user_id)
        'confirmed_at',         // Когда подтвердили
        'calculated_hours',     // Часы (рассчитываются автоматически)
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'calculated_hours' => 'decimal:2',
    ];

    // === ЛОГИРОВАНИЕ ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'mass_personnel_report_id', 'full_name', 'notes',
                'photo_missing_reason', 'is_confirmed', 'confirmed_by',
                'confirmed_at', 'calculated_hours'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Работник добавлен в массовый отчет',
                    'updated' => 'Данные работника изменены',
                    'deleted' => 'Работник удален из отчета',
                    default => "Работник {$eventName}",
                };
            })
            ->useLogName('contractor_workers')
            ->logFillable()
            ->submitEmptyLogs(false);
    }

    // === СВЯЗИ ===
    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
    }
    
    public function massPersonnelReport()
    {
        return $this->belongsTo(MassPersonnelReport::class);
    }

    public function confirmator()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // === МЕТОДЫ ===
    public function calculateHours()
    {
        $report = $this->massPersonnelReport;
        
        if (!$report) {
            return 0;
        }

        // Берем общее время отчета
        $totalMinutes = $report->visitedLocations->sum('duration_minutes');
        $hours = $totalMinutes / 60;
        
        // Округление до 30 минут
        $roundedHours = ceil($hours * 2) / 2;
        
        $this->calculated_hours = $roundedHours;
        $this->save();
        
        return $roundedHours;
    }

    public function getAmountAttribute()
    {
        $report = $this->massPersonnelReport;
        
        if (!$report || !$report->specialty) {
            return 0;
        }

        $hours = $this->calculated_hours ?: $this->calculateHours();
        $baseRate = $report->specialty->base_hourly_rate;
        
        return $baseRate * $hours;
    }

    public function confirm($userId, $reason = null)
    {
        $this->update([
            'is_confirmed' => true,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
            'photo_missing_reason' => $reason,
        ]);
    }

    public function unconfirm()
    {
        $this->update([
            'is_confirmed' => false,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'photo_missing_reason' => null,
        ]);
    }

    // === SCOPES ===
    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    public function scopeUnconfirmed($query)
    {
        return $query->where('is_confirmed', false);
    }

    public function scopeWithMissingPhoto($query)
    {
        return $query->whereNotNull('photo_missing_reason');
    }
}
