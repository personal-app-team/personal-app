<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VacancyCondition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'vacancy_id',
        'description',
        'order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'order', 'vacancy_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Условие вакансии создано',
                    'updated' => 'Условие вакансии изменено',
                    'deleted' => 'Условие вакансии удалено',
                    default => "Условие вакансии {$eventName}",
                };
            })
            ->useLogName('vacancy_condition');
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
