<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vacancy extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'employment_type', 'department_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Вакансия создана',
                    'updated' => 'Вакансия изменена',
                    'deleted' => 'Вакансия удалена',
                    default => "Вакансия {$eventName}",
                };
            })
            ->useLogName('vacancy');
    }

    // === СВЯЗИ ===

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function recruitmentRequests(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class);
    }

    public function vacancyConditions(): HasMany
    {
        return $this->hasMany(VacancyCondition::class);
    }

    public function vacancyRequirements(): HasMany
    {
        return $this->hasMany(VacancyRequirement::class);
    }

    public function vacancyTasks(): HasMany
    {
        return $this->hasMany(VacancyTask::class);
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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getActiveRecruitmentRequestsCount()
    {
        return $this->recruitmentRequests()
            ->whereIn('status', ['new', 'assigned', 'in_progress'])
            ->count();
    }
}
