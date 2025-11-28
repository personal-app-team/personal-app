<?php
// app/Models/VacancyRequirement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacancyRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacancy_id',
        'description', 
        'mandatory',
        'order',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
    ];

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}