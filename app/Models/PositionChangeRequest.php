<?php
// app/Models/PositionChangeRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_position',
        'new_position',
        'current_payment_type',
        'new_payment_type',
        'current_payment_value',
        'new_payment_value',
        'reason',
        'requested_by_id',
        'approved_by_id',
        'status',
        'effective_date',
        'notification_users',
    ];

    protected $casts = [
        'current_payment_value' => 'decimal:2',
        'new_payment_value' => 'decimal:2',
        'effective_date' => 'date',
        'notification_users' => 'array',
    ];

    // === СВЯЗИ ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function employmentHistory()
    {
        return $this->hasOne(EmploymentHistory::class, 'position_change_request_id');
    }

    // === SCOPES ===

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeEffectiveFrom($query, $date)
    {
        return $query->where('effective_date', '>=', $date);
    }

    // === БИЗНЕС-ЛОГИКА ===

    public function approve(User $approver)
    {
        $this->update([
            'status' => 'approved',
            'approved_by_id' => $approver->id,
        ]);

        // Создаем новую запись в EmploymentHistory
        $this->createEmploymentHistoryRecord();

        // Закрываем текущую активную запись EmploymentHistory
        $this->closeCurrentEmploymentHistory();

        // Отправляем уведомления
        $this->sendNotifications();
    }

    public function reject(User $rejecter)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by_id' => $rejecter->id,
        ]);

        $this->sendRejectionNotifications();
    }

    protected function createEmploymentHistoryRecord()
    {
        // Находим текущую активную запись EmploymentHistory
        $currentEmployment = $this->user->currentEmployment;

        if ($currentEmployment) {
            EmploymentHistory::create([
                'user_id' => $this->user_id,
                'employment_form' => $currentEmployment->employment_form,
                'department_id' => $currentEmployment->department_id,
                'position' => $this->new_position,
                'start_date' => $this->effective_date,
                'end_date' => null, // Новая активная запись
                'contract_type_id' => $currentEmployment->contract_type_id,
                'tax_status_id' => $currentEmployment->tax_status_id,
                'payment_type' => $this->new_payment_type,
                'salary_amount' => $this->new_payment_value,
                'has_overtime' => $currentEmployment->has_overtime,
                'overtime_rate' => $currentEmployment->overtime_rate,
                'work_schedule' => $currentEmployment->work_schedule,
                'primary_specialty_id' => $currentEmployment->primary_specialty_id,
                'notes' => "Изменение должности на основании запроса #{$this->id}. Причина: {$this->reason}",
                'position_change_request_id' => $this->id,
                'created_by_id' => $this->approved_by_id,
            ]);
        }
    }

    protected function closeCurrentEmploymentHistory()
    {
        $currentEmployment = $this->user->currentEmployment;
        
        if ($currentEmployment) {
            $currentEmployment->update([
                'end_date' => $this->effective_date->subDay(), // Заканчивается за день до вступления в силу
                'termination_reason' => 'transfer',
                'termination_date' => now(),
                'notes' => $currentEmployment->notes . "\nЗавершено в связи с изменением должности (запрос #{$this->id})"
            ]);
        }
    }

    protected function sendNotifications()
    {
        // Здесь будет логика отправки уведомлений указанным пользователям
        // Можно интегрировать с существующей системой уведомлений
    }

    protected function sendRejectionNotifications()
    {
        // Логика отправки уведомлений об отклонении
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'pending' => 'На рассмотрении',
            'approved' => 'Утверждено',
            'rejected' => 'Отклонено',
            default => $this->status
        };
    }

    public function getPaymentTypeDisplayAttribute($type)
    {
        return match($type) {
            'rate' => 'Ставка',
            'salary' => 'Оклад',
            'combined' => 'Комбинированный',
            default => $type
        };
    }

    public function getNotificationUsersListAttribute()
    {
        if (empty($this->notification_users)) {
            return collect();
        }

        return User::whereIn('id', $this->notification_users)->get();
    }

    public function getChangeSummaryAttribute()
    {
        $changes = [];

        if ($this->current_position !== $this->new_position) {
            $changes[] = "Должность: {$this->current_position} → {$this->new_position}";
        }

        if ($this->current_payment_type !== $this->new_payment_type) {
            $currentType = $this->getPaymentTypeDisplayAttribute($this->current_payment_type);
            $newType = $this->getPaymentTypeDisplayAttribute($this->new_payment_type);
            $changes[] = "Тип оплаты: {$currentType} → {$newType}";
        }

        if ($this->current_payment_value != $this->new_payment_value) {
            $changes[] = "Оплата: {$this->current_payment_value} → {$this->new_payment_value}";
        }

        return implode(', ', $changes);
    }
}
