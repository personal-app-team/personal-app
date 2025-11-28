<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'position_title',
        'specialty_id',
        'employment_type',
        'payment_type',
        'payment_value',
        'start_date',
        'end_date',
        'decision_makers',
        'approved_by_id',
        'status',
        'trainee_period_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'decision_makers' => 'array',
        'payment_value' => 'decimal:2',
    ];

    // === СВЯЗИ ===

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    // === SCOPES ===

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeTemporary($query)
    {
        return $query->where('employment_type', 'temporary');
    }

    public function scopePermanent($query)
    {
        return $query->where('employment_type', 'permanent');
    }

    // === БИЗНЕС-ЛОГИКА ===

    public function approve()
    {
        $this->update(['status' => 'approved']);

        // Создаем запись в EmploymentHistory
        $this->createEmploymentHistory();

        // Создаем пользователя в системе (если нужно)
        $this->createSystemUser();
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    protected function createEmploymentHistory()
    {
        // Определяем форму трудоустройства
        $employmentForm = $this->employment_type === 'temporary' ? 'temporary' : 'permanent';

        // Создаем запись в EmploymentHistory
        EmploymentHistory::create([
            'user_id' => $this->candidate->user_id, // Предполагаем, что кандидат стал пользователем
            'employment_form' => $employmentForm,
            'department_id' => $this->candidate->recruitmentRequest->department_id,
            'position' => $this->position_title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'contract_type_id' => $this->determineContractType(),
            'tax_status_id' => $this->determineTaxStatus(),
            'payment_type' => $this->payment_type,
            'salary_amount' => $this->payment_value,
            'work_schedule' => '5/2', // По умолчанию
            'primary_specialty_id' => $this->specialty_id,
            'created_by_id' => $this->approved_by_id,
        ]);
    }

    protected function createSystemUser()
    {
        // Логика создания пользователя из кандидата
        // Можно интегрировать с существующей системой создания пользователей
    }

    protected function determineContractType()
    {
        // Логика определения типа договора на основе employment_type и других параметров
        return null; // Заглушка
    }

    protected function determineTaxStatus()
    {
        // Логика определения налогового статуса
        return null; // Заглушка
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===

    public function getEmploymentTypeDisplayAttribute()
    {
        return match($this->employment_type) {
            'temporary' => 'Временный',
            'permanent' => 'Постоянный',
            default => $this->employment_type
        };
    }

    public function getPaymentTypeDisplayAttribute()
    {
        return match($this->payment_type) {
            'rate' => 'Ставка',
            'salary' => 'Оклад',
            'combined' => 'Комбинированный',
            default => $this->payment_type
        };
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'draft' => 'Черновик',
            'approved' => 'Утверждено',
            'rejected' => 'Отклонено',
            default => $this->status
        };
    }

    public function getDecisionMakersListAttribute()
    {
        if (empty($this->decision_makers)) {
            return collect();
        }

        // Проверяем, является ли decision_makers массивом
        $userIds = is_array($this->decision_makers) ? $this->decision_makers : json_decode($this->decision_makers, true);
        
        if (empty($userIds)) {
            return collect();
        }

        return User::whereIn('id', $userIds)->get();
    }
}
