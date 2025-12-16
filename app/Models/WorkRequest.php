<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;

class WorkRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    // Статусы заявок
    const STATUS_PUBLISHED = 'published';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_CLOSED = 'closed';
    const STATUS_NO_SHIFTS = 'no_shifts';
    const STATUS_WORKING = 'working';
    const STATUS_UNCLOSED = 'unclosed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Типы персонала
    const PERSONNEL_OUR_STAFF = 'our_staff';
    const PERSONNEL_CONTRACTOR = 'contractor';

    protected $fillable = [
        'request_number',
        'external_number',
        'initiator_id',
        'brigadier_id',
        'workers_count',
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
        'work_date',
        'start_time',
        'estimated_duration_minutes',
        'status',
        'dispatcher_id',
        'additional_info',
        'desired_workers',
        'total_worked_hours',
        'published_at',
        'staffed_at',
        'completed_at',
    ];

    protected $casts = [
        'workers_count' => 'integer',
        'work_date' => 'date',
        'start_time' => 'datetime',
        'estimated_duration_minutes' => 'integer',
        'total_worked_hours' => 'decimal:2',
        'published_at' => 'datetime',
        'staffed_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_custom_address' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workRequest) {
            // Генерация номера заявки
            if (!$workRequest->request_number) {
                $workRequest->request_number = $workRequest->generateRequestNumber();
            }

            // Статус по умолчанию
            if (!$workRequest->status) {
                $workRequest->status = self::STATUS_PUBLISHED;
                $workRequest->published_at = now();
            }
        });

        static::saving(function ($workRequest) {
            // Валидация бизнес-правил
            $workRequest->validateBusinessRules();
        });
    }

    /**
     * Генерация номера заявки
     */
    public function generateRequestNumber(): string
    {
        $prefix = $this->getRequestNumberPrefix();
        $year = now()->year;
        
        // Находим последний номер за год
        $lastNumber = self::whereYear('created_at', $year)
            ->where('request_number', 'LIKE', "WR-{$prefix}-{$year}-%")
            ->orderBy('request_number', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastNumber) {
            preg_match('/-(\d+)$/', $lastNumber->request_number, $matches);
            $sequence = ($matches[1] ?? 0) + 1;
        }
        
        return sprintf("WR-%s-%d-%04d", $prefix, $year, $sequence);
    }

    /**
     * Получить префикс для номера заявки
     */
    private function getRequestNumberPrefix(): string
    {
        if ($this->personnel_type === self::PERSONNEL_CONTRACTOR && $this->contractor_id) {
            // Для подрядчиков - contractor_code
            $contractor = Contractor::find($this->contractor_id);
            return $contractor->contractor_code ?? 'CNTR';
        }
        
        // Для наших исполнителей - префикс категории
        $category = Category::find($this->category_id);
        return $category->prefix ?? 'GEN';
    }

    /**
     * Валидация бизнес-правил
     */
    public function validateBusinessRules(): void
    {
        // Правило 1: Один тип персонала
        if ($this->personnel_type === self::PERSONNEL_CONTRACTOR && !$this->contractor_id) {
            throw new \Exception('Для заявок на подрядчика должен быть указан подрядчик');
        }

        if ($this->personnel_type === self::PERSONNEL_OUR_STAFF && $this->contractor_id) {
            throw new \Exception('Для заявок на наших исполнителей не должен быть указан подрядчик');
        }

        // Правило 2: Категория обязательна
        if (!$this->category_id) {
            throw new \Exception('Категория персонала обязательна');
        }

        // Правило 3: Бригадир или контактное лицо
        if (!$this->brigadier_id && !$this->contact_person) {
            throw new \Exception('Должен быть указан либо бригадир, либо контактное лицо');
        }

        // Правило 4: Дата работ обязательна
        if (!$this->work_date) {
            throw new \Exception('Дата работ обязательна');
        }

        // Правило 5: Адрес (официальный или кастомный)
        if (!$this->address_id && !$this->custom_address) {
            throw new \Exception('Должен быть указан либо официальный адрес, либо кастомный адрес');
        }

        // Правило 6: Проверка бригадира (если указан)
        if ($this->brigadier_id && $this->work_date) {
            $this->validateBrigadier();
        }

        if ($this->workers_count !== null && $this->workers_count <= 0) {
            throw new \Exception('Количество работников должно быть положительным числом');
        }
    }

    /**
     * Проверка, что пользователь назначен бригадиром на эту дату
     */
    public function validateBrigadier(): void
    {
        if (!$this->brigadier_id || !$this->work_date) {
            return;
        }

        $isBrigadier = Assignment::where('user_id', $this->brigadier_id)
            ->where('assignment_type', 'brigadier_schedule')
            ->whereDate('planned_date', $this->work_date)
            ->where('status', 'confirmed')
            ->exists();

        if (!$isBrigadier) {
            throw new \Exception(
                "Пользователь ID {$this->brigadier_id} не назначен бригадиром на {$this->work_date}"
            );
        }
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

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
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

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'request_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'work_request_id');
    }

    public function massPersonnelReports()
    {
        return $this->hasMany(MassPersonnelReport::class, 'request_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(WorkRequestStatus::class);
    }

    // === ACCESSORS ===
    public function getFinalAddressAttribute()
    {
        if ($this->is_custom_address && $this->custom_address) {
            return $this->custom_address;
        }

        return $this->address?->full_address;
    }

    public function getPersonnelTypeDisplayAttribute()
    {
        return match($this->personnel_type) {
            self::PERSONNEL_OUR_STAFF => 'Наши исполнители',
            self::PERSONNEL_CONTRACTOR => 'Подрядчик',
            default => 'Не указано'
        };
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            self::STATUS_PUBLISHED => 'Опубликована',
            self::STATUS_IN_PROGRESS => 'В работе у диспетчера',
            self::STATUS_CLOSED => 'Укомплектована',
            self::STATUS_NO_SHIFTS => 'Смены не созданы',
            self::STATUS_WORKING => 'В работе (смены открыты)',
            self::STATUS_UNCLOSED => 'Смены не закрыты вовремя',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_CANCELLED => 'Отменена',
            default => $this->status
        };
    }

    public function getPlannedEndTimeAttribute()
    {
        if (!$this->start_time || !$this->estimated_duration_minutes) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        return $start->addMinutes($this->estimated_duration_minutes);
    }

    public function getEstimatedHoursAttribute()
    {
        if (!$this->estimated_duration_minutes) {
            return 0;
        }

        return round($this->estimated_duration_minutes / 60, 2);
    }

    // === МЕТОДЫ ДЛЯ WORKFLOW ===
    public function takeInProgress()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'dispatcher_id' => auth()->id()
        ]);
    }

    public function markAsClosed()
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'staffed_at' => now()
        ]);
    }

    public function markAsWorking()
    {
        $this->update([
            'status' => self::STATUS_WORKING
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    // === РАСЧЕТНЫЕ МЕТОДЫ ===
    public function calculateTotalHours()
    {
        $shiftHours = $this->shifts()->sum('worked_minutes') / 60;
        $reportHours = $this->massPersonnelReports()->sum('total_hours');
        
        $this->total_worked_hours = $shiftHours + $reportHours;
        $this->save();
        
        return $this->total_worked_hours;
    }

    public function getAssignedWorkersCount()
    {
        $shiftWorkers = $this->shifts()->count();
        $reportWorkers = $this->massPersonnelReports()->sum('workers_count');
        
        return $shiftWorkers + $reportWorkers;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 
                'initiator_id', 
                'brigadier_id', 
                'contact_person',  // ← УБЕДИТЕСЬ ЧТО ДОБАВИЛИ!
                'dispatcher_id',
                'contractor_id', 
                'personnel_type', 
                'category_id', 
                'work_type_id',
                'work_date', 
                'start_time', 
                'estimated_duration_minutes',
                'workers_count',   // ← ДОБАВЛЯЕМ!
                'request_number', 
                'external_number',
                'address_id',
                'is_custom_address',
                'custom_address',
                'project_id',
                'purpose_id',
                'additional_info',
                'desired_workers',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Заявка на работы создана',
                    'updated' => 'Заявка на работы изменена',
                    'deleted' => 'Заявка на работы удалена',
                    'restored' => 'Заявка на работы восстановлена',
                    default => "Заявка {$eventName}",
                };
            });
    }
}
