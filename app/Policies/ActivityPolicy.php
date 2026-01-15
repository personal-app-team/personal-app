<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_activity_logs');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasPermissionTo('view_activity_logs');
    }

    public function create(User $user): bool
    {
        return false; // Логи нельзя создавать вручную
    }

    public function update(User $user, Activity $activity): bool
    {
        return false; // Логи нельзя редактировать
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false; // Логи нельзя удалять через интерфейс
    }

    public function restore(User $user, Activity $activity): bool
    {
        return false; // Логи нельзя восстанавливать
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return false; // Логи нельзя удалять навсегда
    }
    
    public function deleteAny(User $user): bool
    {
        return false; // Запрещаем массовое удаление
    }
    
    public function restoreAny(User $user): bool
    {
        return false; // Запрещаем массовое восстановление
    }
    
    public function replicate(User $user, Activity $activity): bool
    {
        return false; // Запрещаем репликацию
    }
    
    public function reorder(User $user): bool
    {
        return false; // Запрещаем изменение порядка
    }
}
