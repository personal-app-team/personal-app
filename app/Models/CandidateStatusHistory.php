<?php
// app/Models/CandidateStatusHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'status',
        'comment',
        'changed_by_id',
        'previous_status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // === СВЯЗИ ===

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'new' => 'Новый',
            'contacted' => 'Связались',
            'sent_for_approval' => 'Отправлен на согласование',
            'approved_for_interview' => 'Одобрен для собеседования',
            'in_reserve' => 'В резерве',
            'rejected' => 'Отклонен',
            default => $this->status
        };
    }

    public function getPreviousStatusDisplayAttribute()
    {
        return match($this->previous_status) {
            'new' => 'Новый',
            'contacted' => 'Связались',
            'sent_for_approval' => 'Отправлен на согласование',
            'approved_for_interview' => 'Одобрен для собеседования',
            'in_reserve' => 'В резерве',
            'rejected' => 'Отклонен',
            default => $this->previous_status ?? '—'
        };
    }
}
