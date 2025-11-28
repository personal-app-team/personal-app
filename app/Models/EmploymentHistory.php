<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmploymentHistory extends Model
{
    use HasFactory;

    protected $table = 'employment_history';

    protected $fillable = [
        'user_id',
        'employment_form',
        'department_id', 
        'position',
        'start_date',
        'end_date',
        'termination_reason',
        'termination_date',
        'contract_type_id',
        'tax_status_id',
        'payment_type',
        'salary_amount',
        'has_overtime',
        'overtime_rate',
        'work_schedule',
        'primary_specialty_id',
        'notes',
        'created_by_id',
        'position_change_request_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'termination_date' => 'date',
        'salary_amount' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'has_overtime' => 'boolean',
    ];

    // === СВЯЗИ ДЛЯ СИСТЕМЫ ПОДБОРА ПЕРСОНАЛА ===

    public function hiringDecision()
    {
        return $this->belongsTo(HiringDecision::class);
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===

    public function getIsActiveAttribute()
    {
        return is_null($this->end_date);
    }

    public function getDurationInMonthsAttribute()
    {
        $end = $this->end_date ?: now();
        return $this->start_date->diffInMonths($end);
    }

    // === ОСНОВНЫЕ СВЯЗИ ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function taxStatus()
    {
        return $this->belongsTo(TaxStatus::class);
    }

    public function primarySpecialty()
    {
        return $this->belongsTo(Specialty::class, 'primary_specialty_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function positionChangeRequest()
    {
        return $this->belongsTo(PositionChangeRequest::class);
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->whereNull('end_date');
    }

    public function scopeHistorical($query)
    {
        return $query->whereNotNull('end_date');
    }

    public function isActive()
    {
        return is_null($this->end_date);
    }

    public function getDuration()
    {
        $end = $this->end_date ? Carbon::parse($this->end_date) : now();
        return Carbon::parse($this->start_date)->diff($end);
    }

    public function getFormattedDuration()
    {
        $duration = $this->getDuration();
        return $duration->format('%y лет, %m месяцев, %d дней');
    }
}
