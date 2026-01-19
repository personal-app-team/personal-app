<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AssignmentDateValidation implements Rule
{
    protected $allowAdmin;
    protected $checkWorkRequestDate;
    protected $user;
    
    public function __construct($allowAdmin = true, $checkWorkRequestDate = null)
    {
        $this->allowAdmin = $allowAdmin;
        $this->checkWorkRequestDate = $checkWorkRequestDate;
        $this->user = Auth::user();
    }
    
    public function passes($attribute, $value)
    {
        // Если нет пользователя (например, в консоли)
        if (!$this->user) {
            return true;
        }
        
        // Определяем, какую дату проверяем
        $dateToCheck = $this->checkWorkRequestDate ?? $value;
        
        // Если дата не указана - пропускаем
        if (!$dateToCheck) {
            return true;
        }
        
        $date = Carbon::parse($dateToCheck);
        
        // 1. Админы могут создавать на любые даты (если разрешено)
        if ($this->allowAdmin && $this->user->hasRole('admin')) {
            return true;
        }
        
        // 2. Исполнители могут подтверждать назначения на любые даты
        // (они не создают назначения, а подтверждают существующие)
        if ($this->user->hasRole('executor')) {
            return true;
        }
        
        // 3. Инициаторы и диспетчеры - только текущие и будущие даты
        if ($this->user->hasAnyRole(['initiator', 'dispatcher'])) {
            return !$date->isPast() || $date->isToday();
        }
        
        // 4. Остальные роли (HR, manager и т.д.) - по умолчанию разрешаем
        // (они обычно не создают назначения)
        return true;
    }
    
    public function message()
    {
        if ($this->allowAdmin && $this->user && $this->user->hasRole('admin')) {
            return 'Администратор может создавать назначения на любые даты.';
        }
        
        return 'Вы не можете создавать назначения на прошедшие даты. Только администратор может сделать исключение.';
    }
}
