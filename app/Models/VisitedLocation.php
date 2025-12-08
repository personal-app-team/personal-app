<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VisitedLocation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'visitable_id',
        'visitable_type',
        'address',
        'latitude',
        'longitude',
        'started_at',
        'ended_at',
        'duration_minutes',
        'notes',
        'workers_count' // для массовых отчетов
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'workers_count' => 'integer',
    ];

    // === ЛОГИРОВАНИЕ ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'visitable_id', 'visitable_type', 'address', 'started_at', 'ended_at',
                'duration_minutes', 'notes', 'workers_count'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                $type = match($this->visitable_type) {
                    'App\\Models\\Shift' => 'смены',
                    'App\\Models\\MassPersonnelReport' => 'массового отчета',
                    default => 'объекта',
                };
                
                return match($eventName) {
                    'created' => "Посещенная локация для {$type} создана",
                    'updated' => "Посещенная локация для {$type} изменена",
                    'deleted' => "Посещенная локация для {$type} удалена",
                    default => "Посещенная локация {$eventName}",
                };
            })
            ->useLogName('visited_locations')
            ->logFillable()
            ->submitEmptyLogs(false);
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'visitable_info' => $this->visitable_info,
            'duration_hours' => round($this->duration_minutes / 60, 2) . ' ч',
            'location_type' => $this->visitable_type,
        ]);
    }

    // === ПОЛИМОРФНАЯ СВЯЗЬ ===
    public function visitable()
    {
        return $this->morphTo();
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    // === АТРИБУТЫ ===
    public function getVisitableInfoAttribute(): string
    {
        if (!$this->visitable) {
            return 'Не указано';
        }
        
        return match($this->visitable_type) {
            'App\\Models\\Shift' => "Смена #{$this->visitable_id}",
            'App\\Models\\MassPersonnelReport' => "Массовый отчет #{$this->visitable_id}",
            default => "{$this->visitable_type} #{$this->visitable_id}",
        };
    }

    public function getDurationHoursAttribute(): float
    {
        return $this->duration_minutes / 60;
    }

    // === SCOPES ===
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('visitable_type', 'App\\Models\\Shift')
                    ->where('visitable_id', $shiftId);
    }

    public function scopeForMassPersonnelReport($query, $reportId)
    {
        return $query->where('visitable_type', 'App\\Models\\MassPersonnelReport')
                    ->where('visitable_id', $reportId);
    }

    public function scopeShifts($query)
    {
        return $query->where('visitable_type', 'App\\Models\\Shift');
    }

    public function scopeMassPersonnelReports($query)
    {
        return $query->where('visitable_type', 'App\\Models\\MassPersonnelReport');
    }
}
