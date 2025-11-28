<?php
// app/Models/Interview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'scheduled_at',
        'interview_type',
        'location',
        'interviewer_id',
        'status',
        'result',
        'feedback',
        'notes',
        'duration_minutes',
        'created_by_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    // === СВЯЗИ ===

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // === SCOPES ===

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_at', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    // === БИЗНЕС-ЛОГИКА ===

    public function complete($result, $feedback = null)
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
            'feedback' => $feedback,
        ]);

        // Автоматически создаем запись в истории статусов кандидата
        $statusMap = [
            'hire' => 'approved_for_interview', // Будет изменен на следующий этап
            'reject' => 'rejected',
            'reserve' => 'in_reserve',
            'other_vacancy' => 'in_reserve',
            'trainee' => 'in_reserve', // Будет создана TraineeRequest
        ];

        if (isset($statusMap[$result])) {
            $this->candidate->candidateStatusHistory()->create([
                'status' => $statusMap[$result],
                'comment' => "Результат собеседования: {$this->getResultDisplayAttribute()}. " . ($feedback ? "Отзыв: {$feedback}" : ''),
                'changed_by_id' => $this->interviewer_id,
                'previous_status' => $this->candidate->status,
            ]);

            $this->candidate->update(['status' => $statusMap[$result]]);
        }

        // Если результат "trainee", создаем TraineeRequest
        if ($result === 'trainee') {
            // Здесь будет интеграция с существующей системой TraineeRequest
        }
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===

    public function getInterviewTypeDisplayAttribute()
    {
        return match($this->interview_type) {
            'technical' => 'Техническое',
            'managerial' => 'С руководителем',
            'cultural' => 'Культурное',
            'combined' => 'Комбинированное',
            default => $this->interview_type
        };
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'scheduled' => 'Запланировано',
            'completed' => 'Завершено',
            'cancelled' => 'Отменено',
            default => $this->status
        };
    }

    public function getResultDisplayAttribute()
    {
        return match($this->result) {
            'hire' => 'Нанять',
            'reject' => 'Отклонить',
            'reserve' => 'В резерв',
            'other_vacancy' => 'Другая вакансия',
            'trainee' => 'Стажировка',
            default => '—'
        };
    }

    public function getIsUpcomingAttribute()
    {
        return $this->status === 'scheduled' && $this->scheduled_at > now();
    }

    public function getIsPastAttribute()
    {
        return $this->scheduled_at < now();
    }
}
