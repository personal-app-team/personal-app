<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TraineeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'candidate_name',
        'candidate_email', 
        'candidate_position',
        'specialty_id',
        'is_paid',
        'proposed_rate',
        'duration_days',
        'status',
        'hr_comment',
        'hr_user_id',
        'hr_approved_at',
        'manager_comment',
        'manager_user_id',
        'manager_approved_at',
        'start_date',
        'end_date',
        'trainee_user_id',
        'decision_required_at',
        'blocked_at',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'proposed_rate' => 'decimal:2',
        'hr_approved_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'decision_required_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    // === СВЯЗИ ===

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hrUser()
    {
        return $this->belongsTo(User::class, 'hr_user_id');
    }

    public function managerUser()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function traineeUser()
    {
        return $this->belongsTo(User::class, 'trainee_user_id');
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    // === SCOPES ===

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeHrApproved($query)
    {
        return $query->where('status', 'hr_approved');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('end_date', '<=', now()->addDay())
                    ->where('status', 'active');
    }

    public function scopeRequiringDecision($query)
    {
        return $query->where('decision_required_at', '<=', now())
                    ->where('status', 'active');
    }

    // === МЕТОДЫ ===

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isHrApproved()
    {
        return $this->status === 'hr_approved';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function canBeApprovedByHr()
    {
        return $this->isPending();
    }

    public function canBeApprovedByManager()
    {
        return $this->isHrApproved();
    }
}
