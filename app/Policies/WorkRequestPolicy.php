<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkRequest;
use Illuminate\Auth\Access\Response;

class WorkRequestPolicy
{
    public function viewAny(User $user): bool
    {
        // Админ, manager, viewer, dispatcher, initiator видят все заявки
        if ($user->hasAnyRole(['admin', 'manager', 'viewer', 'dispatcher', 'initiator'])) {
            return true;
        }
        
        // HR видит заявки, связанные с рекрутингом
        if ($user->hasRole('hr')) {
            return $user->can('view_any_work_request');
        }
        
        return false;
    }

    public function view(User $user, WorkRequest $workRequest): bool
    {
        if ($user->hasAnyRole(['admin', 'manager', 'viewer', 'dispatcher'])) {
            return true;
        }
        
        if ($user->hasRole('initiator')) {
            // Инициатор видит свои заявки
            return $workRequest->initiator_id === $user->id;
        }
        
        return false;
    }

    public function create(User $user): bool
    {
        // Админ, диспетчер, инициатор могут создавать заявки
        return $user->hasAnyRole(['admin', 'dispatcher', 'initiator']);
    }

    public function update(User $user, WorkRequest $workRequest): bool
    {
        if ($user->hasRole('admin')) return true;
        
        if ($user->hasRole('dispatcher')) {
            // Диспетчер может редактировать заявки, которые он взял в работу
            return $workRequest->dispatcher_id === $user->id;
        }
        
        if ($user->hasRole('initiator')) {
            // Инициатор может редактировать свои заявки до публикации
            return $workRequest->initiator_id === $user->id &&
                   $workRequest->status === 'draft';
        }
        
        return false;
    }

    public function delete(User $user, WorkRequest $workRequest): bool
    {
        return $user->hasRole('admin');
    }

    // Специальные действия
    public function publish(User $user, WorkRequest $workRequest): bool
    {
        // Инициатор может публиковать свои черновики
        return $user->hasRole('initiator') &&
               $workRequest->initiator_id === $user->id &&
               $workRequest->status === 'draft';
    }

    public function takeInProgress(User $user, WorkRequest $workRequest): bool
    {
        // Диспетчер может брать в работу опубликованные заявки
        return $user->hasRole('dispatcher') &&
               $workRequest->status === 'published';
    }
}
