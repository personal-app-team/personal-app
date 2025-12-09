<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VacancyTask extends Model
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
                    'created' => 'Задача вакансии создана',
                    'updated' => 'Задача вакансии изменена',
                    'deleted' => 'Задача вакансии удалена',
                    default => "Задача вакансии {$eventName}",
                };
            })
            ->useLogName('vacancy_task');
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
