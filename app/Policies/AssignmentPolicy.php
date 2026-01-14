<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // ТОЛЬКО бизнес-логика, без проверки разрешений!
        return true; // Filament Shield уже проверил view_any_assignment
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        // Инициатор может видеть только плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return $assignment->assignment_type === 'brigadier_schedule';
        }
        
        // Исполнитель может видеть только свои назначения
        if ($user->hasRole('executor')) {
            return $assignment->user_id === $user->id;
        }
        
        return true; // Для остальных ролей
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Инициатор может создавать только плановые назначения бригадира
        // Фильтрация по типу будет в форме Filament
        return true; // Filament Shield уже проверил create_assignment
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        // Инициатор может редактировать только:
        // 1. Плановые назначения бригадира
        // 2. Только в статусе pending
        if ($user->hasRole('initiator')) {
            return $assignment->assignment_type === 'brigadier_schedule' &&
                   $assignment->status === 'pending';
        }
        
        // Исполнитель может обновлять только свои назначения
        if ($user->hasRole('executor')) {
            return $assignment->user_id === $user->id &&
                   $assignment->status === 'pending';
        }
        
        return true; // Для диспетчера, админа и др.
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        // Инициатор НЕ может удалять назначения
        if ($user->hasRole('initiator')) {
            return false;
        }
        
        // Исполнитель НЕ может удалять назначения
        if ($user->hasRole('executor')) {
            return false;
        }
        
        return true; // Для диспетчера, админа
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        // Инициатор и исполнитель не могут массово удалять
        if ($user->hasAnyRole(['initiator', 'executor'])) {
            return false;
        }
        
        return true; // Для диспетчера, админа
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Assignment $assignment): bool
    {
        // Только администраторы могут окончательно удалять
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        // Только администраторы могут массово окончательно удалять
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Assignment $assignment): bool
    {
        // Только администраторы и диспетчеры могут восстанавливать
        return $user->hasAnyRole(['admin', 'dispatcher']);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        // Только администраторы и диспетчеры могут массово восстанавливать
        return $user->hasAnyRole(['admin', 'dispatcher']);
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Assignment $assignment): bool
    {
        // Инициатор и исполнитель не могут копировать
        if ($user->hasAnyRole(['initiator', 'executor'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        // Только администраторы могут менять порядок
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can confirm the assignment.
     */
    public function confirm(User $user, Assignment $assignment): bool
    {
        // Исполнитель может подтверждать только свои pending назначения
        if ($user->hasRole('executor')) {
            return $assignment->user_id === $user->id 
                && $assignment->status === 'pending';
        }
        
        // Диспетчер и администратор могут подтверждать любые pending
        if ($user->hasAnyRole(['dispatcher', 'admin'])) {
            return $assignment->status === 'pending';
        }
        
        // Остальные роли не могут подтверждать
        return false;
    }
    
    /**
     * Determine whether the user can reject the assignment.
     */
    public function reject(User $user, Assignment $assignment): bool
    {
        // Исполнитель может отклонить только свои pending назначения
        if ($user->hasRole('executor')) {
            return $assignment->user_id === $user->id 
                && $assignment->status === 'pending';
        }
        
        // Диспетчер и администратор могут отклонять любые pending
        if ($user->hasAnyRole(['dispatcher', 'admin'])) {
            return $assignment->status === 'pending';
        }
        
        // Остальные роли не могут отклонять
        return false;
    }
}
