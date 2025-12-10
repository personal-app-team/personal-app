<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AddressProject extends Pivot
{
    /**
     * Имя таблицы
     *
     * @var string
     */
    protected $table = 'address_project';
    
    /**
     * Поля для массового заполнения
     *
     * @var array
     */
    protected $fillable = [
        'address_id',
        'project_id',
    ];
    
    /**
     * Отключаем временные метки для pivot-таблицы
     *
     * @var bool
     */
    public $timestamps = false;
}
