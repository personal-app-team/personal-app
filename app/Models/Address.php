<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_name',
        'full_address', 
        'location_type',
        'is_template'  // добавляем
    ];

    protected $casts = [
        'is_template' => 'boolean'  // добавляем
    ];

    // Метод для правил валидации
    public static function rules($id = null, $isTemplate = null)
    {
        $rule = Rule::unique('addresses', 'full_address');
        
        if ($id) {
            $rule = $rule->ignore($id);
        }
        
        if ($isTemplate !== null) {
            $rule = $rule->where('is_template', $isTemplate);
        }
        
        return ['required', 'string', 'max:1000', $rule];
    }

    // Аксессоры для обратной совместимости
    public function getNameAttribute()
    {
        return $this->short_name;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['short_name'] = $value;
    }

    public function getDescriptionAttribute()
    {
        return $this->location_type;
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['location_type'] = $value;
    }

    // Отношения
    public function projects()
    {
        return $this->belongsToMany(Project::class)
                    ->using(AddressProject::class);
    }

    public function addressRules()
    {
        return $this->hasMany(PurposeAddressRule::class);
    }

    public function workRequests()
    {
        return $this->hasMany(WorkRequest::class);
    }

    // Scope для шаблонов
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    // Scope для обычных адресов
    public function scopeActual($query)
    {
        return $query->where('is_template', false);
    }
}
