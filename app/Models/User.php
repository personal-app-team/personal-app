<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'email',
        'password',
        'phone',
        'telegram_id',
        'contractor_id',
        'notes',
        'user_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // === НОВЫЕ СВЯЗИ ===

    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class)->orderBy('start_date', 'desc');
    }

    public function currentEmployment()
    {
        return $this->hasOne(EmploymentHistory::class)->whereNull('end_date');
    }

    // === СУЩЕСТВУЮЩИЕ СВЯЗИ ===

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function managedContractor()
    {
        return $this->hasOne(Contractor::class, 'user_id');
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'user_specialties')
                    ->withPivot('base_hourly_rate')
                    ->withTimestamps();
    }

    public function initiatedRequests()
    {
        return $this->hasMany(WorkRequest::class, 'initiator_id');
    }

    public function brigadierRequests()
    {
        return $this->hasMany(WorkRequest::class, 'brigadier_id');
    }

    public function dispatcherRequests()
    {
        return $this->hasMany(WorkRequest::class, 'dispatcher_id');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function grantedInitiatorRights()
    {
        return $this->hasMany(InitiatorGrant::class, 'brigadier_id');
    }

    public function givenInitiatorRights()
    {
        return $this->hasMany(InitiatorGrant::class, 'initiator_id');
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ДЛЯ FILAMENT ===
    
    public function getExecutorTypeAttribute()
    {
        if (!$this->hasRole('executor')) {
            return null;
        }
        
        return $this->contractor_id ? 'contractor' : 'our';
    }

    public function setExecutorTypeAttribute($value)
    {
        if ($value === 'our') {
            $this->contractor_id = null;
        }
    }

    public function getFullNameAttribute()
    {
        $parts = array_filter([$this->surname, $this->name, $this->patronymic]);
        return implode(' ', $parts) ?: $this->name;
    }

    // === ОПРЕДЕЛЕНИЕ ТИПА ПОЛЬЗОВАТЕЛЯ ===
    
    public function isEmployee()
    {
        return $this->user_type === 'employee';
    }

    public function isContractor()
    {
        return $this->user_type === 'contractor';
    }

    public function isInitiator()
    {
        return $this->hasRole('initiator') && !$this->canHaveShifts();
    }

    public function isDispatcher()
    {
        return $this->hasRole('dispatcher') && !$this->canHaveShifts();
    }
    
    // User-представитель подрядчика (управляет компанией)
    public function isExternalContractor()
    {
        return $this->isContractor() && $this->hasRole('manager') && !$this->contractor_id;
    }

    public function isOurExecutor()
    {
        return $this->isEmployee() && $this->hasRole('executor');
    }

    public function isContractorExecutor()
    {
        return $this->isContractor() && $this->hasRole('executor') && $this->contractor_id;
    }

    public function isContractorManager()
    {
        return $this->isContractor() && $this->hasRole('manager') && !$this->contractor_id;
    }

    /**
     * Получить тип пользователя для отображения
     */
    public function getUserTypeDisplayAttribute(): string
    {
        if ($this->isExternalContractor()) return '👑 Подрядчик';
        if ($this->isOurExecutor()) return '👷 Наш исполнитель';
        if ($this->isContractorExecutor()) return '🏢 Исполнитель подрядчика';
        if ($this->isInitiator()) return '📋 Инициатор';
        if ($this->isDispatcher()) return '📞 Диспетчер';
        return '❓ Другое';
    }
    
    // === БИЗНЕС-ЛОГИКА ===
    
    public function canCreateWorkRequests()
    {
        return $this->hasAnyRole(['initiator', 'dispatcher']);
    }
    
    public function canHaveShifts()
    {
        return $this->hasRole('executor');
    }
    
    public function isBrigadier($date = null)
    {
        $date = $date ? Carbon::parse($date)->format('Y-m-d') : now()->format('Y-m-d');
        
        return $this->brigadierAssignments()
            ->whereDate('planned_date', $date)
            ->where('status', 'confirmed')
            ->exists();
    }

    public function canCreateRequestsAsBrigadier($date = null)
    {
        $date = $date ?: now()->format('Y-m-d');
        
        return $this->hasRole('initiator') && $this->isBrigadier($date);
    }

    public function getBrigadierInitiatorDates()
    {
        if (!$this->hasRole('initiator')) {
            return [];
        }
        
        return $this->getBrigadierDates();
    }

    public function canCreateRequestsAsBrigadierOnAnyDate()
    {
        return $this->hasRole('initiator') && $this->getBrigadierInitiatorDates()->isNotEmpty();
    }

    public function getBrigadierDates()
    {
        return $this->brigadierAssignments()
            ->where('status', 'confirmed')
            ->pluck('planned_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();
    }

    public function getExecutorRole($date = null)
    {
        $date = $date ?: now();
        
        if ($this->isBrigadier($date)) {
            return $this->canCreateRequestsAsBrigadier($date) 
                ? 'brigadier_with_rights' 
                : 'brigadier';
        }
        
        return 'executor';
    }

    public function getExecutorRoleDisplay($date = null)
    {
        $role = $this->getExecutorRole($date);
        $roles = [
            'executor' => 'Исполнитель',
            'brigadier' => 'Бригадир', 
            'brigadier_with_rights' => 'Бригадир (может создавать заявки)'
        ];
        return $roles[$role] ?? 'Исполнитель';
    }

    public function getManagedExecutors()
    {
        if (!$this->isExternalContractor()) {
            return collect();
        }
        
        return $this->managedContractor?->executors ?? collect();
    }

    public function getContractorShifts()
    {
        if (!$this->isExternalContractor()) {
            return collect();
        }
        
        return $this->managedContractor?->allShifts() ?? collect();
    }

    // === SCOPES ===
    
    public function scopeBrigadiers($query)
    {
        return $query->whereHas('brigadierAssignments', function($q) {
            $q->where('status', 'confirmed');
        });
    }

    public function scopeOurExecutors($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'executor');
        })->whereNull('contractor_id');
    }

    public function scopeContractorExecutors($query, $contractorId = null)
    {
        $query = $query->whereHas('roles', function($q) {
            $q->where('name', 'executor');
        })->whereNotNull('contractor_id');
        
        if ($contractorId) {
            $query->where('contractor_id', $contractorId);
        }
        
        return $query;
    }

    public function scopeAvailable($query, $date)
    {
        return $query->whereDoesntHave('shifts', function($q) use ($date) {
            $q->whereDate('work_date', $date)
              ->whereIn('status', ['active', 'completed']);
        });
    }

    // === RELATIONSHIPS FOR ASSIGNMENTS ===
    
    public function brigadierAssignments()
    {
        return $this->hasMany(Assignment::class, 'user_id')
                    ->where('assignment_type', 'brigadier_schedule');
    }

    public function workRequestAssignments()
    {
        return $this->hasMany(Assignment::class, 'user_id')
                    ->where('assignment_type', 'work_request');
    }

    public function activeAssignments()
    {
        return $this->hasMany(Assignment::class, 'user_id')
                    ->whereIn('status', ['pending', 'confirmed']);
    }

    // === ВАЛИДАЦИЯ И БИЗНЕС-ЛОГИКА ===
    
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // Автоматическая установка user_type на основе ролей
            if (is_null($user->user_type)) {
                $user->user_type = $user->determineUserType();
            }

            // Валидация для подрядчиков
            if ($user->isContractor()) {
                if ($user->hasRole('executor') && !$user->contractor_id) {
                    throw new \Exception('Исполнитель подрядчика должен быть привязан к компании-подрядчику');
                }
            }

            // Валидация для сотрудников
            if ($user->isEmployee() && $user->contractor_id) {
                throw new \Exception('Сотрудник не может быть привязан к подрядчику');
            }
        });
    }

    protected function determineUserType()
    {
        // Если пользователь имеет роль contractor (старая роль) или привязан к подрядчику, то contractor
        if ($this->hasRole('contractor') || $this->contractor_id) {
            return 'contractor';
        }

        // Иначе - employee
        return 'employee';
    }

    public function getExecutorTypeInfo(): array
    {
        if (!$this->hasRole('executor')) {
            return ['type' => 'not_executor', 'label' => 'Не исполнитель'];
        }

        if ($this->isOurExecutor()) {
            return [
                'type' => 'our',
                'label' => '👷 Наш исполнитель',
                'description' => 'Сотрудник компании',
                'contractor' => null,
                'employment_type' => $this->currentEmployment?->employment_form ?? 'unknown',
                'position' => $this->currentEmployment?->position ?? 'Не указана'
            ];
        }

        if ($this->isContractorExecutor()) {
            return [
                'type' => 'contractor',
                'label' => '🏢 Исполнитель подрядчика',
                'description' => 'Внешний специалист',
                'contractor' => $this->contractor,
                'contract_type' => $this->contractor?->contractType?->name,
                'tax_status' => $this->contractor?->taxStatus?->name
            ];
        }

        return ['type' => 'unknown', 'label' => 'Неизвестный тип'];
    }
}
