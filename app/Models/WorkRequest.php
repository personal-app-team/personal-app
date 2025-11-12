<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkRequest extends Model
{
    use HasFactory;

    // === СТАТУСЫ ЗАЯВКИ (ПОЛНЫЙ НАБОР) ===
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_BRIGADIER_CONFIRMATION = 'pending_brigadier_confirmation';
    const STATUS_PUBLISHED = 'published';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_CLOSED = 'closed';
    const STATUS_NO_SHIFTS = 'no_shifts';
    const STATUS_WORKING = 'working';
    const STATUS_UNCLOSED = 'unclosed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // ТИПЫ ПЕРСОНАЛА
    const PERSONNEL_OUR = 'our';
    const PERSONNEL_CONTRACTOR = 'contractor';

    protected $fillable = [
        'request_number',
        'initiator_id',
        'brigadier_id',
        'contact_person',
        'category_id',
        'work_type_id',
        'address_id',
        'is_custom_address',
        'custom_address',
        'project_id',
        'purpose_id',
        'personnel_type',
        'contractor_id',
        'mass_personnel_names',
        'work_date',
        'start_time',
        'workers_count',
        'estimated_shift_duration',
        'status',
        'dispatcher_id',
        'additional_info',
        'total_worked_hours',
    ];

    protected $casts = [
        'is_custom_address' => 'boolean',
        'brigadier_manual' => 'boolean',
        'personnel_type' => 'string',
        'work_date' => 'date',
        'start_time' => 'datetime',
        'published_at' => 'datetime',
        'staffed_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_shift_duration' => 'decimal:2',
        'total_worked_hours' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // === МАССИВЫ ДЛЯ УДОБСТВА ===
    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_PENDING_BRIGADIER_CONFIRMATION => 'Ожидает подтверждения бригадира',
            self::STATUS_PUBLISHED => 'Опубликована',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_CLOSED => 'Закрыта',
            self::STATUS_NO_SHIFTS => 'Нет смен',
            self::STATUS_WORKING => 'Выполняется',
            self::STATUS_UNCLOSED => 'Не закрыта',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_CANCELLED => 'Отменена',
        ];
    }

    public static function getPersonnelTypes()
    {
        return [
            self::PERSONNEL_OUR => 'Наш персонал',
            self::PERSONNEL_CONTRACTOR => 'Персонал подрядчика',
        ];
    }

    // === СВЯЗИ ===
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function brigadier()
    {
        return $this->belongsTo(User::class, 'brigadier_id');
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function purpose()
    {
        return $this->belongsTo(Purpose::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'request_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function massPersonnelReports()
    {
        return $this->hasMany(MassPersonnelReport::class);
    }

    // === НОВАЯ СВЯЗЬ: ИСТОРИЯ СТАТУСОВ ===
    public function statusHistory()
    {
        return $this->hasMany(WorkRequestStatus::class)->orderBy('changed_at', 'desc');
    }

    public function currentStatusRecord()
    {
        return $this->hasOne(WorkRequestStatus::class)->latestOfMany();
    }

    // === SCOPE ДЛЯ ФИЛЬТРАЦИИ ===
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingBrigadier($query)
    {
        return $query->where('status', self::STATUS_PENDING_BRIGADIER_CONFIRMATION);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    // === БИЗНЕС-МЕТОДЫ С ИСТОРИЕЙ ===

    /**
     * Безопасное изменение статуса с записью в историю
     */
    public function changeStatus($newStatus, $changedBy = null, $notes = null)
    {
        $oldStatus = $this->status;
        
        // Обновляем текущий статус
        $this->update(['status' => $newStatus]);
        
        // Записываем в историю
        WorkRequestStatus::create([
            'work_request_id' => $this->id,
            'status' => $newStatus,
            'changed_by_id' => $changedBy ?? auth()->id(),
            'changed_at' => now(),
            'notes' => $notes ?? "Status changed from {$oldStatus} to {$newStatus}"
        ]);

        return $this;
    }

    /**
     * Получить контактное лицо (бригадир или ручное)
     */
    public function getContactPersonAttribute()
    {
        return $this->brigadier_manual ?: $this->brigadier?->full_name;
    }

    /**
     * Получить финальный адрес (официальный или кастомный)
     */
    public function getFinalAddressAttribute()
    {
        if ($this->is_custom_address && $this->custom_address) {
            return $this->custom_address;
        }
        return $this->address?->full_address;
    }

    /**
     * Получить отображаемый тип персонала
     */
    public function getPersonnelTypeDisplayAttribute()
    {
        return self::getPersonnelTypes()[$this->personnel_type] ?? 'Не указан';
    }

    /**
     * Получить отображаемый статус
     */
    public function getStatusDisplayAttribute()
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Проверить можно ли генерировать номер заявки
     */
    public function canGenerateRequestNumber()
    {
        return $this->status === self::STATUS_CLOSED && 
               $this->personnel_type && 
               $this->category_id;
    }

    /**
     * Сгенерировать номер заявки по правилам
     */
    public function generateRequestNumber()
    {
        if (!$this->canGenerateRequestNumber()) {
            return null;
        }

        if ($this->personnel_type === self::PERSONNEL_OUR) {
            return $this->category->prefix . '-' . $this->id . '/' . $this->work_date->year;
        }

        if ($this->personnel_type === self::PERSONNEL_CONTRACTOR && $this->contractor) {
            return $this->contractor->contractor_code . '-' . $this->id . '/' . $this->work_date->year;
        }

        return null;
    }

    /**
     * Можно ли назначать бригадира для этой заявки
     */
    public function canAssignBrigadier()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_BRIGADIER_CONFIRMATION
        ]);
    }

    /**
     * Заявка находится на этапе планирования
     */
    public function isInPlanningStage()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_BRIGADIER_CONFIRMATION
        ]);
    }
}
