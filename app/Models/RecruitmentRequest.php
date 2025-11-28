<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RecruitmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'user_id',
        'department_id',
        'comment',
        'required_count',
        'employment_type',
        'start_date',
        'end_date',
        'hr_responsible_id',
        'status',
        'urgency',
        'deadline',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'deadline' => 'date',
        'employment_type' => 'string',
        'status' => 'string',
        'urgency' => 'string',
    ];

    // === СВЯЗИ ===

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function hrResponsible()
    {
        return $this->belongsTo(User::class, 'hr_responsible_id');
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    // === SCOPES ===

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeUrgent($query)
    {
        return $query->where('urgency', 'high');
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereIn('status', ['new', 'assigned', 'in_progress']);
    }

    // === БИЗНЕС-ЛОГИКА ===

    public function assignToHr(User $hrUser)
    {
        $this->update([
            'hr_responsible_id' => $hrUser->id,
            'status' => 'assigned',
        ]);
    }

    public function startProgress()
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isOverdue()
    {
        return $this->deadline < now() && in_array($this->status, ['new', 'assigned', 'in_progress']);
    }

    public function getDaysUntilDeadline()
    {
        return now()->diffInDays($this->deadline, false);
    }

    public function getCandidatesCountByStatus()
    {
        return $this->candidates()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
