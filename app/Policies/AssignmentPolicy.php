<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'dispatcher', 'initiator']);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->hasRole('admin') || 
               ($user->hasRole('dispatcher') && $assignment->assignment_type !== 'brigadier_schedule') ||
               ($user->hasRole('initiator') && $assignment->assignment_type === 'brigadier_schedule');
    }

    public function create(User $user): bool
    {
        // Проверяем тип создаваемого назначения через request
        $assignmentType = request()->input('assignment_type');
        
        return match($assignmentType) {
            'brigadier_schedule' => $user->can('create_brigadier_schedule'),
            'work_request' => $user->can('create_work_request_assignment'),
            'mass_personnel' => $user->can('create_mass_personnel_assignment'),
            default => $user->hasRole('admin')
        };
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($assignment->assignment_type === 'brigadier_schedule') {
            // Инициаторы могут только отменять свои бригадирские назначения
            return $user->hasRole('initiator') && 
                   $user->can('cancel_assignments') &&
                   $assignment->status === 'pending';
        }

        // Диспетчеры могут редактировать назначения на заявки и массовый персонал
        return $user->hasRole('dispatcher') && 
               in_array($assignment->assignment_type, ['work_request', 'mass_personnel']) &&
               $user->can('edit_assignments');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        // Только админы могут удалять
        return $user->hasRole('admin') && $user->can('delete_assignments');
    }

    public function cancel(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($assignment->assignment_type === 'brigadier_schedule') {
            return $user->hasRole('initiator') && $user->can('cancel_assignments');
        }

        return $user->hasRole('dispatcher') && $user->can('cancel_assignments');
    }
}