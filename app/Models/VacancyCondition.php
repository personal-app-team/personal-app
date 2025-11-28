<?php
// app/Models/VacancyCondition.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacancyCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'description',
        'order',
    ];

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
