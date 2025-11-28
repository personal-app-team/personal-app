<?php
// app/Models/CandidateDecision.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'user_id',
        'decision',
        'comment',
        'decision_date',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'decision' => 'string',
    ];

    // === СВЯЗИ ===

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===

    public function getDecisionDisplayAttribute()
    {
        return match($this->decision) {
            'reject' => 'Отклонить',
            'reserve' => 'В резерв',
            'interview' => 'Собеседование',
            'other_vacancy' => 'Другая вакансия',
            default => $this->decision
        };
    }
}
