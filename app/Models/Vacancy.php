<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'short_description',
        'employment_type',
        'department_id',
        'created_by_id',
        'status',
    ];

    protected $casts = [
        'employment_type' => 'string',
        'status' => 'string',
    ];

    // === СВЯЗИ ===

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function tasks()
    {
        return $this->hasMany(VacancyTask::class);
    }

    public function requirements()
    {
        return $this->hasMany(VacancyRequirement::class);
    }

    public function conditions()
    {
        return $this->hasMany(VacancyCondition::class);
    }

    public function recruitmentRequests()
    {
        return $this->hasMany(RecruitmentRequest::class);
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
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

    public function close()
    {
        $this->update(['status' => 'closed']);
    }

    public function reopen()
    {
        $this->update(['status' => 'active']);
    }

    public function getActiveRecruitmentRequestsCount()
    {
        return $this->recruitmentRequests()
            ->whereIn('status', ['new', 'assigned', 'in_progress'])
            ->count();
    }
}
