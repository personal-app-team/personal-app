<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'request_id',
        'user_id',
        'contractor_id',
        'role',
        'specialty_id',
        'work_type_id',
        'contract_type_id',
        'tax_status_id',
        'work_date',
        'start_time',
        'end_time',
        'status',
        'worked_minutes',
        'base_rate',
        'compensation_amount',
        'compensation_description',
        'notes',
        'is_paid',
    ];

    protected $casts = [
        'work_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'worked_minutes' => 'integer',
        'is_paid' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'user_id',
                'work_date',
                'start_time',
                'end_time',
                'worked_minutes',
                'compensation_amount',
                'base_rate',
                'hand_amount',
                'payout_amount',
                'tax_amount',
                'expenses_total',
                'is_paid',
                'assignment_number',
                'request_id',
                'role',
                'specialty_id',
                'work_type_id',
                'address_id',
                'tax_status_id',
                'contract_type_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Смена создана',
                    'updated' => 'Смена изменена',
                    'deleted' => 'Смена удалена',
                    'restored' => 'Смена восстановлена',
                    default => "Смена {$eventName}",
                };
            })
            ->useLogName('shifts');
    }

    /**
     * Дополнительные настройки для лучшего отображения
     */
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'user_name' => $this->user?->full_name,
            'work_request_number' => $this->workRequest?->request_number,
            'specialty_name' => $this->specialty?->name,
            'assignment_number' => $this->assignment_number,
            'work_date_formatted' => $this->work_date ? $this->work_date->format('d.m.Y') : null,
            'worked_hours' => $this->worked_hours,
        ]);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($shift) {
            // Recalculate worked_minutes before save
            if ($shift->start_time && $shift->end_time) {
                $start = Carbon::parse($shift->start_time);
                $end = Carbon::parse($shift->end_time);
                
                if ($end->lt($start)) {
                    $end->addDay();
                }
                
                $shift->worked_minutes = max(0, $start->diffInMinutes($end));
            }
        });
    }

    /**
     * Get the worked hours as a formatted string
     */
    public function getWorkedHoursAttribute(): string
    {
        return number_format(($this->worked_minutes ?? 0) / 60, 2);
    }

    // === СВЯЗИ ===
    public function workRequest()
    {
        return $this->belongsTo(WorkRequest::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function taxStatus()
    {
        return $this->belongsTo(TaxStatus::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function contractorRate()
    {
        return $this->belongsTo(ContractorRate::class);
    }

    public function shiftExpenses()
    {
        return $this->hasMany(ShiftExpense::class);
    }

    public function visitedLocations()
    {
        return $this->morphMany(VisitedLocation::class, 'visitable');
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    public function compensations()
    {
        return $this->morphMany(Compensation::class, 'compensatable');
    }

    public function expenses()
    {
        return $this->morphMany(Expense::class, "expensable");
    }

    public function getExpensesTotalAttribute()
    {
        return $this->expenses()->sum("amount");
    }

    // public function assignmentDate()
    // {
    // }

    // === SCOPES ===
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('work_date', today());
    }

    public function scopeBrigadier($query)
    {
        return $query->where('role', 'brigadier');
    }

    public function scopeExecutor($query)
    {
        return $query->where('role', 'executor');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeByAssignmentNumber($query, $assignmentNumber)
    {
        return $query->where('assignment_number', $assignmentNumber);
    }

    // === МЕТОДЫ РАСЧЕТОВ ===

    /**
     * Определить базовую ставку для смены
     */
    public function determineBaseRate()
    {
        // 1. Если это наш исполнитель (не подрядчик)
        if ($this->user_id && $this->specialty_id && !$this->user->contractor_id) {
            $userSpecialty = $this->user->specialties()
                ->where('specialty_id', $this->specialty_id)
                ->first();
            
            return $userSpecialty->pivot->base_hourly_rate ?? $this->specialty->base_hourly_rate ?? 0;
        }
        
        // 2. Если это подрядчик (персонализированный или массовый)
        if ($this->contractor_rate_id) {
            return $this->contractorRate->hourly_rate ?? 0;
        }
        
        // 3. Если это подрядчик без конкретной ставки (устаревшая логика)
        if ($this->user_id && $this->user->contractor_id && $this->specialty_id) {
            // Попробуем найти ставку по категории и названию специальности
            $specialty = \App\Models\Specialty::find($this->specialty_id);
            if ($specialty) {
                $rate = \App\Models\ContractorRate::where('contractor_id', $this->user->contractor_id)
                    ->where('category_id', $specialty->category_id)
                    ->where('specialty_name', $specialty->name)
                    ->active()
                    ->first();
                    
                return $rate?->hourly_rate ?? 0;
            }
        }
        
        return 0;
    }

    /**
     * Рассчитать сумму НА РУКИ (до налога)
     * Формула: (Базовая_ставка × Часы) + Компенсация + Операционные_расходы
     */
    public function calculateHandAmount()
    {
        $hours = $this->worked_minutes / 60;
        $baseRate = $this->base_rate ?: $this->determineBaseRate();
        $baseAmount = $baseRate * $hours;
        $compensation = $this->compensation_amount ?? 0;
        $expenses = $this->shiftExpenses->sum('amount');
        
        return $baseAmount + $compensation + $expenses;
    }

    /**
     * Рассчитать сумму налога
     */
    public function calculateTaxAmount()
    {
        $handAmount = $this->hand_amount ?: $this->calculateHandAmount();
        $taxRate = $this->taxStatus?->tax_rate ?? 0;
        
        return $handAmount * $taxRate;
    }

    /**
     * Рассчитать сумму К ВЫПЛАТЕ (с налогом)
     */
    public function calculatePayoutAmount()
    {
        $handAmount = $this->hand_amount ?: $this->calculateHandAmount();
        $taxAmount = $this->calculateTaxAmount();
        
        return $handAmount + $taxAmount;
    }

    /**
     * Обновить все расчеты смены
     */
    public function updateCalculations()
    {
        // Устанавливаем базовую ставку если не установлена
        if (!$this->base_rate) {
            $this->base_rate = $this->determineBaseRate();
        }

        // Устанавливаем month_period если не установлен
        if (!$this->month_period) {
            $this->month_period = $this->work_date->format('Y-m');
        }

        // Автоматически определяем tax_status и contract_type если не установлены
        if (!$this->tax_status_id) {
            $this->updateTaxStatus();
        }
        
        if (!$this->contract_type_id) {
            $this->updateContractType();
        }

        // Обновляем суммы по новой логике
        $this->hand_amount = $this->calculateHandAmount();     // НА РУКИ
        $this->tax_amount = $this->calculateTaxAmount();       // Налог
        $this->payout_amount = $this->calculatePayoutAmount(); // К ВЫПЛАТЕ
        $this->expenses_total = $this->shiftExpenses->sum('amount');
        
        $this->save();
        
        return $this;
    }

    public function getTotalTimeFromLocations()
    {
        return $this->visitedLocations()->sum('duration_minutes') / 60;
    }

    // === WORKFLOW МЕТОДЫ ===
    public function startShift()
    {
        $this->update([
            'status' => 'active',
            'start_time' => now()
        ]);
    }

    public function endShift()
    {
        $this->update([
            'status' => 'pending_approval',
            'end_time' => now()
        ]);
    }

    public function submitForApproval()
    {
        $this->update(['status' => 'pending_approval']);
    }

    public function approve()
    {
        $this->update(['status' => 'completed']);
        $this->updateCalculations();
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'is_paid' => true
        ]);
    }

    // === СТАТУСНЫЕ МЕТОДЫ ===
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPendingApproval()
    {
        return $this->status === 'pending_approval';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isBrigadier()
    {
        return $this->role === 'brigadier';
    }

    public function isExecutor()
    {
        return $this->role === 'executor';
    }

    /**
     * Рассчитать общее время из посещенных локаций
     */
    public function calculateTotalTime()
    {
        $totalMinutes = $this->visitedLocations->sum('duration_minutes');
        $this->update(['worked_minutes' => $totalMinutes]);
        return $totalMinutes;
    }

    /**
     * Определить налоговый статус для смены
     */
    public function determineTaxStatus()
    {
        if ($this->tax_status_id) {
            return $this->taxStatus;
        }

        if ($this->user_id && $this->user->tax_status_id) {
            return $this->user->taxStatus;
        }
        
        if ($this->user_id && $this->user->contractor_id && $this->user->contractor->tax_status_id) {
            return $this->user->contractor->taxStatus;
        }
        
        if ($this->contractor_id && !$this->user_id && $this->contractor->tax_status_id) {
            return $this->contractor->taxStatus;
        }
        
        return null;
    }

    /**
     * Обновить налоговый статус смены
     */
    public function updateTaxStatus()
    {
        $taxStatus = $this->determineTaxStatus();
        if ($taxStatus) {
            $this->tax_status_id = $taxStatus->id;
        }
        return $taxStatus;
    }

    /**
     * Определить тип договора для смены
     */
    public function determineContractType()
    {
        if ($this->contract_type_id) {
            return $this->contractType;
        }

        if ($this->user_id && $this->user->contract_type_id) {
            return $this->user->contractType;
        }
        
        if ($this->user_id && $this->user->contractor_id && $this->user->contractor->contract_type_id) {
            return $this->user->contractor->contractType;
        }
        
        if ($this->contractor_id && !$this->user_id && $this->contractor->contract_type_id) {
            return $this->contractor->contractType;
        }
        
        return null;
    }

    /**
     * Обновить тип договора смены
     */
    public function updateContractType()
    {
        $contractType = $this->determineContractType();
        if ($contractType) {
            $this->contract_type_id = $contractType->id;
        }
        return $contractType;
    }
}
