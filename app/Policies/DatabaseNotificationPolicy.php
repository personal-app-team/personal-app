<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatabaseNotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Администраторы могут видеть все уведомления
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $user->hasPermissionTo('view_any_notification');
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        // Администраторы могут видеть все уведомления
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Остальные могут видеть только свои уведомления
        // И должны иметь разрешение view_notification
        return $user->hasPermissionTo('view_notification') && 
               $notification->notifiable_type === 'App\Models\User' &&
               $notification->notifiable_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Уведомления создаются только системой
        return false;
    }

    public function update(User $user, DatabaseNotification $notification): bool
    {
        // Уведомления не редактируются
        return false;
    }

    public function delete(User $user, DatabaseNotification $notification): bool
    {
        // Администраторы могут удалять любые уведомления
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Остальные могут удалять только свои уведомления
        return $user->hasPermissionTo('delete_notification') &&
               $notification->notifiable_type === 'App\Models\User' &&
               $notification->notifiable_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        // Администраторы могут удалять любые уведомления
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $user->hasPermissionTo('delete_any_notification');
    }

    public function restore(User $user, DatabaseNotification $notification): bool
    {
        return false; // Восстановление не нужно
    }

    public function forceDelete(User $user, DatabaseNotification $notification): bool
    {
        // Администраторы могут удалять любые уведомления
        if ($user->hasRole('admin')) {
            return true;
        }
        
        return $user->hasPermissionTo('delete_any_notification') &&
               $notification->notifiable_type === 'App\Models\User' &&
               $notification->notifiable_id === $user->id;
    }
    
    // Дополнительный метод для отметки прочитанным
    public function markAsRead(User $user, DatabaseNotification $notification): bool
    {
        // Администраторы могут отмечать прочитанным любые уведомления
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Остальные могут отмечать прочитанным только свои уведомления
        return $notification->notifiable_type === 'App\Models\User' &&
               $notification->notifiable_id === $user->id;
    }
}
