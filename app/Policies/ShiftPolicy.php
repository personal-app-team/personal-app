<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Shift;
use Illuminate\Auth\Access\Response;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        // Админ, manager, viewer, dispatcher видят все смены
        if ($user->hasAnyRole(['admin', 'manager', 'viewer', 'dispatcher'])) {
            return true;
        }
        
        // Инициатор видит смены, связанные с его плановыми назначениями
        if ($user->hasRole('initiator')) {
            return $user->can('view_any_shift');
        }
        
        // Исполнитель, contractor_executor, trainee видят только свои
        return $user->hasAnyRole(['executor', 'contractor_executor', 'trainee']);
    }

    public function view(User $user, Shift $shift): bool
    {
        if ($user->hasAnyRole(['admin', 'manager', 'viewer', 'dispatcher'])) {
            return true;
        }
        
        if ($user->hasRole('initiator')) {
            // Инициатор видит смены по своим плановым назначениям
            return $shift->assignment?->created_by === $user->id;
        }
        
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->id === $shift->user_id;
        }
        
        return false;
    }

    public function create(User $user): bool
    {
        // Смены создаются автоматически или админом/диспетчером
        return $user->hasAnyRole(['admin', 'dispatcher']);
    }

    public function update(User $user, Shift $shift): bool
    {
        if ($user->hasRole('admin')) return true;
        
        if ($user->hasRole('dispatcher')) {
            return $shift->status === 'scheduled' || $shift->status === 'active';
        }
        
        // Исполнитель может обновлять только свои активные смены
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->id === $shift->user_id && 
                   $shift->status === 'active';
        }
        
        return false;
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->hasRole('admin');
    }

    // Действия для смены
    public function start(User $user, Shift $shift): bool
    {
        // Исполнитель может начать только свою запланированную смену
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->id === $shift->user_id &&
                   $shift->status === 'scheduled' &&
                   $shift->planned_date == now()->toDateString();
        }
        
        return false;
    }

    public function end(User $user, Shift $shift): bool
    {
        // Исполнитель может завершить только свою активную смену
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->id === $shift->user_id &&
                   $shift->status === 'active';
        }
        
        return false;
    }

    public function approve(User $user, Shift $shift): bool
    {
        // Диспетчер или админ могут утверждать завершенные смены
        return $user->hasAnyRole(['admin', 'dispatcher']) &&
               $shift->status === 'completed';
    }
}
