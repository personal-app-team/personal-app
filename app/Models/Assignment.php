<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'work_request_id',
        'user_id',
        'role_in_shift',
        'source',
        'planned_date',
        'assignment_number',
        'assignment_type',
        'planned_start_time',
        'planned_duration_hours',
        'assignment_comment',
        'status',
        'confirmed_at',
        'rejected_at',
        'rejection_reason',
        'planned_address_id',
        'planned_custom_address',
        'is_custom_planned_address',
        'shift_id'
    ];

    protected $casts = [
        'planned_date' => 'date',
        'planned_start_time' => 'datetime:H:i',
        'planned_duration_hours' => 'decimal:1',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_custom_planned_address' => 'boolean',
    ];

    // === Логирование ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'assignment_type',
                'status',
                'user_id',
                'work_request_id',
                'assignment_number',
                'planned_date',
                'planned_start_time',
                'planned_duration_hours',
                'planned_address_id',
                'planned_custom_address',
                'assignment_comment',
                'confirmed_at',
                'rejected_at',
                'rejection_reason',
            ])
            ->logOnlyDirty() // Только измененные поля
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Назначение создано',
                    'updated' => 'Назначение изменено',
                    'deleted' => 'Назначение удалено',
                    'restored' => 'Назначение восстановлено',
                    default => "Назначение {$eventName}",
                };
            })
            ->useLogName('assignments')
            ->logFillable() // Автоматически логировать fillable поля
            ->submitEmptyLogs(false);
    }
    
    // Дополнительные настройки для лучшего отображения
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        // Добавляем удобные метки для отношений
        $activity->properties = $activity->properties->merge([
            'user_name' => $this->user?->full_name,
            'work_request_number' => $this->workRequest?->request_number,
            'planned_address' => $this->full_planned_address,
        ]);
    }

    // === СВЯЗИ ===
    public function workRequest()
    {
        return $this->belongsTo(WorkRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plannedAddress()
    {
        return $this->belongsTo(Address::class, 'planned_address_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // === SCOPES ===
    
    // По типу назначения
    public function scopeBrigadierSchedules($query)
    {
        return $query->where('assignment_type', 'brigadier_schedule');
    }

    public function scopeWorkRequests($query)
    {
        return $query->where('assignment_type', 'work_request');
    }

    public function scopeMassPersonnel($query)
    {
        return $query->where('assignment_type', 'mass_personnel');
    }

    // По статусу
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Для бригадиров (без привязки к заявке)
    public function scopeBrigadierAssignments($query)
    {
        return $query->whereNull('work_request_id')
                    ->whereNotNull('assignment_number');
    }

    // Для исполнителей (привязаны к заявке)
    public function scopeExecutorAssignments($query)
    {
        return $query->whereNotNull('work_request_id')
                    ->whereNull('assignment_number');
    }

    // По номеру назначения
    public function scopeByAssignmentNumber($query, $assignmentNumber)
    {
        return $query->where('assignment_number', $assignmentNumber);
    }

    // Активные назначения (не отклоненные и не завершенные)
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    // === МЕТОДЫ ===

    public function isMassPersonnel()
    {
        return $this->assignment_type === 'mass_personnel';
    }

    /**
     * Подтвердить назначение
     */
    public function confirm()
    {
        return $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);
    }

    /**
     * Отклонить назначение
     */
    public function reject($reason = null)
    {
        return $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Завершить назначение
     */
    public function complete()
    {
        return $this->update([
            'status' => 'completed'
        ]);
    }

    /**
     * Получить планируемое время окончания
     */
    public function getPlannedEndTimeAttribute()
    {
        if (!$this->planned_start_time || !$this->planned_duration_hours) {
            return null;
        }

        $start = Carbon::parse($this->planned_start_time);
        return $start->addHours($this->planned_duration_hours)->format('H:i');
    }

    /**
     * Является ли назначение для бригадира
     */
    public function isBrigadierSchedule()
    {
        return $this->assignment_type === 'brigadier_schedule';
    }

    /**
     * Является ли назначение для исполнителя по заявке
     */
    public function isWorkRequest()
    {
        return $this->assignment_type === 'work_request';
    }

    /**
     * Является ли назначение подтвержденным
     */
    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    /**
     * Является ли назначение ожидающим подтверждения
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Получить полный адрес (официальный или кастомный)
     */
    public function getFullPlannedAddressAttribute()
    {
        if ($this->is_custom_planned_address) {
            return $this->planned_custom_address;
        }

        return $this->plannedAddress?->full_address;
    }
}
