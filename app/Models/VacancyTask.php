<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacancyTask extends Model
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
