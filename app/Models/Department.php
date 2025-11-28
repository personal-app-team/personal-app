<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description', 
        'parent_id',
        'manager_id',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    // === СВЯЗИ ДЛЯ СИСТЕМЫ ПОДБОРА ПЕРСОНАЛА ===

    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    public function recruitmentRequests()
    {
        return $this->hasMany(RecruitmentRequest::class);
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithManager($query)
    {
        return $query->whereNotNull('manager_id');
    }
}
