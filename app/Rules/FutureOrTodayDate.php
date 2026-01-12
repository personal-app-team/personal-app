<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class FutureOrTodayDate implements Rule
{
    public function passes($attribute, $value)
    {
        $user = Auth::user();
        
        if (!$user) {
            return true;
        }
        
        // Если дата не указана, пропускаем
        if (!$value) {
            return true;
        }
        
        // Для инициатора и диспетчера - только текущие и будущие даты
        if ($user->hasAnyRole(['initiator', 'dispatcher'])) {
            return strtotime($value) >= strtotime('today');
        }
        
        return true;
    }
    
    public function message()
    {
        return 'Вы не можете создавать назначения на прошедшие даты.';
    }
}
