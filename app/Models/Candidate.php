<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'recruitment_request_id',
        'phone',
        'email',
        'resume_path',
        'source',
        'first_contact_date',
        'hr_contact_date',
        'expert_id',
        'status',
        'notes',
        'current_stage',
        'created_by_id',
    ];

    protected $casts = [
        'first_contact_date' => 'date',
        'hr_contact_date' => 'date',
    ];

    // === СВЯЗИ ===

    public function recruitmentRequest()
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function candidateStatusHistory()
    {
        return $this->hasMany(CandidateStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function candidateDecisions()
    {
        return $this->hasMany(CandidateDecision::class)->orderBy('decision_date', 'desc');
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class)->orderBy('scheduled_at', 'desc');
    }

    public function hiringDecisions()
    {
        return $this->hasMany(HiringDecision::class)->orderBy('created_at', 'desc');
    }

    // === SCOPES ===

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    public function scopeSentForApproval($query)
    {
        return $query->where('status', 'sent_for_approval');
    }

    public function scopeApprovedForInterview($query)
    {
        return $query->where('status', 'approved_for_interview');
    }

    // === БИЗНЕС-ЛОГИКА ===

    public function markAsContacted()
    {
        $previousStatus = $this->status;
        
        $this->update([
            'status' => 'contacted',
            'hr_contact_date' => now(),
        ]);

        // Создаем запись в истории
        $this->candidateStatusHistory()->create([
            'status' => 'contacted',
            'comment' => 'Кандидат отмечен как связанный',
            'changed_by_id' => auth()->id(),
            'previous_status' => $previousStatus,
        ]);
    }

    public function sendForApproval()
    {
        $previousStatus = $this->status;
        
        $this->update(['status' => 'sent_for_approval']);

        $this->candidateStatusHistory()->create([
            'status' => 'sent_for_approval',
            'comment' => 'Кандидат отправлен на согласование заявителю',
            'changed_by_id' => auth()->id(),
            'previous_status' => $previousStatus,
        ]);
    }

    public function approveForInterview()
    {
        $previousStatus = $this->status;
        
        $this->update(['status' => 'approved_for_interview']);

        $this->candidateStatusHistory()->create([
            'status' => 'approved_for_interview',
            'comment' => 'Кандидат одобрен для собеседования',
            'changed_by_id' => auth()->id(),
            'previous_status' => $previousStatus,
        ]);
    }

    public function reject()
    {
        $previousStatus = $this->status;
        
        $this->update(['status' => 'rejected']);

        $this->candidateStatusHistory()->create([
            'status' => 'rejected',
            'comment' => 'Кандидат отклонен',
            'changed_by_id' => auth()->id(),
            'previous_status' => $previousStatus,
        ]);
    }

    public function reserve()
    {
        $previousStatus = $this->status;
        
        $this->update(['status' => 'in_reserve']);

        $this->candidateStatusHistory()->create([
            'status' => 'in_reserve',
            'comment' => 'Кандидат перемещен в резерв',
            'changed_by_id' => auth()->id(),
            'previous_status' => $previousStatus,
        ]);
    }

    // === ДОБАВЛЯЕМ МЕТОД ДЛЯ СОЗДАНИЯ РЕШЕНИЯ ЗАЯВИТЕЛЯ ===

    public function createDecision(array $data)
    {
        $decision = $this->candidateDecisions()->create([
            'user_id' => $data['user_id'],
            'decision' => $data['decision'],
            'comment' => $data['comment'],
            'decision_date' => $data['decision_date'] ?? now(),
        ]);

        // Обновляем статус кандидата на основе решения
        $newStatus = match($data['decision']) {
            'reject' => 'rejected',
            'reserve' => 'in_reserve',
            'interview' => 'approved_for_interview',
            'other_vacancy' => 'in_reserve', // временно в резерв
            default => $this->status
        };

        $previousStatus = $this->status;
        $this->update(['status' => $newStatus]);

        // Создаем запись в истории
        $this->candidateStatusHistory()->create([
            'status' => $newStatus,
            'comment' => "Решение заявителя: {$decision->decision_display}. " . ($data['comment'] ?? ''),
            'changed_by_id' => $data['user_id'],
            'previous_status' => $previousStatus,
        ]);

        return $decision;
    }

    // === ДОБАВЛЯЕМ МЕТОДЫ ДЛЯ ИНТЕРВЬЮ И РЕШЕНИЙ О ПРИЕМЕ ===

    public function scheduleInterview(array $data)
    {
        $interview = $this->interviews()->create([
            'scheduled_at' => $data['scheduled_at'],
            'interview_type' => $data['interview_type'],
            'location' => $data['location'] ?? null,
            'interviewer_id' => $data['interviewer_id'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'created_by_id' => auth()->id(),
        ]);

        // Обновляем статус кандидата
        $this->update(['status' => 'approved_for_interview']);

        // Создаем запись в истории
        $this->candidateStatusHistory()->create([
            'status' => 'approved_for_interview',
            'comment' => "Запланировано собеседование: {$interview->scheduled_at->format('d.m.Y H:i')}",
            'changed_by_id' => auth()->id(),
            'previous_status' => $this->status,
        ]);

        return $interview;
    }

    public function createHiringDecision(array $data)
    {
        $decision = $this->hiringDecisions()->create([
            'position_title' => $data['position_title'],
            'specialty_id' => $data['specialty_id'] ?? null,
            'employment_type' => $data['employment_type'],
            'payment_type' => $data['payment_type'],
            'payment_value' => $data['payment_value'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'decision_makers' => $data['decision_makers'] ?? [auth()->id()],
            'approved_by_id' => $data['approved_by_id'] ?? auth()->id(),
            'trainee_period_days' => $data['trainee_period_days'] ?? null,
        ]);

        return $decision;
    }
}
