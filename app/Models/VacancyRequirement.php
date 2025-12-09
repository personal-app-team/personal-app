<?php
// app/Models/VacancyRequirement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VacancyRequirement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'vacancy_id',
        'description', 
        'mandatory',
        'order',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'mandatory', 'order', 'vacancy_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Требование вакансии создано',
                    'updated' => 'Требование вакансии изменено',
                    'deleted' => 'Требование вакансии удалено',
                    default => "Требование вакансии {$eventName}",
                };
            })
            ->useLogName('vacancy_requirement');
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}