<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    /**
     * Кто может видеть список назначений
     */
    public function viewAny(User $user): bool
    {
        // Админ, manager, viewer видят все
        if ($user->hasAnyRole(['admin', 'manager', 'viewer'])) {
            return true;
        }
        
        // Диспетчер видит назначения на заявки и массовый персонал
        if ($user->hasRole('dispatcher')) {
            return true;
        }
        
        // Инициатор видит плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return true;
        }
        
        // Исполнитель, contractor_executor, trainee видят только свои
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->can('view_assignment'); // У них есть это разрешение
        }
        
        // HR, contractor_admin не видят назначения
        return false;
    }

    /**
     * Кто может просматривать конкретное назначение
     */
    public function view(User $user, Assignment $assignment): bool
    {
        // Админ, manager, viewer видят все
        if ($user->hasAnyRole(['admin', 'manager', 'viewer'])) {
            return true;
        }
        
        // Диспетчер видит назначения на заявки и массовый персонал
        if ($user->hasRole('dispatcher')) {
            return in_array($assignment->assignment_type, ['work_request', 'mass_personnel', 'brigadier_schedule']);
        }
        
        // Инициатор видит плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return $assignment->assignment_type === 'brigadier_schedule';
        }
        
        // Исполнитель, contractor_executor, trainee видят только свои
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->id === $assignment->user_id;
        }
        
        return false;
    }

    /**
     * Кто может создавать назначения
     */
    public function create(User $user): bool
    {
        // Админ может всё
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Диспетчер может создавать все типы назначений
        if ($user->hasRole('dispatcher')) {
            return $user->can('create_assignment');
        }
        
        // Инициатор может создавать только плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            // Проверяем тип через request (если создание из формы)
            $assignmentType = request()->input('assignment_type');
            return $assignmentType === 'brigadier_schedule' && $user->can('create_assignment');
        }
        
        return false;
    }

    /**
     * Кто может изменять назначение
     */
    public function update(User $user, Assignment $assignment): bool
    {
        // Админ может всё
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Проверяем, что дата не в прошлом (для инициатора и диспетчера)
        if ($assignment->planned_date < now()->startOfDay()) {
            return false;
        }
        
        // Диспетчер может изменять свои назначения
        if ($user->hasRole('dispatcher')) {
            // Проверяем, что назначение создано этим диспетчером
            // Добавим поле created_by в модель Assignment
            return $assignment->created_by === $user->id &&
                   in_array($assignment->assignment_type, ['work_request', 'mass_personnel', 'brigadier_schedule']);
        }
        
        // Инициатор может изменять только свои плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return $assignment->assignment_type === 'brigadier_schedule' &&
                   $assignment->created_by === $user->id;
        }
        
        return false;
    }

    /**
     * Кто может удалять назначение
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        // Только админ может удалять
        return $user->hasRole('admin');
    }

    /**
     * Кто может подтверждать назначение
     */
    public function confirm(User $user, Assignment $assignment): bool
    {
        // Админ может всё
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Проверяем, что назначение ожидает подтверждения
        if ($assignment->status !== 'pending') {
            return false;
        }
        
        // Диспетчер может подтверждать назначения массового персонала
        if ($user->hasRole('dispatcher')) {
            return $assignment->assignment_type === 'mass_personnel';
        }
        
        // Contractor_dispatcher может подтверждать заявки массового персонала
        if ($user->hasRole('contractor_dispatcher')) {
            // Здесь логика для подтверждения заявок, а не назначений
            return false;
        }
        
        // Исполнитель, contractor_executor, trainee могут подтверждать только свои назначения
        if ($user->hasAnyRole(['executor', 'contractor_executor', 'trainee'])) {
            return $user->id === $assignment->user_id &&
                   in_array($assignment->assignment_type, ['work_request', 'brigadier_schedule']);
        }
        
        return false;
    }

    /**
     * Кто может отклонять назначение
     */
    public function reject(User $user, Assignment $assignment): bool
    {
        // Та же логика, что и для подтверждения
        return $this->confirm($user, $assignment);
    }

    /**
     * Кто может отменять назначение
     */
    public function cancel(User $user, Assignment $assignment): bool
    {
        // Админ может всё
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Проверяем, что дата не в прошлом
        if ($assignment->planned_date < now()->startOfDay()) {
            return false;
        }
        
        // Диспетчер может отменять свои назначения
        if ($user->hasRole('dispatcher')) {
            return $assignment->created_by === $user->id;
        }
        
        // Инициатор может отменять свои плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return $assignment->assignment_type === 'brigadier_schedule' &&
                   $assignment->created_by === $user->id;
        }
        
        return false;
    }

    /**
     * Кто может назначать другого исполнителя
     */
    public function reassign(User $user, Assignment $assignment): bool
    {
        // Админ может всё
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Проверяем, что дата не в прошлом
        if ($assignment->planned_date < now()->startOfDay()) {
            return false;
        }
        
        // Диспетчер может переназначать свои назначения
        if ($user->hasRole('dispatcher')) {
            return $assignment->created_by === $user->id &&
                   in_array($assignment->assignment_type, ['work_request', 'mass_personnel']);
        }
        
        // Инициатор может переназначать свои плановые назначения бригадира
        if ($user->hasRole('initiator')) {
            return $assignment->assignment_type === 'brigadier_schedule' &&
                   $assignment->created_by === $user->id;
        }
        
        return false;
    }
}
